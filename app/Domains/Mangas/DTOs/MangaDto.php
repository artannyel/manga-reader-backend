<?php

declare(strict_types=1);

namespace App\Domains\Mangas\DTOs;

use App\Domains\Mangas\Models\Manga;
use Carbon\Carbon;

class MangaDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $statusTranslated,
        public readonly ?string $coverFilename,
        public readonly ?string $coverUrl,
        public readonly ?string $lastViewedAt,
        public readonly int $viewsCount,
        public readonly ?string $latestChapterDate = null
    ) {}

    /**
     * Create DTO from Manga model.
     */
    public static function fromModel(Manga $manga): self
    {
        $latestChapterDate = null;
        if (isset($manga->latest_chapter_date) && $manga->latest_chapter_date) {
            $latestChapterDate = Carbon::parse($manga->latest_chapter_date)->toISOString();
        }

        $descriptions = $manga->description;
        $defaultDesc = $descriptions['pt-br'] ?? $descriptions['pt'] ?? $descriptions['en'] ?? (reset($descriptions) ?: '');

        $statusTranslated = match ($manga->status) {
            'ongoing' => 'Em andamento',
            'completed' => 'Finalizado',
            'hiatus' => 'Em hiato',
            'cancelled' => 'Cancelado',
            default => $manga->status,
        };

        return new self(
            id: $manga->id,
            title: $manga->title,
            description: $defaultDesc,
            status: $manga->status,
            statusTranslated: $statusTranslated,
            coverFilename: $manga->cover_filename,
            coverUrl: $manga->cover_url,
            lastViewedAt: $manga->last_viewed_at?->toISOString(),
            viewsCount: (int) $manga->views_count,
            latestChapterDate: $latestChapterDate
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_translated' => $this->statusTranslated,
            'cover_filename' => $this->coverFilename,
            'cover_url' => $this->coverUrl,
            'last_viewed_at' => $this->lastViewedAt,
            'views_count' => $this->viewsCount,
        ];

        if ($this->latestChapterDate !== null) {
            $data['latest_chapter_date'] = $this->latestChapterDate;
        }

        return $data;
    }
}
