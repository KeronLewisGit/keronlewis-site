---
title: Core Web Vitals in 2026: A Developer's Checklist (LCP, INP, CLS)
seo_title: Core Web Vitals Checklist 2026: Fix LCP, INP and CLS
description: A practical Core Web Vitals checklist for developers: 2026 thresholds for LCP, INP and CLS, how to measure real users and the fixes that move each score.
category: Web Development
keyword: core web vitals checklist
date: 2026-11-10
---

To pass Core Web Vitals in 2026, a page needs Largest Contentful Paint (LCP) of 2.5 seconds or less, Interaction to Next Paint (INP) of 200 milliseconds or less, and Cumulative Layout Shift (CLS) of 0.1 or less, all measured at the 75th percentile of real visits on mobile and desktop. Lab scores help you debug, but field data from real users is what counts.

This checklist is the order I work through on client sites: measure with field data, find the worst metric, fix the causes that usually matter, then confirm with real users again. Most sites fail on one metric, not three, so measure before changing anything.

The examples lean on Laravel and WordPress because that's what I build on, but every item applies to any stack.

## Core Web Vitals thresholds in 2026

The thresholds haven't changed since INP replaced First Input Delay (FID) as a Core Web Vital in March 2024. From Google's [Web Vitals overview](https://web.dev/articles/vitals):

| Metric | Measures | Good | Needs improvement | Poor |
| --- | --- | --- | --- | --- |
| LCP | Loading: when the largest visible element renders | 2.5 s or less | 2.5 s to 4 s | Over 4 s |
| INP | Responsiveness: delay from a click, tap or key press to the next frame | 200 ms or less | 200 ms to 500 ms | Over 500 ms |
| CLS | Visual stability: unexpected layout movement | 0.1 or less | 0.1 to 0.25 | Over 0.25 |

Two details people miss:

- **The 75th percentile.** A page passes if at least 75% of visits meet the "good" target. Your fast office connection is not the test. Visitors on older phones and weaker networks pull the number down.
- **INP covers the whole visit.** It observes clicks, taps and key presses (not scrolling or hover) for as long as the page is open, and reports one of the slowest. A page can load fast and still fail INP because a filter or menu is slow.

Google says good Core Web Vitals align with what its [ranking systems reward](https://developers.google.com/search/docs/appearance/core-web-vitals), but relevance and content still come first. Treat this as user experience work that also helps search, not a ranking hack.

## How to measure: field data first, lab data second

**Field data** is from real Chrome users, collected in the Chrome UX Report (CrUX). It's what Google uses. You can see it in three places:

1. **[PageSpeed Insights](https://pagespeed.web.dev/)**: the "Discover what your real users are experiencing" section at the top. Shows URL-level data if the page has enough traffic, otherwise the whole origin.
2. **Google Search Console**, Core Web Vitals report: groups similar URLs and shows which groups fail. Best for sites with many pages.
3. **Your own monitoring** with Google's [web-vitals library](https://github.com/GoogleChrome/web-vitals), which reports the same metrics from your visitors to your own endpoint.

CrUX data covers a rolling 28-day window, so a fix takes up to four weeks to show fully in PageSpeed Insights and Search Console. Don't panic when the number doesn't move the next day.

**Lab data** (Lighthouse, the "Diagnose performance issues" section of PageSpeed Insights, Chrome DevTools) runs a single simulated load. It's useful for finding causes, not for judging whether you pass. Lab tests can't measure INP at all because nobody is clicking. Lighthouse reports Total Blocking Time as a rough stand-in.

Small sites often have no CrUX data. In that case, collect your own. This snippet sends each metric to a Laravel route:

```javascript
import { onLCP, onINP, onCLS } from 'web-vitals/attribution';

function send(metric) {
  const body = JSON.stringify({
    name: metric.name,
    value: metric.value,
    rating: metric.rating,
    page: location.pathname,
    // Attribution tells you which element or interaction caused the score.
    target:
      metric.attribution?.target ?? // LCP element
      metric.attribution?.interactionTarget ?? // INP element
      metric.attribution?.largestShiftTarget ?? // CLS element
      null,
  });
  navigator.sendBeacon('/vitals', body);
}

onLCP(send);
onINP(send);
onCLS(send);
```

Store the rows, then look at the 75th percentile per page and per device type. The attribution build tells you which element was the LCP and which interaction was slow, which saves hours of guessing.

## LCP checklist: make the main content appear fast

LCP is usually a hero image, a large heading or a banner. Find the actual element first (PageSpeed Insights and the attribution data both name it). Then work down this list.

- [ ] **Fast server response (TTFB).** Cache full pages where you can. In Laravel, run `php artisan optimize` on deploy, cache expensive queries and avoid N+1 queries on the homepage. In WordPress, use page caching. A slow server makes every other fix smaller.
- [ ] **The LCP image is discoverable in the HTML.** Use a normal `<img>` tag, not a CSS background or an image injected by JavaScript, so the browser finds it early.
- [ ] **Never lazy-load the LCP image.** Remove `loading="lazy"` from the hero. Lazy-load images further down the page instead.
- [ ] **Give the LCP image high priority** with the `fetchpriority="high"` attribute.
- [ ] **Right-sized, modern formats.** Serve AVIF or WebP with `srcset` and `sizes` so a phone doesn't download a 2,400-pixel desktop image.
- [ ] **Fonts don't hide text.** Use `font-display: swap` (or `optional`), self-host fonts, and preload only the one or two files used above the fold.
- [ ] **Render-blocking CSS and JavaScript are minimal.** Defer non-essential scripts. Load chat widgets, heatmaps and social embeds after the page is usable.
- [ ] **No client-side rendering for the main content** when the server could send it. A blank shell that fills in after JavaScript runs delays LCP on slow phones.
- [ ] **A CDN for static assets** if your server is far from your visitors.

Google's optimize LCP guide on web.dev breaks LCP into four parts (server response, resource load delay, load duration, render delay), which helps you see which item above is the real problem.

## INP checklist: make every interaction respond fast

INP failures come from JavaScript keeping the main thread busy when the user taps something. The checklist:

- [ ] **Find the slow interaction.** Use web-vitals attribution data or the Performance panel in Chrome DevTools while clicking through menus, filters, forms and accordions.
- [ ] **Break up long tasks.** Any task over 50 ms blocks input. Split work into chunks and yield to the browser between them (code below).
- [ ] **Give visual feedback first, do heavy work after.** Update the button state or open the menu immediately, then run the expensive part.
- [ ] **Cut third-party scripts.** Tag managers, chat widgets, A/B testing tools and ad scripts are frequent INP problems. Remove what nobody uses, and load the rest after interaction or idle time.
- [ ] **Keep the DOM small.** Huge pages (page builders are common offenders) make every style recalculation and layout slower.
- [ ] **Avoid layout thrashing.** Don't read layout values (like `offsetHeight`) and write styles in the same loop.
- [ ] **Debounce input handlers** for search-as-you-type and filters.

Yielding to the main thread looks like this. `scheduler.yield()` is [not supported in every browser](https://developer.mozilla.org/en-US/docs/Web/API/Scheduler/yield) yet, so feature-detect it and fall back to `setTimeout`:

```javascript
function yieldToMain() {
  if (globalThis.scheduler?.yield) {
    return scheduler.yield();
  }
  return new Promise((resolve) => setTimeout(resolve, 0));
}

async function applyFilters(items, filters) {
  showLoadingState(); // Give feedback in the same frame as the click.
  await yieldToMain();

  const results = [];
  for (let i = 0; i < items.length; i++) {
    if (matches(items[i], filters)) results.push(items[i]);
    if (i % 200 === 0) await yieldToMain(); // Let pending input run.
  }
  render(results);
}
```

Google's optimize INP guide on web.dev goes further into input delay, processing time and presentation delay.

## CLS checklist: stop the page from jumping

CLS is usually the easiest metric to fix once you see what's moving.

- [ ] **Set `width` and `height` on every image and video**, or use CSS `aspect-ratio`, so the browser reserves space before the file loads.
- [ ] **Reserve space for embeds, ads and iframes** with a fixed-height container.
- [ ] **Don't insert content above existing content** after load: cookie banners, promo bars and "download our app" strips should overlay the page or have reserved space.
- [ ] **Match fallback fonts to web fonts** so text doesn't reflow when the font arrives. The CSS `size-adjust` descriptor helps.
- [ ] **Animate with `transform` and `opacity`**, not `top`, `left`, `height` or `width`.
- [ ] **Keep pages eligible for the back/forward cache** (avoid `unload` handlers). Instant back navigation also avoids load-time shifts.

Chrome DevTools can highlight layout shift regions as they happen, and web.dev's optimize CLS guide lists the less common causes.

## Fixes for Laravel and WordPress sites

**Laravel.** Use Vite to bundle and version assets so they can be cached long-term. Cache config, routes and views on deploy. Eager-load relationships to avoid N+1 queries on listing pages. Keep Alpine or Livewire components small, and be careful with large Livewire components that re-render on every keystroke, a common INP issue. Use responsive image markup in your Blade components so every image gets dimensions and `srcset` by default.

**WordPress.** The usual culprits are page builders that output deep DOM trees, sliders in the hero (bad for LCP and CLS), and plugins that load scripts on every page whether they're used or not. Use page caching, an image optimization approach that serves WebP or AVIF, and remove plugins you don't need. A lightweight block theme often beats months of tuning a heavy one. If you're still deciding between the two platforms, I compare them in [Laravel vs WordPress](/blog/laravel-vs-wordpress).

## Testing for real Caribbean visitors

Many of my clients' visitors are on phones, on mobile data, sometimes outside town where coverage is weaker. A site that scores well on office broadband can fail in the field. What I do:

- **Test on a mid-range Android phone**, not only a recent iPhone or a laptop. That's closer to the 75th percentile visitor.
- **Throttle in DevTools** to a slow 4G profile and a 4x CPU slowdown when testing locally.
- **Check where the server is.** A lot of Caribbean sites are hosted in the US or Europe. That's usually fine with caching and a CDN for static files, but uncached pages pay the distance on every request.
- **Watch the extras.** WhatsApp chat buttons, booking widgets and social feeds are useful, but each one adds JavaScript. Load them after the main content.

## What to do next: a one-week plan

1. **Day 1:** Run your top five pages through PageSpeed Insights. Note which metric fails on mobile.
2. **Day 2:** Add the web-vitals snippet so you have your own field data going forward.
3. **Days 3 to 4:** Work through the checklist for the failing metric only.
4. **Day 5:** Deploy, then check lab results to confirm the fix.
5. **Four weeks later:** Check CrUX data in PageSpeed Insights or Search Console to confirm real users see the change.

Accessibility and performance work well together, so pair this with a [WCAG 2.2 accessibility checklist](/blog/wcag-accessibility-checklist) pass while you're in the templates. If you'd rather have someone audit and fix it for you, performance work is part of every [website development](/services/website-development) project I take on.

## Frequently asked questions

### What is a good INP score?

A good Interaction to Next Paint score is 200 milliseconds or less at the 75th percentile of page visits. Between 200 and 500 milliseconds needs improvement, and anything over 500 milliseconds is poor. INP measures the delay between a click, tap or key press and the next visual update, across the whole visit, so slow menus, filters and forms after load all count.

### Do Core Web Vitals affect Google rankings?

Google says page experience, including Core Web Vitals, aligns with what its core ranking systems reward, and recommends achieving good scores. It also makes clear that relevant, helpful content matters more. In practice, passing Core Web Vitals won't rescue weak content, but slow, jumpy pages lose visitors, and that hurts conversions whatever the ranking effect.

### Why does PageSpeed Insights say there is no field data for my site?

The Chrome UX Report only publishes data for pages and origins with enough real Chrome traffic over a 28-day period. Small or new sites often fall below that threshold. PageSpeed Insights will then show only lab results. To get real-user numbers anyway, add the web-vitals JavaScript library and send the metrics to your own analytics or a simple logging endpoint.

### Why are my lab and field Core Web Vitals scores different?

Lab tools like Lighthouse run one simulated page load on fixed hardware and network settings. Field data comes from many real visitors with different phones, connections, cached files and behavior over 28 days. Lab tests also can't measure INP because no one interacts with the page. Use lab data to find and debug problems, and field data to judge whether you pass.

### Is First Input Delay still a Core Web Vital?

No. Interaction to Next Paint replaced First Input Delay as the responsiveness Core Web Vital in March 2024. FID only measured the delay before the browser started handling the first interaction. INP measures the full time to the next frame for interactions throughout the visit, so it catches slow responses that FID missed. Tools have removed FID from their Core Web Vitals reports.
