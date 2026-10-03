<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    // SQLite can't take a snapshot inside the transaction RefreshDatabase wraps each test in.
    use DatabaseMigrations;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = sys_get_temp_dir().'/portfolio-backup-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    public function test_the_backup_is_a_working_copy_of_the_database(): void
    {
        ContactMessage::factory()->create(['email' => 'ada@example.com']);
        $this->travelTo('2026-10-03 09:30:00');

        $this->artisan('portfolio:backup', ['--to' => $this->folder])->assertSuccessful();

        $copy = new PDO("sqlite:{$this->folder}/database-2026-10-03-093000.sqlite");
        $this->assertSame('ada@example.com', $copy->query('select email from contact_messages')->fetchColumn());
    }

    public function test_only_the_newest_copies_are_kept(): void
    {
        File::ensureDirectoryExists($this->folder);
        foreach (['2026-09-29', '2026-09-30', '2026-10-01'] as $day) {
            File::put("{$this->folder}/database-{$day}-020000.sqlite", 'old');
        }
        File::put("{$this->folder}/notes.txt", 'not a backup');
        $this->travelTo('2026-10-03 02:00:00');

        $this->artisan('portfolio:backup', ['--to' => $this->folder, '--keep' => 2])->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['database-2026-10-01-020000.sqlite', 'database-2026-10-03-020000.sqlite', 'notes.txt'],
            array_map('basename', File::files($this->folder)),
        );
    }

    public function test_a_database_that_is_not_sqlite_is_refused(): void
    {
        config(['database.default' => 'mysql']);

        $this->artisan('portfolio:backup', ['--to' => $this->folder])->assertFailed();
        config(['database.default' => 'sqlite']);

        $this->assertDirectoryDoesNotExist($this->folder);
    }
}
