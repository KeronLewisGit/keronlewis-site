<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('portfolio:inbox {--limit=20 : How many recent messages to show}')]
#[Description('List recent contact-form messages saved in the database')]
class ListMessages extends Command
{
    public function handle(): int
    {
        $messages = ContactMessage::latest()->limit((int) $this->option('limit'))->get();

        if ($messages->isEmpty()) {
            $this->info('No messages yet.');

            return self::SUCCESS;
        }

        $this->table(['When', 'From', 'About', 'Emailed', 'Message'], $messages->map(fn (ContactMessage $m) => [
            $m->created_at->timezone(config('portfolio.profile.timezone'))->format('j M Y H:i'),
            "{$m->name} <{$m->email}>",
            $m->topicLabel(),
            $m->emailed_at ? 'yes' : 'NO',
            Str::limit(preg_replace('/\s+/', ' ', $m->message), 70),
        ]));

        return self::SUCCESS;
    }
}
