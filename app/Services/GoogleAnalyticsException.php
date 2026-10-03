<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * A Google Analytics failure, with a message written for the admin screen.
 */
class GoogleAnalyticsException extends RuntimeException
{
    public static function fromResponse(Response $response, ?string $serviceAccount): static
    {
        $status = $response->json('error.status');
        $message = (string) $response->json('error.message', 'no details given');

        return new static(match (true) {
            $status === 'PERMISSION_DENIED' && preg_match('/has not been used|is disabled/i', $message) === 1 => "The Google Analytics Data API isn't enabled in the Google Cloud project that owns this service account. Enable it there, wait a minute, then try again.",
            $status === 'PERMISSION_DENIED' => "Google Analytics refused access. Add {$serviceAccount} to the property as a Viewer (Admin → Property access management) and check the Property ID.",
            in_array($status, ['INVALID_ARGUMENT', 'NOT_FOUND']) => "Google didn't accept that Property ID. It is the number under Admin → Property details, not the G- measurement ID. (Google said: {$message})",
            $status === 'RESOURCE_EXHAUSTED' => 'The property has used up its Analytics API quota for now. Try again in an hour.',
            default => "Google Analytics returned an error: {$message}",
        });
    }
}
