<?php

declare(strict_types=1);

namespace App\Domains\Chapters\Actions;

use App\Domains\Chapters\Models\Chapter;
use App\Domains\Chapters\Repositories\ChapterRepository;
use App\Domains\Mangas\Services\MangaDexService;

class SyncChapterPagesAction
{
    public function __construct(
        protected MangaDexService $mangaDexService,
        protected ChapterRepository $chapterRepository
    ) {}

    /**
     * Synchronize pages and hash details for a chapter.
     *
     * @param Chapter $chapter
     * @return Chapter
     */
    public function execute(Chapter $chapter): Chapter
    {
        $pagesInfo = $this->mangaDexService->fetchChapterPages($chapter->id);

        $this->chapterRepository->update($chapter, [
            'hash' => $pagesInfo['hash'],
            'pages' => $pagesInfo['pages'],
            'pages_saver' => $pagesInfo['pages_saver'],
            'last_synced_at' => now(),
        ]);

        return $chapter->fresh();
    }
}
