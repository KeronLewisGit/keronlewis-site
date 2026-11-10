---
title: WCAG 2.2 Checklist: Accessibility for Web Developers
seo_title: WCAG 2.2 Checklist for Web Developers (AA, With Code)
description: A practical WCAG 2.2 AA checklist for developers: the new success criteria, keyboard, forms, contrast and target size, with code fixes and tests.
category: Web Development
keyword: wcag 2.2 checklist
date: 2026-11-10
---

A WCAG 2.2 checklist for developers comes down to this: meet every Level A and AA success criterion, which means semantic HTML, full keyboard access with a visible focus that isn't hidden, labelled forms with clear errors, 4.5:1 text contrast, text alternatives for images and media, and the six new A and AA criteria that 2.2 added, such as 24px minimum targets and login without memory tests.

WCAG 2.2 is the current W3C recommendation and, since 2025, also an ISO standard (ISO/IEC 40500:2025). It is backwards compatible: a site that meets 2.2 AA also meets 2.1 AA and 2.0 AA, which is what most laws still name.

I use the checklist below on the business sites I build. It's grouped by the part of the page you're working on, with the success criterion numbers so you can look each one up in the official [WCAG 2.2 quick reference](https://www.w3.org/WAI/WCAG22/quickref/).

## What changed in WCAG 2.2

WCAG 2.2 added nine success criteria and removed one. 4.1.1 Parsing is now obsolete, because modern browsers and assistive technology handle markup errors consistently. The full list with examples is on the W3C's [What's New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/) page.

| Criterion | Level | What it asks for | Typical fix |
| --- | --- | --- | --- |
| 2.4.11 Focus Not Obscured (Minimum) | AA | A focused element isn't completely hidden by your own content | `scroll-padding` for sticky headers, banners that don't cover focus |
| 2.4.12 Focus Not Obscured (Enhanced) | AAA | No part of the focused element is hidden | Same, stricter |
| 2.4.13 Focus Appearance | AAA | A focus indicator that is large and contrasting enough | Thick outline with 3:1 contrast |
| 2.5.7 Dragging Movements | AA | Anything done by dragging can be done with single clicks or taps | Buttons next to sliders and drag-to-sort lists |
| 2.5.8 Target Size (Minimum) | AA | Pointer targets at least 24 by 24 CSS pixels, or enough spacing | Minimum sizes on icon buttons and small links |
| 3.2.6 Consistent Help | A | Help options appear in the same relative order across pages | Contact link and chat in the same place site-wide |
| 3.3.7 Redundant Entry | A | Don't make people retype information already given in the same process | Prefill or offer "same as billing" |
| 3.3.8 Accessible Authentication (Minimum) | AA | No memory or puzzle test to log in without an alternative | Allow paste and password managers, offer email links |
| 3.3.9 Accessible Authentication (Enhanced) | AAA | Stricter version with fewer exceptions | No object-recognition tests either |

For a Level AA target, the six that matter are 2.4.11, 2.5.7, 2.5.8, 3.2.6, 3.3.7 and 3.3.8. In my experience the two that trip up existing sites most are target size and focus hidden behind a sticky header.

## Which WCAG level should you target?

Level AA. It's what laws and contracts ask for, and Level AAA isn't expected for whole sites. Some examples of where AA shows up:

- **United States**: the Department of Justice's ADA Title II rule requires WCAG 2.1 AA for state and local government websites and apps. An April 2026 interim rule pushed the deadlines back a year, to April 2027 for larger governments and April 2028 for smaller ones. The [ADA.gov fact sheet](https://www.ada.gov/resources/2024-03-08-web-rule/) has the current dates.
- **European Union**: the European Accessibility Act has applied since 28 June 2025 to many consumer products and services, including e-commerce, so Caribbean businesses selling into the EU can fall under it.
- **Procurement**: governments, universities and large companies increasingly put WCAG AA into tenders and supplier contracts.

Laws change and depend on where you and your customers are, so check the rules that apply to you. Targeting 2.2 AA covers the 2.1 requirements anyway.

## Structure and semantics checklist

Most accessibility comes free with correct HTML. Assistive technology reads the structure you mark up, not the one you style.

- [ ] Page has a unique, descriptive `<title>` (2.4.2)
- [ ] `<html lang="en">` set, and `lang` on passages in another language (3.1.1, 3.1.2)
- [ ] One `<h1>`, and headings in order without skipping levels for styling (1.3.1, 2.4.6)
- [ ] Landmarks used: `<header>`, `<nav>`, `<main>`, `<footer>` (1.3.1)
- [ ] A skip link to the main content (2.4.1)
- [ ] Lists, tables and buttons use real `<ul>`, `<table>` and `<button>` elements (1.3.1, 4.1.2)
- [ ] Link text makes sense on its own or in its sentence, so no bare "click here" (2.4.4)
- [ ] Custom widgets follow the W3C ARIA Authoring Practices patterns, and ARIA is only used where HTML has no native element (4.1.2)

