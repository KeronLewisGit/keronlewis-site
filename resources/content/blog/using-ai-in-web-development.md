---
title: How to Use AI in Web Development Without Shipping Bad Code
seo_title: AI in Web Development: Use It Without Shipping Bad Code
description: A developer's process for using AI in web development: what to hand to the model, what to keep, how to review its code and which security traps to avoid.
category: AI
keyword: ai in web development
date: 2026-11-10
---

The safe way to use AI in web development is to treat every suggestion as a pull request from a fast, confident contributor who has never run your app. Give it small, well-scoped tasks with real context, read every line of the diff, run the tests, and check every new dependency before it lands. Keep decisions about architecture, security and data in your own hands.

AI is good at drafts: boilerplate, form validation, test scaffolding, regex, SQL you would otherwise look up, explaining an unfamiliar codebase. It is bad at knowing what it doesn't know. It will invent a package, call a method that was removed two versions ago, or skip an authorization check, and the code will still look tidy.

I use AI coding assistants every working day on Laravel, WordPress and React projects for Caribbean businesses. This is the process I follow so the speed doesn't come with a pile of bugs I'll be fixing for the client six months later.

## Where AI helps in web development, and where it doesn't

The first decision is which tasks to hand over at all. My rough split:

| Task | Hand it to AI? | What I still check |
| --- | --- | --- |
| CRUD controllers, form requests, migrations | Yes | Validation rules, mass-assignment, authorization |
| Writing tests for code that already exists | Yes | That the tests fail when the code is broken |
| Explaining a legacy file or a stack trace | Yes | The explanation against the actual code |
| CSS layout fixes, Tailwind classes | Yes | Mobile widths, focus states, contrast |
| Refactoring a long method | Yes, in small steps | Behavior is unchanged, tests still pass |
| Payment and webhook handling | Draft only | Signature checks, idempotency, amounts, logging |
| Authentication and permissions | Draft only | Every route and policy by hand |
| Database design for a new product | Discussion only | I make the final call |
| Choosing a new dependency | No | I research it myself |

The pattern: the more a mistake costs, the less I let the model decide. A wrong Tailwind class is a two-minute fix. A webhook that credits the same payment twice is a refund conversation with a client.

## A workflow for AI-assisted coding that keeps quality high

This is the loop I run for almost every change, whether I'm in an editor chat, an inline completion or an agent that edits files on its own.

1. **Write the task down first.** One or two sentences: what changes, which files, what "done" looks like. If I can't write that, the task is too big or too vague for AI, and probably for me too.
2. **Give it the context it can't see.** Framework and version, the relevant files, the conventions the project uses. More on this below.
3. **Ask for a plan before code** on anything bigger than a single function. Reading a five-line plan is faster than reading a 200-line diff that went the wrong way.
4. **Keep changes small.** One concern per request. Big multi-file rewrites are where hallucinated methods and silent behavior changes hide.
5. **Read the diff like a reviewer**, not like the author. I use the checklist further down.
6. **Run the tests and the app.** Not "looks right". Run it, click through it, check the logs.
7. **Commit in small steps** so a bad change is one `git revert` away.

> The model writes the first draft. You are still the person who ships it, so you are still the person who has to understand it.

## Give the model the context it can't see

Most bad AI code comes from missing context, not from a weak model. The assistant doesn't know you're on Laravel 13 and PHP 8.3, that your team uses form request classes instead of inline validation, or that the client's host is shared hosting with no Redis.

