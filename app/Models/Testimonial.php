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
 *
 * The name and project shown beside the words are the ones entered in the
 * admin area ('sent_to' and 'project'), not anything the client types.
 */
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = ['token', 'sent_to', 'project_slug', 'project', 'name', 'role', 'quote', 'highlights', 'submitted_at', 'approved_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'approved_at' => 'datetime', 'highlights' => 'array'];
    }

    public static function invite(string $sentTo, ?string $project = null): static
    {
        return static::create(['token' => Str::random(40), 'sent_to' => $sentTo] + static::projectFields($project));
    }

    /**
     * Turn what was typed as the project into the columns to store. A name
     * that matches a project in the portfolio also ties the testimonial to
     * that project's card and case study.
     *
     * @return array{project: ?string, project_slug: ?string}
     */
    public static function projectFields(?string $project): array
    {
        $project = trim((string) $project);
        $match = collect(config('portfolio.projects'))->first(fn (array $known) => Str::lower($known['name']) === Str::lower($project));

        return [
            'project' => $match['name'] ?? ($project ?: null),
            'project_slug' => $match['slug'] ?? null,
        ];
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

    /**
     * The project or company named beside the testimonial.
     */
    public function projectName(): ?string
    {
        return $this->project ?: (collect(config('portfolio.projects'))->firstWhere('slug', $this->project_slug)['name'] ?? null);
    }

    /**
     * What the public pages need to show this testimonial.
     *
     * @return array{id: int, quote: string, name: string, detail: ?string, highlights: list<string>, project_slug: ?string}
     */
    public function forDisplay(): array
    {
        return [
            'id' => $this->id,
            'quote' => $this->quote,
            'name' => $this->sent_to,
            // Older testimonials may only have the role their writer typed.
            'detail' => $this->projectName() ?: $this->role,
            'highlights' => $this->highlights ?? [],
            'project_slug' => $this->project_slug,
        ];
    }
}
