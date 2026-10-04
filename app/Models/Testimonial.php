<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A client's words about a project. It starts as a private link sent to the
 * client, becomes a submission once they fill it in, and is shown on the site
 * only after the admin approves it.
 */
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = ['token', 'sent_to', 'project_slug', 'name', 'role', 'quote', 'submitted_at', 'approved_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public static function invite(string $sentTo, ?string $projectSlug = null): static
    {
        return static::create(['token' => Str::random(40), 'sent_to' => $sentTo, 'project_slug' => $projectSlug]);
    }

    /** Links that have been sent out but not filled in yet. */
    public function scopeUnanswered(Builder $query): void
    {
        $query->whereNull('submitted_at');
    }

    /** Filled in by the client and waiting for a decision. */
    public function scopePending(Builder $query): void
    {
        $query->whereNotNull('submitted_at')->whereNull('approved_at');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->whereNotNull('submitted_at')->whereNotNull('approved_at');
    }

    public function link(): string
    {
        return route('testimonials.create', $this->token);
    }

    public function projectName(): ?string
    {
        return collect(config('portfolio.projects'))->firstWhere('slug', $this->project_slug)['name'] ?? null;
    }
}
