<?php

declare(strict_types=1);

namespace App\Domains\Chapters\Repositories;

use App\Domains\Chapters\Models\Chapter;

class ChapterRepository
{
    /**
     * Find a chapter by ID.
     */
    public function find(string $id): ?Chapter
    {
        return Chapter::find($id);
    }

    /**
     * Upsert a list of chapters.
     *
     * @param array<int, array<string, mixed>> $chapters
     */
    public function upsertChapters(array $chapters): void
    {
        if (empty($chapters)) {
            return;
        }

        Chapter::upsert(
            $chapters,
            ['manga_id', 'chapter_number', 'language'],
            ['title', 'volume_number', 'updated_at']
        );
    }

    /**
     * Update a chapter.
     */
    public function update(Chapter $chapter, array $attributes): bool
    {
        return $chapter->update($attributes);
    }
}