Fix that once with a project instructions file. Most tools now read one automatically: `CLAUDE.md` for Claude Code, `.github/copilot-instructions.md` for GitHub Copilot, and `AGENTS.md`, an [open format](https://agents.md/) several agents support. A short one does the job:

```markdown
# Project conventions

- Laravel 13, PHP 8.3, MySQL 8. Deployed to shared hosting: no queues worker, no Redis.
- Validation lives in Form Request classes, never in controllers.
- Use policies for authorization. Every admin route must be behind the `can:` middleware.
- Tests use Pest. Run `php artisan test` before saying a task is done.
- Do not add Composer or npm packages without asking.
- Money is stored as integer cents. Currency is TTD unless the model says otherwise.
```

Then, per task, paste or point at the files that matter. If you want the model to follow an existing pattern, show it one existing file that does it right. That single example usually beats a paragraph of description. I cover more of these techniques in [12 prompt engineering patterns for developers](/blog/prompt-engineering-for-developers).

## Review AI-generated code like a pull request from a stranger

AI code fails in predictable ways, so review for those specifically. My checklist:

- [ ] **Does every function, method and config key it calls exist** in the versions I'm running? Check the docs, not the model's memory.
- [ ] **Did it add a dependency?** Is the package real, maintained, and the one I think it is?
- [ ] **Is user input validated** and is output escaped? Look for raw SQL, `{!! !!}` in Blade, `dangerouslySetInnerHTML` in React.
- [ ] **Is authorization checked** on every new route, not only authentication?
- [ ] **Did it change things I didn't ask for?** Reformatted files, renamed variables, deleted a "redundant" check.
- [ ] **Are errors handled** or swallowed? Empty `catch` blocks are a common shortcut.
- [ ] **Are secrets hard-coded?** API keys and URLs belong in `.env`.
- [ ] **Would I be able to explain this code to the client's next developer?**

If I can't explain a block, I ask the model to explain it, then I check the explanation against the docs. If it still doesn't make sense, I rewrite it myself. Code nobody understands is the most expensive code in a project.

## Security risks specific to AI-generated code

Beyond the usual web vulnerabilities, AI adds a few of its own.

**Hallucinated packages.** Models sometimes recommend packages that don't exist. Attackers have started registering those made-up names with malicious code, a trick now called "slopsquatting". A [USENIX Security 2025 paper](https://www.usenix.org/conference/usenixsecurity25/presentation/spracklen) tested 16 code-generation models and found hundreds of thousands of unique hallucinated package names across Python and JavaScript. Before you run `composer require` or `npm install` on a suggestion, open the package page, check the maintainer, the download history and the repository.

**Outdated patterns.** Training data lags. You'll get deprecated helpers, old config formats and security advice that was replaced years ago. Check against the current framework docs.

**Leaking data into prompts.** Pasting a production database dump or a client's customer list into a chat tool sends it to a third party. Use fake or anonymized data, and read the tool's data-use terms. If you work under GDPR or a client's privacy policy, this matters.

**AI features inside your app.** If the site itself calls an LLM (a chatbot, a summarizer), you have a new class of risk: prompt injection, leaking the system prompt, unbounded API costs. The [OWASP Top 10 for LLM Applications](https://genai.owasp.org/resource/owasp-top-10-for-llm-applications-2025/) is the best checklist I know for that. For the classic issues, run through a [Laravel security checklist](/blog/laravel-security-checklist) before launch.

## Tests are the guardrail that makes AI speed safe

The fastest way to catch confident wrong code is a test that runs every time. AI is good at writing tests, which is convenient, but a test the model wrote for code the model wrote can share the same misunderstanding. Two habits help.

First, write or describe the test cases yourself, in plain language, then let the AI turn them into code. You decide what correct means.

Second, check that the test fails when it should. Break the code on purpose and run it. A test that passes against broken code is decoration.

A small Pest example for an admin route that AI scaffolded:

```php
<?php

use App\Models\User;

it('blocks non-admins from the orders export', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/orders/export')
        ->assertForbidden();
});

it('lets admins download the orders export', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/orders/export')
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
```

That first test is the one that matters. Assistants often add the route and forget the policy. A two-line test catches it every time. The [Laravel testing docs](https://laravel.com/docs/13.x/testing) cover the rest.

## Using AI on client projects in the Caribbean

A few things are specific to how I work here.

**Client data stays out of prompts.** A hospital, school or security firm in Trinidad trusts you with patient, student or guard records. Use seeded fake data when you need the model to see rows.

**Plan for the hosting the client actually has.** Many small businesses here run on shared hosting. AI tools default to suggesting queues, Redis, Docker and serverless functions. Tell the model up front, or you'll get an architecture the host can't run.

**Budget for tool costs in USD.** Most AI coding tools bill in US dollars on a credit card. Account for that in your rates. Pricing changes often, so check the current plans rather than trusting a blog post.

**Keep it working offline.** Broadband outside the towns isn't always reliable. Don't build a workflow where you can't write code when the AI service or your connection drops. Know your framework well enough to keep going alone.

## What to do next: set up your guardrails this week

You don't need a policy document. Do these five things on your current project:

1. Add a short instructions file (`CLAUDE.md`, `AGENTS.md` or the Copilot equivalent) with your stack, versions and rules.
2. Add a rule that no package gets installed without you checking it first.
3. Make sure `php artisan test` or `npm test` runs in under a minute so you actually run it after every AI change.
4. Print the review checklist above and use it for a week.
5. Commit after every accepted change.

If you're still choosing a tool, my comparison of [AI coding assistants](/blog/ai-coding-assistants) covers the main options. And if you'd rather have an experienced developer build or review a web app for your business, you can see how I work on the [web app development](/services/web-app-development) page.

## Frequently asked questions

### Will AI replace web developers?

Not in the way the headlines suggest. AI writes a lot of routine code faster than a person, but someone still has to decide what to build, understand the business rules, review the output, handle security and take responsibility when something breaks. The developers most at risk are those who only produce boilerplate. Developers who can specify, review and ship working systems are getting more done with AI, not less work.

### Is AI-generated code safe to use in production?

It can be, once it has been reviewed and tested like any other code. The risk is shipping it unread. Common problems are missing authorization checks, unvalidated input, invented packages or methods, and outdated APIs. Read every line, run your test suite, check new dependencies by hand and avoid using AI output for payment or authentication logic without a careful manual review.

### What is the best way to use AI as a beginner web developer?

Use it as a tutor more than a code generator. Ask it to explain errors, compare approaches and review code you wrote yourself. When it does write code, type it out or rewrite it rather than pasting it, and make sure you can explain each line. Beginners who paste code they don't understand get stuck the moment something breaks, and with AI code something eventually breaks.

### How do I stop AI from inventing functions and packages?

Give it the exact framework and library versions, paste the relevant documentation or existing code, and tell it not to add dependencies without asking. Ask it to cite where a method comes from. Then verify against the official docs before running anything. Tools that can search the web or read your installed vendor files hallucinate less, but you still need to check.
