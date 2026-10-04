<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only access to config/portfolio.php, plus the few things derived from it.
 */
class Portfolio
{
    public function profile(): array
    {
        return config('portfolio.profile');
    }

    public function projects(): Collection
    {
        return collect(config('portfolio.projects'))->map(function (array $project) {
            $file = "img/work/{$project['slug']}.webp";
            $path = public_path($file);
            $size = is_file($path) ? @getimagesize($path) : false;

            // The cards load a narrower copy; see `php artisan portfolio:screenshots`.
            $thumbFile = "img/work/thumbs/{$project['slug']}.webp";
            $thumbPath = public_path($thumbFile);
            $thumbSize = $size && is_file($thumbPath) ? @getimagesize($thumbPath) : false;

            return $project + [
                'host' => Str::of(parse_url($project['url'], PHP_URL_HOST))->replaceStart('www.', '')->toString(),
                'image' => $size ? asset($file).'?v='.filemtime($path) : null,
                'image_width' => $size[0] ?? null,
                'image_height' => $size[1] ?? null,
                'thumb' => $thumbSize ? asset($thumbFile).'?v='.filemtime($thumbPath) : null,
                'thumb_width' => $thumbSize[0] ?? null,
                'case_url' => isset($project['case_study']) ? route('work.show', $project['slug']) : null,
            ];
        });
    }

    /**
     * The projects that have a 'case_study' block, and so a page at /work/{slug}.
     */
    public function caseStudies(): Collection
    {
        return $this->projects()->whereNotNull('case_url')->values();
    }

    /**
     * The services on offer, each with the address of its own page.
     */
    public function services(): Collection
    {
        return collect(config('portfolio.services'))->map(fn (array $service) => $service + [
            'url' => route('services.show', $service['slug']),
        ]);
    }

    public function projectGroups(): array
    {
        return config('portfolio.project_groups');
    }

    /**
     * Roles as they stand today: a role with a scheduled 'ended' block takes
     * on those values once that date arrives.
     */
    public function experience(): Collection
    {
        $today = now(config('portfolio.profile.timezone'))->toDateString();

        return collect(config('portfolio.experience'))->map(function (array $role) use ($today) {
            $ended = $role['ended'] ?? null;
            unset($role['ended']);

            return $ended && $today >= $ended['from'] ? array_merge($role, Arr::except($ended, 'from')) : $role;
        });
    }

    public function skills(): array
    {
        return config('portfolio.skills');
    }

    /**
     * Skill name => ids of the roles that list it in their stack.
     * Only skills used in at least one role are included.
     */
    public function skillIndex(): array
    {
        $index = [];

        foreach ($this->experience() as $role) {
            foreach ($role['stack'] as $skill) {
                $index[$skill][] = $role['id'];
            }
        }

        return $index;
    }

    /**
     * Geometry for the résumé's career chart: one bar per role on a shared
     * year axis, positioned as percentages so the chart is pure HTML/CSS.
     */
    public function careerChart(?Carbon $today = null): array
    {
        $today ??= now();
        $roles = $this->experience();

        $axisStart = Carbon::parse($roles->min('start').'-01')->startOfYear();
        $axisEnd = $today->copy()->addYear()->startOfYear();
        $span = $axisStart->diffInDays($axisEnd);

        $pct = fn (Carbon $date) => round($axisStart->diffInDays($date) / $span * 100, 2);

        return [
            'years' => collect(range($axisStart->year, $axisEnd->year))
                ->map(fn (int $year) => ['year' => $year, 'left' => $pct(Carbon::create($year))])
                ->all(),
            'today' => $pct($today),
            'bars' => $roles->map(function (array $role) use ($pct, $today) {
                $start = Carbon::parse($role['start'].'-01');
                $end = $role['end'] ? Carbon::parse($role['end'].'-01')->endOfMonth()->min($today) : $today;

                return [
                    'id' => $role['id'],
                    'role' => $role['role'],
                    'org' => $role['org'],
                    'period' => $role['period'],
                    'type' => $role['type'],
                    'current' => $role['end'] === null,
                    'left' => $pct($start),
                    'width' => round($pct($end) - $pct($start), 2),
                ];
            })->all(),
        ];
    }

