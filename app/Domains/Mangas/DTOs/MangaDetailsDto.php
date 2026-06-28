<?php

declare(strict_types=1);

namespace App\Domains\Mangas\DTOs;

use App\Domains\Chapters\DTOs\ChapterDto;
use App\Domains\Mangas\Models\Manga;

class MangaDetailsDto
{
    /**
     * @param array<int, ChapterDto> $chapters
     * @param array<int, string> $availableLanguages
     * @param array<string, string> $descriptions
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly array $descriptions,
        public readonly ?string $status,
        public readonly ?string $statusTranslated,
        public readonly ?string $coverFilename,
        public readonly ?string $coverUrl,
        public readonly ?string $lastSyncedAt,
        public readonly ?string $lastViewedAt,
        public readonly int $viewsCount,
        public readonly array $chapters,
        public readonly array $availableLanguages
    ) {}

    /**
     * Create DTO from Manga model.
     */
    public static function fromModel(Manga $manga): self
    {
        $chapters = $manga->chapters->map(function ($chapter) {
            return ChapterDto::fromModel($chapter);
        })->all();

        $availableLanguages = $manga->chapters
            ->pluck('language')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $statusTranslated = match ($manga->status) {
            'ongoing' => 'Em andamento',
            'completed' => 'Finalizado',
            'hiatus' => 'Em hiato',
            'cancelled' => 'Cancelado',
            default => $manga->status,
        };

        $descriptions = $manga->description;
        $defaultDesc = $descriptions['pt-br'] ?? $descriptions['pt'] ?? $descriptions['en'] ?? (reset($descriptions) ?: '');

        return new self(
            id: $manga->id,
            title: $manga->title,
            description: $defaultDesc,
            descriptions: $descriptions,
            status: $manga->status,
            statusTranslated: $statusTranslated,
            coverFilename: $manga->cover_filename,
            coverUrl: $manga->cover_url,
            lastSyncedAt: $manga->last_synced_at?->toISOString(),
            lastViewedAt: $manga->last_viewed_at?->toISOString(),
            viewsCount: (int) $manga->views_count,
            chapters: $chapters,
            availableLanguages: $availableLanguages
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
            'description' => $this->description,
            'descriptions' => $this->descriptions,
            'status' => $this->status,
            'status_translated' => $this->statusTranslated,
            'cover_filename' => $this->coverFilename,
            'cover_url' => $this->coverUrl,
            'last_synced_at' => $this->lastSyncedAt,
            'last_viewed_at' => $this->lastViewedAt,
            'views_count' => $this->viewsCount,
            'chapters' => array_map(fn(ChapterDto $dto) => $dto->toArray(), $this->chapters),
            'available_languages' => $this->availableLanguages,
        ];
    }
}
