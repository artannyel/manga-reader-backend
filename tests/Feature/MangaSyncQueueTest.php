<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Mangas\Jobs\SyncMangaDetailsJob;
use App\Domains\Mangas\Models\Manga;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MangaSyncQueueTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test details endpoint queues a sync job if the manga is stale.
     */
    public function test_show_stale_manga_queues_sync_job(): void
    {
        Queue::fake();

        $mangaId = 'a01b0423-ae2d-49ab-b118-09193234b35e';
        Manga::create([
            'id' => $mangaId,
            'title' => 'Stale Manga',
            'status' => 'ongoing',
            'last_synced_at' => now()->subHours(25), // > 24 hours
            'last_viewed_at' => now()->subDays(1),
            'views_count' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/manga/{$mangaId}");

        $response->assertStatus(200)
            ->assertJsonPath('status_translated', 'Em andamento');

        Queue::assertPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) use ($mangaId) {
            return $job->mangaId === $mangaId;
        });
    }

    /**
     * Test details endpoint does NOT queue a sync job if the manga is fresh.
     */
    public function test_show_fresh_manga_does_not_queue_sync_job(): void
    {
        Queue::fake();

        $mangaId = 'b02b0423-ae2d-49ab-b118-09193234b35e';
        Manga::create([
            'id' => $mangaId,
            'title' => 'Fresh Manga',
            'status' => 'completed',
            'last_synced_at' => now()->subHours(5), // < 24 hours
            'last_viewed_at' => now()->subHours(1),
            'views_count' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/manga/{$mangaId}");

        $response->assertStatus(200)
            ->assertJsonPath('status_translated', 'Finalizado');

        Queue::assertNotPushed(SyncMangaDetailsJob::class);
    }

    /**
     * Test daily sync command fetches updated mangas and dispatches sync jobs.
     */
    public function test_daily_sync_command_dispatches_jobs(): void
    {
        Queue::fake();
        Http::fake([
            '*/manga*' => Http::response([
                'data' => [
                    ['id' => 'a1111111-1111-1111-1111-111111111111'],
                    ['id' => 'a2222222-2222-2222-2222-222222222222'],
                ]
            ], 200)
        ]);

        $this->artisan('manga:daily-sync')
            ->assertSuccessful()
            ->expectsOutput('Fetching recently updated mangas from MangaDex...')
            ->expectsOutput('Found 2 updated mangas. Dispatching sync jobs...')
            ->expectsOutput('All sync jobs have been dispatched to the queue.');

        Queue::assertPushed(SyncMangaDetailsJob::class, 2);
        Queue::assertPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'a1111111-1111-1111-1111-111111111111';
        });
        Queue::assertPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'a2222222-2222-2222-2222-222222222222';
        });
    }

    /**
     * Test daily sync command paginates correctly when limit exceeds chunk size.
     */
    public function test_daily_sync_command_paginates_correctly(): void
    {
        Queue::fake();
        Http::fake([
            '*/manga*' => Http::sequence()
                ->push([
                    'data' => array_map(fn($i) => ['id' => "manga-{$i}"], range(1, 100))
                ], 200)
                ->push([
                    'data' => array_map(fn($i) => ['id' => "manga-{$i}"], range(101, 150))
                ], 200)
        ]);

        $this->artisan('manga:daily-sync --limit=150')
            ->assertSuccessful()
            ->expectsOutput('Fetching recently updated mangas from MangaDex...')
            ->expectsOutput('Found 150 updated mangas. Dispatching sync jobs...')
            ->expectsOutput('All sync jobs have been dispatched to the queue.');

        Queue::assertPushed(SyncMangaDetailsJob::class, 150);
    }

    /**
     * Test hourly sync command dispatches sync jobs for recently viewed mangas.
     */
    public function test_hourly_sync_command_dispatches_jobs_for_recently_viewed(): void
    {
        Queue::fake();

        // Manga viewed 5 days ago (should sync)
        Manga::create([
            'id' => 'c03b0423-ae2d-49ab-b118-09193234b35e',
            'title' => 'Recent Manga 1',
            'last_viewed_at' => now()->subDays(5),
        ]);

        // Manga viewed 1 day ago (should sync)
        Manga::create([
            'id' => 'd04b0423-ae2d-49ab-b118-09193234b35e',
            'title' => 'Recent Manga 2',
            'last_viewed_at' => now()->subDays(1),
        ]);

        // Manga viewed 8 days ago (should NOT sync)
        Manga::create([
            'id' => 'e05b0423-ae2d-49ab-b118-09193234b35e',
            'title' => 'Old Manga',
            'last_viewed_at' => now()->subDays(8),
        ]);

        // Manga with no last_viewed_at (should NOT sync)
        Manga::create([
            'id' => 'f06b0423-ae2d-49ab-b118-09193234b35e',
            'title' => 'Never Viewed Manga',
            'last_viewed_at' => null,
        ]);

        $this->artisan('manga:hourly-sync')
            ->assertSuccessful()
            ->expectsOutput('Fetching local mangas viewed within the last 7 days...')
            ->expectsOutput('Found 2 mangas. Dispatching sync jobs...')
            ->expectsOutput('All sync jobs have been dispatched to the queue.');

        Queue::assertPushed(SyncMangaDetailsJob::class, 2);
        Queue::assertPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'c03b0423-ae2d-49ab-b118-09193234b35e';
        });
        Queue::assertPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'd04b0423-ae2d-49ab-b118-09193234b35e';
        });
        Queue::assertNotPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'e05b0423-ae2d-49ab-b118-09193234b35e';
        });
        Queue::assertNotPushed(SyncMangaDetailsJob::class, function (SyncMangaDetailsJob $job) {
            return $job->mangaId === 'f06b0423-ae2d-49ab-b118-09193234b35e';
        });
    }
}
