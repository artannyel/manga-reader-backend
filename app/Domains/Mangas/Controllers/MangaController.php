<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Controllers;

use App\Domains\Mangas\Actions\SyncMangaAction;
use App\Domains\Mangas\DTOs\MangaDetailsDto;
use App\Domains\Mangas\DTOs\MangaDto;
use App\Domains\Mangas\Jobs\SyncMangaDetailsJob;
use App\Domains\Mangas\Repositories\MangaRepository;
use App\Domains\Mangas\Requests\MangaIndexRequest;
use App\Domains\Mangas\Requests\MangaSearchRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MangaController extends Controller
{
    public function __construct(
        protected MangaRepository $mangaRepository,
        protected SyncMangaAction $syncMangaAction
    ) {}

    /**
     * Get local manga feed, ordered by latest chapter's created/updated date.
     *
     * @param MangaIndexRequest $request
     * @return JsonResponse
     */
    public function index(MangaIndexRequest $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);

        $mangas = $this->mangaRepository->getFeed($limit, $offset);
        $total = $this->mangaRepository->countFeed();

        $data = $mangas->map(function ($manga) {
            return MangaDto::fromModel($manga)->toArray();
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Search local mangas by title.
     *
     * @param MangaSearchRequest $request
     * @return JsonResponse
     */
    public function search(MangaSearchRequest $request): JsonResponse
    {
        $title = $request->input('title');
        $limit = (int) $request->input('limit', 10);
        $offset = (int) $request->input('offset', 0);

        $mangas = $this->mangaRepository->search($title, $limit, $offset);
        $total = $this->mangaRepository->countSearch($title);

        $data = $mangas->map(function ($manga) {
            return MangaDto::fromModel($manga)->toArray();
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Get details of a single manga, syncing with MangaDex if missing or stale.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $manga = $this->mangaRepository->findWithChapters($id);

        if (!$manga) {
            // Import manga and chapters into DB
            try {
                $manga = $this->syncMangaAction->execute($id);
                // On import set views_count = 1 and last_viewed_at = now()
                $manga->update([
                    'views_count' => 1,
                    'last_viewed_at' => now(),
                ]);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Manga not found'], 404);
            }
        } else {
            // Increment views_count and set last_viewed_at = now()
            $manga->increment('views_count');
            $manga->update(['last_viewed_at' => now()]);

            // If stale (> 24 hours), update asynchronously using the background queue
            if (!$manga->last_synced_at || $manga->last_synced_at->diffInSeconds(now()) > 86400) {
                SyncMangaDetailsJob::dispatch($id);
            }
        }

        $dto = MangaDetailsDto::fromModel($manga);

        return response()->json($dto->toArray());
    }
}
