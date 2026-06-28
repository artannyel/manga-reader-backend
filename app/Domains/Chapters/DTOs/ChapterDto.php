<?php

declare(strict_types=1);

namespace App\Domains\Chapters\DTOs;

use App\Domains\Chapters\Models\Chapter;

class ChapterDto
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly string $chapterNumber,
        public readonly ?string $volumeNumber,
        public readonly string $language,
        public readonly int $pagesCount
    ) {}

    /**
     * Create DTO from Chapter model.
     */
    public static function fromModel(Chapter $chapter): self
    {
        return new self(
            id: $chapter->id,
            title: $chapter->title,
            chapterNumber: $chapter->chapter_number,
            volumeNumber: $chapter->volume_number,
            language: $chapter->language,
            pagesCount: (int) $chapter->pages_count
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'chapter_number' => $this->chapterNumber,
            'volume_number' => $this->volumeNumber,
            'language' => $this->language,
            'pages_count' => $this->pagesCount,
        ];
    }
}
