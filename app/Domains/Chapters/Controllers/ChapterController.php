<?php

declare(strict_types=1);

namespace App\Domains\Chapters\Controllers;

use App\Domains\Chapters\Actions\SyncChapterPagesAction;
use App\Domains\Chapters\Repositories\ChapterRepository;
use App\Domains\Chapters\Requests\ChapterPagesRequest;
use App\Domains\Mangas\Actions\SyncMangaAction;
use App\Domains\Mangas\Services\MangaDexService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChapterController extends Controller
{
    public function __construct(
        protected ChapterRepository $chapterRepository,
        protected SyncChapterPagesAction $syncChapterPagesAction,
        protected SyncMangaAction $syncMangaAction,
        protected MangaDexService $mangaDexService
    ) {}

    /**
     * Get pages for a chapter.
     *
     * @param string $id
     * @param ChapterPagesRequest $request
     * @return JsonResponse
     */
    public function showPages(string $id, ChapterPagesRequest $request): JsonResponse
    {
        $quality = $request->input('quality', 'data');

        $chapter = $this->chapterRepository->find($id);

        if (!$chapter) {
            try {
                // Fetch the parent manga ID for the chapter
                $mangaId = $this->mangaDexService->fetchMangaIdFromChapter($id);

                // Sync the entire manga (which imports the manga and all its chapters)
                $this->syncMangaAction->execute($mangaId);

                // Find the chapter again
                $chapter = $this->chapterRepository->find($id);

                if (!$chapter) {
                    return response()->json(['message' => 'Capítulo não encontrado'], 404);
                }
            } catch (\Throwable $e) {
                Log::error('Erro ao buscar/sincronizar capítulo não cadastrado no banco local.', [
                    'chapter_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json(['message' => 'Capítulo não encontrado'], 404);
            }
        }

        // Check if pages are missing or stale (older than 24 hours)
        if (
            empty($chapter->hash) ||
            empty($chapter->pages) ||
            !$chapter->last_synced_at ||
            $chapter->last_synced_at->diffInSeconds(now()) > 86400
        ) {
            try {
                $chapter = $this->syncChapterPagesAction->execute($chapter);
            } catch (\Throwable $e) {
                Log::error('Erro ao sincronizar páginas do capítulo com a API externa.', [
                    'chapter_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                if (empty($chapter->hash) || empty($chapter->pages)) {
                    return response()->json(['message' => 'Falha ao recuperar as páginas do capítulo'], 500);
                }
            }
        }

        $uploadsUrl = rtrim(config('services.mangadex.uploads_url', 'https://uploads.mangadex.org'), '/');
        $hash = $chapter->hash;

        $filenames = ($quality === 'data-saver') ? ($chapter->pages_saver ?? []) : ($chapter->pages ?? []);

        $pagesUrls = array_map(function ($filename) use ($uploadsUrl, $quality, $hash) {
            return "{$uploadsUrl}/{$quality}/{$hash}/{$filename}";
        }, $filenames);

        return response()->json([
            'chapter_id' => $chapter->id,
            'quality' => $quality,
            'hash' => $hash,
            'pages' => $pagesUrls,
        ]);
    }
}