    /**
     * Structured data (schema.org) describing the person behind the site, so
     * search engines can connect the name, the job, the location and the profiles.
     */
    public function schema(string $pageTitle): array
    {
        $profile = $this->profile();
        $person = url('/').'#person';
        $site = url('/').'#website';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Person',
                    '@id' => $person,
                    'name' => $profile['name'],
                    'jobTitle' => $profile['title'],
                    'description' => $profile['summary'],
                    'url' => url('/'),
                    'image' => asset('og-cover.png'),
                    'email' => $profile['email'],
                    'telephone' => $profile['phone_e164'],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $profile['city'],
                        'addressCountry' => $profile['country_code'],
                    ],
                    'sameAs' => collect($profile['links'])->pluck('url')->all(),
                    'knowsAbout' => collect($this->skills())->flatten()->all(),
                    'knowsLanguage' => 'en',
                    'alumniOf' => collect(config('portfolio.education'))
                        ->map(fn ($item) => ['@type' => 'CollegeOrUniversity', 'name' => $item['school']])->all(),
                    'hasOccupation' => [
                        '@type' => 'Occupation',
                        'name' => 'Web Developer',
                        'occupationLocation' => ['@type' => 'Country', 'name' => 'Trinidad and Tobago'],
                        'skills' => collect($this->skills())->flatten()->implode(', '),
                    ],
                    'worksFor' => $this->experience()->whereNull('end')
                        ->map(fn ($role) => ['@type' => 'Organization', 'name' => $role['org']])->values()->all(),
                ],
                $this->businessSchema() + [
                    'hasOfferCatalog' => [
                        '@type' => 'OfferCatalog',
                        'name' => 'Web development services',
                        'itemListElement' => $this->services()->map(fn (array $service) => [
                            '@type' => 'Offer',
                            'itemOffered' => [
                                '@type' => 'Service',
                                'name' => $service['title'],
                                'description' => $service['text'],
                                'url' => $service['url'],
                            ],
                        ])->all(),
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $site,
                    'url' => url('/'),
                    'name' => $profile['name'],
                    'author' => ['@id' => $person],
                ],
                [
                    '@type' => 'ProfilePage',
                    '@id' => url()->current().'#page',
                    'url' => url()->current(),
                    'name' => $pageTitle,
                    'isPartOf' => ['@id' => $site],
                    'mainEntity' => ['@id' => $person],
                    'dateModified' => Carbon::createFromTimestamp(filemtime(config_path('portfolio.php')))->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * The business side of the site, for local search: who offers the
     * services, where they are based and which countries they serve.
     */
    private function businessSchema(): array
    {
        $profile = $this->profile();

        return [
            '@type' => 'ProfessionalService',
            '@id' => url('/').'#business',
            'name' => "{$profile['name']}, Web Developer",
            'description' => $profile['seo']['home_description'],
            'url' => url('/'),
            'image' => asset('og-cover.png'),
            'email' => $profile['email'],
            'telephone' => $profile['phone_e164'],
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $profile['city'],
                'addressCountry' => $profile['country_code'],
            ],
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Trinidad and Tobago'],
                ['@type' => 'Place', 'name' => 'Caribbean'],
            ],
            'founder' => ['@id' => url('/').'#person'],
            'sameAs' => collect($profile['links'])->pluck('url')->all(),
        ];
    }

    /**
     * Structured data for one service page: the service, who provides it
     * and where the page sits in the site.
     */
    public function serviceSchema(array $service): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Service',
                    '@id' => $service['url'].'#service',
                    'name' => $service['page']['heading'],
                    'serviceType' => $service['title'],
                    'description' => $service['page']['summary'],
                    'url' => $service['url'],
                    'provider' => $this->businessSchema(),
                    'areaServed' => ['@type' => 'Country', 'name' => 'Trinidad and Tobago'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => url('/').'#services'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $service['title'], 'item' => $service['url']],
                    ],
                ],
            ],
        ];
    }

    /**
     * The résumé in the open JSON Resume format (jsonresume.org).
     */
    public function toJsonResume(): array
    {
        $profile = $this->profile();

        return [
            '$schema' => 'https://raw.githubusercontent.com/jsonresume/resume-schema/v1.0.0/schema.json',
            'basics' => [
                'name' => $profile['name'],
                'label' => $profile['title'],
                'email' => $profile['email'],
                'phone' => $profile['phone'],
                'url' => url('/'),
                'summary' => $profile['summary'],
                'location' => ['city' => $profile['city'], 'countryCode' => $profile['country_code']],
                'profiles' => collect($profile['links'])->map(fn ($link) => [
                    'network' => $link['label'],
                    'url' => $link['url'],
                ])->values()->all(),
            ],
            'work' => $this->experience()->map(fn ($role) => array_filter([
                'name' => $role['org'],
                'position' => $role['role'],
                'description' => $role['org_note'],
                'startDate' => $role['start'],
                'endDate' => $role['end'],
                'summary' => $role['summary'],
                'highlights' => $role['bullets'],
            ]))->all(),
            'education' => collect(config('portfolio.education'))->map(fn ($item) => [
                'institution' => $item['school'],
                'studyType' => $item['award'],
                'score' => $item['status'],
            ])->all(),
            'certificates' => collect(config('portfolio.certifications'))->map(fn ($cert) => array_filter([
                'name' => $cert['name'],
                'issuer' => $cert['issuer'],
                'date' => $cert['year'],
            ]))->all(),
            'skills' => collect($this->skills())->map(fn ($keywords, $name) => [
                'name' => $name,
                'keywords' => $keywords,
            ])->values()->all(),
            'projects' => $this->projects()->map(fn ($project) => [
                'name' => $project['name'],
                'description' => $project['summary'],
                'url' => $project['url'],
                'keywords' => $project['stack'],
            ])->all(),
            'meta' => ['canonical' => route('resume.json'), 'lastModified' => now()->toIso8601String()],
        ];
    }

    public function toVCard(): string
    {
        $profile = $this->profile();
        [$first, $last] = explode(' ', $profile['name'], 2) + [1 => ''];

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "N:{$last};{$first};;;",
            "FN:{$profile['name']}",
            "TITLE:{$profile['title']}",
            "EMAIL;TYPE=INTERNET,PREF:{$profile['email']}",
            "TEL;TYPE=CELL:{$profile['phone_e164']}",
            "ADR;TYPE=WORK:;;;{$profile['city']};;;Trinidad and Tobago",
            'URL:'.url('/'),
        ];

        foreach ($profile['links'] as $link) {
            $lines[] = "URL:{$link['url']}";
        }

        $lines[] = 'REV:'.now()->utc()->format('Ymd\THis\Z');
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines)."\r\n";
    }
}
