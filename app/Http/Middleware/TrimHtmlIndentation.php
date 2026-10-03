<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HtmlResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blade templates are indented for people to read, and on the home page that
 * indentation is about a third of the document. Browsers ignore it, so the
 * public pages are sent without it.
 */
class TrimHtmlIndentation
{
    /**
     * Either a block whose whitespace must be left alone, or a line break with the blank space around it.
     */
    private const PATTERN = '#<(pre|textarea|script|style)\b.*?</\1>|[ \t]*\R\s*#is';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // The admin shows visitors' messages exactly as they were typed.
        if (! $response instanceof HtmlResponse
            || $request->is('admin', 'admin/*')
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $trimmed = preg_replace_callback(
            self::PATTERN,
            fn (array $match): string => $match[0][0] === '<' ? $match[0] : "\n",
            (string) $response->getContent(),
        );

        return $trimmed === null ? $response : $response->setContent($trimmed);
    }
}
