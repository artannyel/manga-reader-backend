<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Actions;

use App\Domains\Chapters\Repositories\ChapterRepository;
use App\Domains\Mangas\Models\Manga;
use App\Domains\Mangas\Repositories\MangaRepository;
use App\Domains\Mangas\Services\MangaDexService;
use Illuminate\Support\Facades\DB;

class SyncMangaAction
{
    public function __construct(
        protected MangaDexService $mangaDexService,
        protected MangaRepository $mangaRepository,
        protected ChapterRepository $chapterRepository
    ) {}

    /**
     * Synchronize manga details and chapter list.
     *
     * @param string $mangaId
     * @return Manga
     */
    public function execute(string $mangaId): Manga
    {
        // 1. Fetch details from MangaDex
        $details = $this->mangaDexService->fetchDetails($mangaId);

        // 2. Fetch chapters list from MangaDex
        $chaptersData = $this->mangaDexService->fetchChapters($mangaId);

        // 3. Save to database within a transaction
        return DB::transaction(function () use ($mangaId, $details, $chaptersData) {
            $manga = $this->mangaRepository->updateOrCreate(
                ['id' => $mangaId],
                [
                    'title' => $details['title'],
                    'description' => $details['description'],
                    'status' => $details['status'],
                    'cover_filename' => $details['cover_filename'],
                    'last_synced_at' => now(),
                ]
            );

            $chapters = array_map(function ($chapter) use ($mangaId) {
                return [
                    'id' => $chapter['id'],
                    'manga_id' => $mangaId,
                    'title' => $chapter['title'],
                    'chapter_number' => $chapter['chapter_number'],
                    'volume_number' => $chapter['volume_number'],
                    'language' => $chapter['language'],
                    'pages_count' => $chapter['pages_count'],
                    'hash' => null,
                    'pages' => null,
                    'pages_saver' => null,
                    'last_synced_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $chaptersData);

            $uniqueChapters = [];
            foreach ($chapters as $chapter) {
                $key = $chapter['chapter_number'] . '-' . $chapter['language'];
                if (!isset($uniqueChapters[$key])) {
                    $uniqueChapters[$key] = $chapter;
                }
            }
            $chapters = array_values($uniqueChapters);

            $this->chapterRepository->upsertChapters($chapters);

            // Reload manga with sorted chapters
            return $manga->load(['chapters' => function ($query) {
                $query->orderBy('chapter_number', 'asc');
            }]);
        });
    }
}
