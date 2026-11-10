---
title: Model Context Protocol (MCP) Explained for Web Developers
seo_title: Model Context Protocol (MCP) Explained for Web Developers
description: What the Model Context Protocol is, how MCP servers and clients talk, what changed in the 2026-07-28 spec, and how to build a secure MCP server in Laravel.
category: AI
keyword: model context protocol
date: 2026-11-10
---

The Model Context Protocol (MCP) is an open standard for connecting AI applications to outside tools and data. An MCP server exposes things an AI model can use, such as tools it can call, resources it can read and prompt templates, and any MCP-compatible client (Claude, ChatGPT, Cursor, VS Code and others) can use them without a custom integration for each one.

For web developers, the useful way to think about MCP is this: it is to AI assistants roughly what a REST API is to front ends. You describe your application's capabilities once, in a standard shape, and many different AI clients can use them. The messages are JSON-RPC, the transports are stdio or plain HTTP, and the security questions are the ones you already deal with: authentication, permissions, validation and rate limits.

This guide covers how MCP works, what changed in the current spec revision (2026-07-28), when it makes sense to build an MCP server rather than a normal API, and how to build one in Laravel.

## What the Model Context Protocol is and who runs it

Anthropic released MCP as an open specification in November 2024. In December 2025 it was donated to the [Agentic AI Foundation](https://www.anthropic.com/news/donating-the-model-context-protocol-and-establishing-of-the-agentic-ai-foundation), a directed fund under the Linux Foundation co-founded by Anthropic, Block and OpenAI. The maintainers still run the spec, but it is now vendor-neutral, which matters if you are deciding whether to invest in it.

The spec is versioned by date. Each version string marks the last date a backwards-incompatible change was made. At the time of writing, the official versioning page lists **2026-07-28** as the current revision. The previous one, 2025-11-25, is still widely deployed, so you will meet both.

The problem it solves is the N-by-M integration mess. Without a standard, every AI client needs its own plugin format for every service. With MCP, a service writes one server and every compliant client can talk to it.

## How MCP works: hosts, clients and servers

There are three roles:

- **Host**: the AI application the user is working in, such as a desktop chat app, an IDE or an agent framework.
- **Client**: the part of the host that speaks MCP to one server.
- **Server**: your code, exposing capabilities to the model.

A server can expose three main kinds of thing, usually called primitives:

| Primitive | What it is | Who decides to use it | Web developer analogy |
|---|---|---|---|
| Tools | Functions the model can call with arguments | The model, usually with user approval | A POST endpoint |
| Resources | Read-only data identified by a URI | The application or user | A GET endpoint |
| Prompts | Reusable prompt templates with arguments | The user, often from a menu | A saved form or template |

Messages use JSON-RPC 2.0. A tool call from a client looks roughly like this:

```json
{
  "jsonrpc": "2.0",
  "id": 42,
  "method": "tools/call",
  "params": {
    "name": "find-order",
    "arguments": { "reference": "INV-1042" }
  }
}
```

The [2026-07-28 spec](https://modelcontextprotocol.io/specification/2026-07-28) defines two standard transports:

- **stdio**: the client launches your server as a subprocess and exchanges newline-delimited JSON over standard input and output. Good for local tools: a CLI, a database helper, something that runs on the developer's own machine.
- **Streamable HTTP**: each message is an HTTP POST to a single MCP endpoint, and the reply comes back as JSON or a short-lived event stream for that request. This is the one web developers care about, because it means an MCP server is just another route on your app.

## What changed in the 2026-07-28 MCP spec

If you read an MCP tutorial from 2025, parts of it are now out of date. The 2026-07-28 revision is the biggest change since launch, and it includes breaking changes. The [official release post](https://blog.modelcontextprotocol.io/posts/2026-07-28/) has the full list. The parts that matter most for web developers:

**The core is now stateless.** Earlier versions opened a session with an `initialize` handshake and tracked it with an `Mcp-Session-Id` header. Both are gone. Every request now carries its protocol version, client details and capabilities in its own `_meta` field, so any request can land on any server instance behind a load balancer. For PHP developers this is a welcome change: it fits the normal request and response model, and it is much friendlier to shared hosting and serverless platforms than long-lived sessions were.

**Discovery is a single call.** A new `server/discover` method returns the server's supported versions, capabilities and identity. Clients can call it up front or just send a request and handle a version error.

**Routing headers on HTTP.** Streamable HTTP requests now carry `Mcp-Method` and `Mcp-Name` headers, so a gateway or reverse proxy can route and rate-limit without parsing the JSON body.

**Servers no longer send requests to clients.** Features that relied on the server asking the client for something mid-call (elicitation, sampling, roots) now use a multi round-trip pattern: the server returns a result saying it needs input, and the client retries the original call with the answers.

**Deprecations.** Roots, Sampling and Logging are deprecated, with at least twelve months before removal under the new deprecation policy. The older HTTP+SSE transport is also deprecated.

**Extensions.** Tasks (for long-running work you poll) moved out of the core into an official extension, and the spec now has a formal extensions framework, with MCP Apps among the named extensions.

**Authorization tightened.** Clients must validate the `iss` parameter returned by the authorization server, credentials are bound to the issuer that created them, and Dynamic Client Registration is deprecated in favour of Client ID Metadata Documents.

| Area | 2025-11-25 and earlier | 2026-07-28 |
|---|---|---|
| Connection model | Session with `initialize` handshake | Stateless, metadata on every request |
| Session header | `Mcp-Session-Id` | Removed |
| Capability discovery | During initialization | `server/discover`, optional |
| Server asking client for input | Server-initiated requests | Multi round-trip: client retries with answers |
| Tasks | Experimental core feature | Official extension |
| Roots, Sampling, Logging | Core features | Deprecated |
| Client registration | Dynamic Client Registration common | Client ID Metadata Documents preferred |

Servers and clients can support more than one version, and the spec describes how new implementations fall back to the older handshake-based versions. In practice, check which protocol versions your SDK supports before you assume either behaviour.

## When to build an MCP server instead of a normal API

MCP does not replace your REST API. It sits beside it, aimed at a different caller.

Build an MCP server when:

- You want people to use your application from inside AI tools they already work in, such as asking an assistant "what's outstanding on invoice INV-1042" and getting a real answer.
- You are building internal AI tooling and want several AI clients to share the same set of tools.
- Your own team uses AI coding assistants and would benefit from them reading your project's documentation, database schema or logs in a controlled way.

Stick with a plain API when:

- The caller is your own front end or a mobile app.
- The integration is machine to machine with no model in the loop.
- You need tight contracts, versioning and performance guarantees that a model-facing interface does not add anything to.

A sensible pattern is a thin MCP layer that calls the same service classes your controllers already use. The business logic stays in one place. If you are new to how models choose and call tools, [What Are AI Agents? A Plain-English Guide for Businesses](/blog/ai-agents-explained) gives the background.

## Building an MCP server in Laravel

Laravel has a first-party package, [Laravel MCP](https://laravel.com/docs/13.x/mcp), and there is also an [official PHP SDK](https://php.sdk.modelcontextprotocol.io/) maintained with the PHP Foundation and Symfony for framework-agnostic projects. Here is the Laravel route.

Install it and publish the AI routes file:

```bash
composer require laravel/mcp
php artisan vendor:publish --tag=ai-routes
php artisan make:mcp-server OrdersServer
php artisan make:mcp-tool FindOrderTool
```

A tool is a class with a description, an input schema and a `handle` method:

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Looks up an order by its reference and returns status and balance.')]
#[IsReadOnly]
class FindOrderTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'reference' => 'required|string|max:30',
        ]);

        $order = Order::where('reference', $validated['reference'])
            ->where('customer_id', $request->user()->customer_id)
            ->first();

        if (! $order) {
            return Response::error('No order found with that reference.');
        }

        return Response::text("Order {$order->reference}: {$order->status}, balance {$order->currency} {$order->balance}.");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reference' => $schema->string()
                ->description('The order reference, for example INV-1042.')
                ->required(),
        ];
    }
}
```

Register the tool on the server class (`protected array $tools = [FindOrderTool::class];`), then expose the server in `routes/ai.php`:

```php
use App\Mcp\Servers\OrdersServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/orders', OrdersServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);

