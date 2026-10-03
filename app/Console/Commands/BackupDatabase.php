<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

#[Signature('portfolio:backup {--to= : Folder for the copies (default: storage/app/backups)} {--keep=14 : How many copies to keep}')]
#[Description('Save a dated copy of the SQLite database and delete the oldest copies')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            $this->error('This command only backs up SQLite databases.');

            return self::FAILURE;
        }

        $folder = rtrim($this->option('to') ?: storage_path('app/backups'), '/');
        $file = $folder.'/database-'.now()->format('Y-m-d-His').'.sqlite';
        File::ensureDirectoryExists($folder, 0700);

        // Unlike copying the file, VACUUM INTO gives a complete snapshot even if the site is writing at that moment.
        $connection->statement('VACUUM INTO '.$connection->getPdo()->quote($file));
        File::chmod($file, 0600);

        $expired = collect(File::glob($folder.'/database-*.sqlite'))->sortDesc()->slice(max(1, (int) $this->option('keep')));
        File::delete($expired->all());

        $this->info("Saved {$file}".($expired->isEmpty() ? '' : " and removed {$expired->count()} older ".($expired->count() === 1 ? 'copy' : 'copies')).'.');

        return self::SUCCESS;
    }
}