A skip link that stays out of the way until it's needed:

```html
<a class="skip-link" href="#main">Skip to main content</a>
<!-- header and navigation -->
<main id="main" tabindex="-1">...</main>
```

```css
.skip-link {
  position: absolute;
  left: 1rem;
  top: -100%;
}
.skip-link:focus {
  top: 1rem;
  z-index: 100;
}
```

## Keyboard and focus checklist

Test every page with the keyboard alone: Tab, Shift+Tab, Enter, Space, the arrow keys and Escape.

- [ ] Everything clickable can be reached and used with the keyboard (2.1.1)
- [ ] No keyboard traps: focus can always move out of a widget or modal (2.1.2)
- [ ] Focus order follows the visual and reading order (2.4.3)
- [ ] Focus is always visible, and never removed with `outline: none` without a replacement (2.4.7)
- [ ] The focused element is not completely hidden by sticky headers, cookie banners or chat widgets (2.4.11, new)
- [ ] Modals move focus inside on open, trap it while open and return it on close
- [ ] Content that appears on hover or focus can be dismissed with Escape and doesn't vanish when the pointer moves onto it (1.4.13)

Two CSS rules fix most focus problems on the sites I work on:

```css
/* A clear focus ring for keyboard users, not shown on mouse clicks */
:focus-visible {
  outline: 3px solid #1a56db;
  outline-offset: 2px;
}

/* Stop a sticky header from covering focused elements (2.4.11) */
html {
  scroll-padding-top: 5rem; /* at least the header's height */
}
```

`scroll-padding-top` makes the browser leave room above an element when it scrolls it into view, which is what happens when you Tab to a link under a fixed header. Cookie banners and floating WhatsApp or chat buttons are the other common cause: keep them small, or let users dismiss them.

## Forms, errors and login checklist

Forms are where accessibility failures cost businesses money: lost quote requests, abandoned checkouts, locked-out customers.

- [ ] Every input has a visible `<label>` tied to it with `for`/`id` (1.3.1, 3.3.2)
- [ ] The visible label text is part of the accessible name (2.5.3)
- [ ] Required fields are marked in text, not only by colour or an asterisk without explanation (1.4.1, 3.3.2)
- [ ] Errors are described in text next to the field and linked with `aria-describedby` (3.3.1)
- [ ] Error messages suggest the fix ("Enter a date like 14/02/2026") (3.3.3)
- [ ] Personal data fields have the right `autocomplete` values (1.3.5)
- [ ] Information already entered in the same process isn't asked for again, or can be selected (3.3.7, new)
- [ ] Legal, financial and data-deleting submissions can be reviewed, corrected or reversed (3.3.4)
- [ ] Login allows paste and password managers, and doesn't rely on a text CAPTCHA or a memory test without an alternative (3.3.8, new)
- [ ] Success and error messages that appear without a page load are announced (4.1.3)

In a Laravel Blade form, accessible errors take a few attributes:

```blade
<label for="email">Email address</label>
<input id="email" name="email" type="email" autocomplete="email"
       value="{{ old('email') }}"
       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
@error('email')
    <p id="email-error" class="field-error">{{ $message }}</p>
@enderror

<div role="status" aria-live="polite">{{ session('status') }}</div>
```

For 3.3.8, the safest login uses `autocomplete="username"` and `autocomplete="current-password"`, never blocks paste, and offers an email magic link or passkey as an alternative. One-time codes are fine as long as people can paste them in rather than having to retype them from another device.

## Colour, text and layout checklist

- [ ] Body text contrast at least 4.5:1; large text (24px, or about 18.7px bold) at least 3:1 (1.4.3)
- [ ] Icons, input borders and focus indicators at least 3:1 against what's next to them (1.4.11)
- [ ] Colour is never the only way information is shown, such as red-only errors or chart lines told apart only by colour (1.4.1)
- [ ] Text can be zoomed to 200% without loss of content (1.4.4)
- [ ] Content reflows at 320 CSS pixels wide without horizontal scrolling, except tables, maps and similar (1.4.10)
- [ ] Layout survives increased line height, letter and word spacing (1.4.12)
- [ ] The site works with the device upright or turned sideways, without forcing one orientation (1.3.4)
- [ ] Pointer targets are at least 24 by 24 CSS pixels, or spaced so a 24px circle around each doesn't overlap another (2.5.8, new)

Target size is the most common new failure: social icons, close buttons on modals, pagination numbers and inline icon buttons. A safe default:

```css
button,
.icon-button,
.pagination a {
  min-width: 24px;
  min-height: 24px;
}
```