Mcp::local('orders', OrdersServer::class);
```

`Mcp::web` gives you a Streamable HTTP endpoint. `Mcp::local` registers a stdio server that an AI client on the same machine can start through Artisan. To poke at it while developing, run `php artisan mcp:inspector mcp/orders`, which launches the MCP Inspector against your server.

Two details in that tool matter more than they look. The query is scoped to the authenticated user's customer, so the model cannot read another customer's order by guessing a reference. And validation errors come back as a normal tool error, which lets the model correct itself rather than failing the whole call.

## Securing an MCP server

An MCP server is an API that a language model drives, and models can be manipulated by the text they read. Treat it with at least the care you give a public API. My checklist:

- [ ] **Authenticate every web server.** Laravel MCP supports Sanctum tokens for simple cases and Passport for OAuth, which is what most remote MCP clients expect.
- [ ] **Scope every query to the user.** Never trust an ID from the model to imply permission. Authorise in the tool, as you would in a controller policy.
- [ ] **Prefer read-only tools.** Mark them with annotations. Add write tools only when there is a clear need, and make destructive ones require confirmation.
- [ ] **Validate arguments strictly.** Lengths, formats, allowed values. The model will occasionally send nonsense.
- [ ] **Assume prompt injection.** Any text your tool returns (customer notes, emails, web pages) can contain instructions aimed at the model. Do not let a tool's output trigger another privileged action without a person approving it.
- [ ] **Rate-limit and log.** Throttle per token, and log every tool call with the user, arguments and result size. You will want that trail the first time something odd happens.
- [ ] **Return less.** Send the fields the model needs, not whole records with personal data it does not.

Most of this overlaps with [Laravel Security Checklist: 15 Things to Check Before Launch](/blog/laravel-security-checklist), which is worth running through before you expose any MCP endpoint publicly.

## What to build first with MCP

Start small and read-only:

1. Pick one question your team or customers ask repeatedly that your database can answer, such as order status, stock levels or appointment availability.
2. Build one read-only tool for it behind Sanctum, scoped to the user.
3. Connect it to the AI client you already use and test it with real questions, including deliberately awkward ones.
4. Log for a couple of weeks. Look at what the model asked for and what it got wrong.
5. Only then consider a second tool, or one that writes.

If you want an MCP server or another AI integration added to an existing Laravel or PHP application, that falls under my [integrations and automation work](/services/integrations-automation).

## Frequently asked questions

### What is the Model Context Protocol used for?

MCP is used to connect AI applications to external tools and data in a standard way. A company writes one MCP server that exposes its tools, data and prompt templates, and any compatible AI client can use them. Common uses include letting assistants query business systems, read internal documentation, work with developer tools such as databases and issue trackers, and take approved actions in other software.

### Is MCP the same as an API?

No. MCP is a protocol that usually sits on top of your existing API or services. A REST API is designed for programs written by developers. An MCP server describes capabilities in a way an AI model can discover and call, with descriptions and input schemas the model reads. Many teams keep their REST API and add a thin MCP layer that calls the same underlying code.

### What is the latest version of the MCP specification?

The current revision is 2026-07-28. It made the protocol stateless by removing the initialize handshake and session header, added a server/discover method, moved tasks into an official extension, deprecated roots, sampling and logging, and tightened authorization. The previous revision, 2025-11-25, is still common, so check which versions your SDK and clients support.

### Can I build an MCP server in PHP?

Yes. Laravel has a first-party package, laravel/mcp, which provides Artisan generators, tool and resource classes, and routes for HTTP and local servers, with Sanctum or Passport authentication. For other PHP projects there is an official PHP SDK developed with the PHP Foundation and the Symfony project. Both let you expose existing PHP application logic to AI clients.

### Is MCP secure?

MCP itself defines authorization based on OAuth, but security depends mostly on how you build the server. Authenticate every remote endpoint, authorise each tool call against the user, keep tools read-only where possible, validate arguments, rate-limit and log calls. Also assume prompt injection: content returned by tools can contain instructions aimed at the model, so require human approval for sensitive actions.
