<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

#[Signature('portfolio:screenshots {slug? : Only refresh this project} {--force : Re-capture even if a screenshot exists}')]
#[Description('Capture full-page screenshots of the portfolio sites into public/img/work (needs Node and Google Chrome)')]
class CaptureScreenshots extends Command
{
    /** Width the stored image is scaled to. */
    private const WIDTH = 900;

    /** Width of the smaller copy the project cards load; the full image is kept for the preview and case study. */
    private const THUMB_WIDTH = 400;

    public function handle(): int
    {
        $dir = public_path('img/work');
        File::ensureDirectoryExists($dir);

        $projects = collect(config('portfolio.projects'))
            ->when($this->argument('slug'), fn ($all, $slug) => $all->where('slug', $slug));

        if ($projects->isEmpty()) {
            $this->error('No matching project.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($projects as $project) {
            $target = "{$dir}/{$project['slug']}.webp";

            if (File::exists($target) && ! $this->option('force')) {
                $this->line("  skip  {$project['slug']} (exists, use --force to refresh)");
                $this->saveThumbnail($target, replace: false);

                continue;
            }

            $png = tempnam(sys_get_temp_dir(), 'shot').'.png';
            $capture = Process::path(base_path())->timeout(180)
                ->run(['node', 'scripts/screenshot.mjs', $project['url'], $png]);

            $image = $capture->successful() && is_file($png) ? @imagecreatefrompng($png) : false;
            @unlink($png);

            if (! $image) {
                $this->warn("  fail  {$project['slug']}: ".trim($capture->errorOutput()));
                $failed++;

                continue;
            }

            $scaled = imagescale($image, self::WIDTH);
            imagewebp($scaled, $target, 76);
            $this->saveThumbnail($target, replace: true);

            $this->info(sprintf('  saved %s (%dx%d, %d KB)', $project['slug'], imagesx($scaled), imagesy($scaled), filesize($target) / 1024));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Write thumbs/{slug}.webp beside a screenshot, scaled down from it.
     */
    private function saveThumbnail(string $screenshot, bool $replace): void
    {
        $thumb = dirname($screenshot).'/thumbs/'.basename($screenshot);

        if (File::exists($thumb) && ! $replace) {
            return;
        }

        File::ensureDirectoryExists(dirname($thumb));
        imagewebp(imagescale(imagecreatefromwebp($screenshot), self::THUMB_WIDTH), $thumb, 62);

        $this->line(sprintf('  thumb %s (%d KB)', basename($thumb, '.webp'), filesize($thumb) / 1024));
    }
}