I go further and use 44px for primary buttons and mobile navigation. That matches the AAA criterion 2.5.5 and is kinder to people using phones on the move. Links inside a sentence are exempt from 2.5.8, and so are targets whose size is set by the browser. The W3C's [Understanding Target Size (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html) page explains the spacing exception with diagrams.

Check contrast with your browser's developer tools, which show the ratio when you inspect text, or a dedicated contrast checker. Brand colours often fail on white backgrounds; a darker shade of the same hue usually keeps the brand and passes.

## Images, media and motion checklist

- [ ] Informative images have `alt` text that conveys their purpose; decorative images have `alt=""` (1.1.1)
- [ ] Images of text are avoided, except logos (1.4.5)
- [ ] Videos have captions (1.2.2), and audio description where the visuals carry information the soundtrack doesn't (1.2.3, 1.2.5)
- [ ] Audio-only content, such as podcasts, has a transcript (1.2.1)
- [ ] Auto-playing carousels and animations longer than five seconds can be paused (2.2.2)
- [ ] Nothing flashes more than three times per second (2.3.1)
- [ ] Sliders, maps and drag-to-reorder lists have a single-pointer alternative such as buttons (2.5.7, new)
- [ ] Time limits, like session timeouts on forms, can be turned off, adjusted or extended (2.2.1)

Hero sliders are the usual offender on small business sites. If you keep one, give it a visible pause button and respect reduced-motion settings:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

Reduced motion isn't itself a Level AA requirement, but it costs nothing and helps people who get dizzy or distracted by movement.

## How to test against WCAG 2.2

No single tool proves conformance. Automated scanners catch a useful share of issues, such as missing labels, low contrast and empty links, but they can't judge whether alt text is meaningful or whether focus order makes sense. I test in this order:

1. **Automated scan**: axe DevTools or Lighthouse's accessibility audit on each page template.
2. **Keyboard pass**: unplug the mouse and complete the main tasks, such as booking, checkout and contact.
3. **Zoom and reflow**: 200% zoom, then a 320px-wide window.
4. **Screen reader spot check**: NVDA on Windows or VoiceOver on macOS and iOS, on the main tasks.
5. **Content review**: alt text, link text, headings and error messages read by a person.

The W3C's [evaluation resources](https://www.w3.org/WAI/test-evaluate/) describe this process in more depth, including how to write an accessibility statement. Accessibility work also overlaps with performance: layout shifts and slow-loading pages hurt screen reader and keyboard users too, which I cover in the [Core Web Vitals checklist](/blog/core-web-vitals-checklist).

## Where to start this week

Pick your three most important pages, usually home, a service or product page, and the contact or checkout form. Run the keyboard pass and the target-size and contrast checks on those first, since they catch the problems users hit most. Fix the shared components (header, buttons, form fields) rather than individual pages, so each fix lands site-wide.

Accessibility is also becoming a hiring signal; it's one of the skills in my list of [in-demand developer skills](/blog/in-demand-developer-skills). If you'd like an audit or a rebuild that meets WCAG 2.2 AA from the start, that's part of how I approach [website development](/services/website-development).

## Frequently asked questions

### What is the difference between WCAG 2.1 and WCAG 2.2?

WCAG 2.2 keeps every WCAG 2.1 success criterion except 4.1.1 Parsing, which was removed as obsolete, and adds nine new ones. Six of the new criteria are at Level A or AA: focus not obscured, dragging movements, target size, consistent help, redundant entry and accessible authentication. Meeting WCAG 2.2 AA means you also meet WCAG 2.1 AA.

### Is WCAG 2.2 legally required?

It depends on where you operate. Many laws still reference WCAG 2.1 AA or 2.0 AA, such as the US ADA Title II rule for public bodies. Because WCAG 2.2 is backwards compatible, meeting 2.2 AA satisfies those as well. It's now an ISO standard too, so expect more governments and procurement rules to name it directly.

### What is the minimum target size in WCAG 2.2?

Success criterion 2.5.8 requires pointer targets of at least 24 by 24 CSS pixels at Level AA. Smaller targets pass if they are spaced so a 24px circle centred on each doesn't overlap another target. Inline links in text, targets the browser controls and cases where the size is essential are exempt. Level AAA's 2.5.5 asks for 44 by 44.

### How long does it take to make a website WCAG 2.2 compliant?

A small site built on clean, semantic templates can often be fixed in days, because most issues live in shared components like headers, buttons and forms. Older sites built with heavy page builders or custom widgets take longer, sometimes long enough that a rebuild is cheaper. An audit of key pages and templates gives you a real estimate.

### Can an accessibility overlay make my site compliant?

No overlay widget can fix missing labels, poor focus order, inaccessible custom controls or meaningless alt text in your source code, and many disabled users find overlays get in the way. Conformance comes from fixing the HTML, CSS and JavaScript. Use automated tools to find issues, then fix them in the codebase and test with real assistive technology.
