<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Mangas\Models\Manga;
use App\Domains\Chapters\Models\Chapter;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MangaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test feed endpoint requires authentication.
     */
    public function test_feed_requires_authentication(): void
    {
        $response = $this->getJson('/api/manga');
        $response->assertStatus(401);
    }

    /**
     * Test feed returns mangas ordered by the latest chapter's created/updated date.
     */
    public function test_feed_returns_mangas_ordered_by_latest_chapter(): void
    {
        $mangaAId = '11111111-1111-1111-1111-111111111111';
        $mangaBId = '22222222-2222-2222-2222-222222222222';

        // Use DB insert to avoid Eloquent overriding timestamps
        DB::table('mangas')->insert([
            [
                'id' => $mangaAId,
                'title' => 'Manga A',
                'status' => 'ongoing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $mangaBId,
                'title' => 'Manga B',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('chapters')->insert([
            [
                'id' => '33333333-3333-3333-3333-333333333333',
                'manga_id' => $mangaBId,
                'chapter_number' => '1',
                'language' => 'en',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'id' => '44444444-4444-4444-4444-444444444444',
                'manga_id' => $mangaAId,
                'chapter_number' => '1',
                'language' => 'en',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'id' => '55555555-5555-5555-5555-555555555555',
                'manga_id' => $mangaBId,
                'chapter_number' => '2',
                'language' => 'en',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/manga');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'cover_filename',
                        'cover_url',
                        'latest_chapter_date',
                        'last_viewed_at',
                        'views_count',
                    ]
                ],
                'meta' => ['limit', 'offset', 'total']
            ]);

        // First item in feed should be Manga B (most recent chapter is now)
        $this->assertEquals($mangaBId, $response->json('data.0.id'));
        $this->assertEquals($mangaAId, $response->json('data.1.id'));
    }

    /**
     * Test local manga search endpoint.
     */
    public function test_manga_search_returns_filtered_results(): void
    {
        $manga1Id = '66666666-6666-6666-6666-666666666666';
        $manga2Id = '77777777-7777-7777-7777-777777777777';

        Manga::create([
            'id' => $manga1Id,
            'title' => 'One Piece',
        ]);

        Manga::create([
            'id' => $manga2Id,
            'title' => 'Naruto',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/manga/search?title=Piece');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'One Piece');
    }

    /**
     * Test details endpoint fetches and syncs manga details if missing from DB.
     */
    public function test_show_manga_details_syncs_from_mangadex_if_missing(): void
    {
        $mangaId = '391b0423-ae2d-49ab-b118-09193234b35e';
        $chapterId = '88888888-8888-8888-8888-888888888888';

        Http::fake([
            "*/manga/{$mangaId}/feed*" => Http::response([
                'data' => [
                    [
                        'id' => $chapterId,
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'Chapter 1 Title',
                            'volume' => '1',
                            'translatedLanguage' => 'en',
                        ]
                    ]
                ]
            ], 200),
            "*/manga/{$mangaId}*" => Http::response([
                'data' => [
                    'id' => $mangaId,
                    'type' => 'manga',
                    'attributes' => [
                        'title' => ['en' => 'Manga Title Name'],
                        'description' => ['en' => 'Manga description'],
                        'status' => 'ongoing',
                    ],
                    'relationships' => [
                        [
                            'type' => 'cover_art',
                            'attributes' => ['fileName' => 'cover.jpg']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/manga/{$mangaId}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $mangaId)
            ->assertJsonPath('title', 'Manga Title Name')
            ->assertJsonPath('cover_filename', 'cover.jpg')
            ->assertJsonCount(1, 'chapters');

        $this->assertDatabaseHas('mangas', ['id' => $mangaId, 'views_count' => 1]);
        $this->assertDatabaseHas('chapters', ['id' => $chapterId, 'manga_id' => $mangaId]);
    }

    /**
     * Test details endpoint increments views and last_viewed_at when manga already exists.
     */
    public function test_show_manga_details_increments_views(): void
    {
        $mangaId = '66666666-6666-6666-6666-666666666666';

        Manga::create([
            'id' => $mangaId,
            'title' => 'Manga Title',
            'views_count' => 5,
            'last_synced_at' => now(), // Fresh (less than 24h)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/manga/{$mangaId}");

        $response->assertStatus(200)
            ->assertJsonPath('views_count', 6);

        $this->assertDatabaseHas('mangas', [
            'id' => $mangaId,
            'views_count' => 6,
        ]);
    }

    /**
     * Test fetching chapter pages syncs filenames if pages are not synced yet.
     */
    public function test_show_chapter_pages_syncs_if_missing(): void
    {
        $mangaId = '66666666-6666-6666-6666-666666666666';
        $chapterId = '88888888-8888-8888-8888-888888888888';

        Manga::create([
            'id' => $mangaId,
            'title' => 'Manga Title',
        ]);

        Chapter::create([
            'id' => $chapterId,
            'manga_id' => $mangaId,
            'chapter_number' => '1',
        ]);

        Http::fake([
            "*/at-home/server/{$chapterId}*" => Http::response([
                'baseUrl' => 'https://uploads.mangadex.org',
                'chapter' => [
                    'hash' => 'chapterhash123',
                    'data' => ['page1.png', 'page2.png'],
                    'dataSaver' => ['page1_thumb.png', 'page2_thumb.png'],
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chapters/{$chapterId}/pages?quality=data");

        $response->assertStatus(200)
            ->assertJsonPath('chapter_id', $chapterId)
            ->assertJsonPath('quality', 'data')
            ->assertJsonPath('hash', 'chapterhash123')
            ->assertJsonCount(2, 'pages')
            ->assertJsonPath('pages.0', 'https://uploads.mangadex.org/data/chapterhash123/page1.png');

        $this->assertDatabaseHas('chapters', [
            'id' => $chapterId,
            'hash' => 'chapterhash123',
        ]);
    }

    /**
     * Test fetching chapter pages if chapter is missing from the database.
     */
    public function test_show_chapter_pages_when_chapter_missing_from_db(): void
    {
        $mangaId = '391b0423-ae2d-49ab-b118-09193234b35e';
        $chapterId = '88888888-8888-8888-8888-888888888888';

        // Mock 1. GET /chapter/{id} to find parent manga
        // Mock 2. GET /manga/{id} to import manga details
        // Mock 3. GET /manga/{id}/feed to import manga chapters
        // Mock 4. GET /at-home/server/{id} to fetch pages
        Http::fake([
            "*/chapter/{$chapterId}*" => Http::response([
                'data' => [
                    'id' => $chapterId,
                    'relationships' => [
                        ['type' => 'manga', 'id' => $mangaId]
                    ]
                ]
            ], 200),
            "*/manga/{$mangaId}/feed*" => Http::response([
                'data' => [
                    [
                        'id' => $chapterId,
                        'type' => 'chapter',
                        'attributes' => [
                            'chapter' => '1',
                            'title' => 'Chapter 1 Title',
                            'volume' => '1',
                            'translatedLanguage' => 'en',
                        ]
                    ]
                ]
            ], 200),
            "*/manga/{$mangaId}*" => Http::response([
                'data' => [
                    'id' => $mangaId,
                    'type' => 'manga',
                    'attributes' => [
                        'title' => ['en' => 'Manga Title Name'],
                        'status' => 'ongoing',
                    ],
                ]
            ], 200),
            "*/at-home/server/{$chapterId}*" => Http::response([
                'baseUrl' => 'https://uploads.mangadex.org',
                'chapter' => [
                    'hash' => 'chapterhash123',
                    'data' => ['page1.png', 'page2.png'],
                    'dataSaver' => ['page1_thumb.png', 'page2_thumb.png'],
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chapters/{$chapterId}/pages?quality=data-saver");

        $response->assertStatus(200)
            ->assertJsonPath('chapter_id', $chapterId)
            ->assertJsonPath('quality', 'data-saver')
            ->assertJsonPath('hash', 'chapterhash123')
            ->assertJsonCount(2, 'pages')
            ->assertJsonPath('pages.0', 'https://uploads.mangadex.org/data-saver/chapterhash123/page1_thumb.png');

        $this->assertDatabaseHas('mangas', ['id' => $mangaId]);
        $this->assertDatabaseHas('chapters', ['id' => $chapterId, 'hash' => 'chapterhash123']);
    }
}
