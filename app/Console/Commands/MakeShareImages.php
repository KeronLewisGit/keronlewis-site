<?php

namespace App\Console\Commands;

use App\Support\Portfolio;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

#[Signature('portfolio:share-images')]
#[Description('Draw the image shown when a page is shared: one for the site, one per service and one per case study (needs Node and Google Chrome)')]
class MakeShareImages extends Command
{
    public function handle(Portfolio $portfolio): int
    {
        $profile = $portfolio->profile();
        File::ensureDirectoryExists(public_path('img/share'));

        // Output file => what the card says.
        $cards = [
            public_path('og-cover.png') => [
                'eyebrow' => 'Web development · Trinidad & Tobago',
                'title' => $profile['name'],
                'subtitle' => 'Websites, WordPress stores and web apps',
            ],
        ];

        foreach ($portfolio->services() as $service) {
            $cards[public_path("img/share/service-{$service['slug']}.png")] = [
                'eyebrow' => "{$profile['name']} · Services",
                'title' => $service['title'],
                'subtitle' => 'For businesses in Trinidad & Tobago and the Caribbean',
            ];
        }

        foreach ($portfolio->caseStudies() as $project) {
            $cards[public_path("img/share/work-{$project['slug']}.png")] = [
                'eyebrow' => "{$profile['name']} · Case study",
                'title' => $project['case_study']['title'],
                'subtitle' => $project['name'],
            ];
        }

        $failed = 0;

        foreach ($cards as $target => $card) {
            $html = tempnam(sys_get_temp_dir(), 'share').'.html';
            File::put($html, view('share.card', $card + ['profile' => $profile, 'fonts' => base_path('node_modules/@fontsource-variable')])->render());

            $result = Process::path(base_path())->timeout(60)->run(['node', 'scripts/share-image.mjs', $html, $target]);
            File::delete($html);

            if ($result->failed()) {
                $this->warn('  fail  '.basename($target).': '.trim($result->errorOutput()));
                $failed++;

                continue;
            }

            $this->info(sprintf('  saved %s (%d KB)', basename($target), filesize($target) / 1024));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
