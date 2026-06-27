<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Repositories;

use App\Domains\Chapters\Models\Chapter;
use App\Domains\Mangas\Models\Manga;
use Illuminate\Database\Eloquent\Collection;

class MangaRepository
{
    /**
     * Find a manga by its ID.
     */
    public function find(string $id): ?Manga
    {
        return Manga::find($id);
    }

    /**
     * Find a manga by its ID, loading its chapters.
     */
    public function findWithChapters(string $id): ?Manga
    {
        return Manga::with(['chapters' => function ($query) {
            $query->orderBy('chapter_number', 'asc');
        }])->find($id);
    }

    /**
     * Search local mangas by title.
     *
     * @return Collection<int, Manga>
     */
    public function search(string $title, int $limit = 10, int $offset = 0): Collection
    {
        return Manga::query()
            ->where('title', 'like', "%{$title}%")
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Count local mangas matching title search query.
     */
    public function countSearch(string $title): int
    {
        return Manga::query()
            ->where('title', 'like', "%{$title}%")
            ->count();
    }

    /**
     * Get feed mangas ordered by latest chapter's created/updated date.
     *
     * @return Collection<int, Manga>
     */
    public function getFeed(int $limit = 10, int $offset = 0): Collection
    {
        $latestChapterQuery = Chapter::selectRaw('COALESCE(MAX(updated_at), MAX(created_at))')
            ->whereColumn('manga_id', 'mangas.id');

        return Manga::query()
            ->select('mangas.*')
            ->selectSub($latestChapterQuery, 'latest_chapter_date')
            ->orderByRaw('latest_chapter_date DESC NULLS LAST')
            ->orderBy('mangas.updated_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Count total mangas in database for feed.
     */
    public function countFeed(): int
    {
        return Manga::query()->count();
    }

    /**
     * Create or update a manga.
     */
    public function updateOrCreate(array $attributes, array $values): Manga
    {
        return Manga::updateOrCreate($attributes, $values);
    }

    /**
     * Get mangas viewed within the last specified number of days.
     *
     * @param int $days
     * @return Collection<int, Manga>
     */
    public function getRecentlyViewed(int $days = 7): Collection
    {
        return Manga::query()
            ->where('last_viewed_at', '>=', now()->subDays($days))
            ->get();
    }
}
