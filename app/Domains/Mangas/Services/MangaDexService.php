<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Services;

use Illuminate\Support\Facades\Http;

class MangaDexService
{
    protected string $apiUrl;
    protected string $uploadsUrl;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.mangadex.api_url', 'https://api.mangadex.org'), '/');
        $this->uploadsUrl = rtrim(config('services.mangadex.uploads_url', 'https://uploads.mangadex.org'), '/');
    }

    /**
     * Fetch the top recently updated mangas from MangaDex.
     *
     * @param int $limit
     * @return array<int, string>
     */
    public function fetchRecentlyUpdated(int $limit = 100): array
    {
        $response = Http::get("{$this->apiUrl}/manga", [
            'limit' => $limit,
            'order' => [
                'latestUploadedChapter' => 'desc',
            ],
            'includes' => ['cover_art'],
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        $data = $response->json('data') ?? [];

        return array_map(fn ($item) => $item['id'], $data);
    }

    /**
     * Fetch manga details and its cover art from MangaDex.
     *
     * @param string $mangaId
     * @return array{id: string, title: string, description: ?string, status: ?string, cover_filename: ?string}
     */
    public function fetchDetails(string $mangaId): array
    {
        $response = Http::get("{$this->apiUrl}/manga/{$mangaId}", [
            'includes' => ['cover_art'],
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        $data = $response->json('data') ?? [];
        $attributes = $data['attributes'] ?? [];

        // Parse localized title (prefer english 'en', fallback to first available)
        $title = $attributes['title']['en'] ?? null;
        if (!$title && !empty($attributes['title'])) {
            $title = reset($attributes['title']);
        }
        $title = $title ?? '';

        // Parse localized description (prefer english 'en', fallback to first available)
        $description = $attributes['description']['en'] ?? null;
        if (!$description && !empty($attributes['description'])) {
            $description = reset($attributes['description']);
        }

        // Find cover filename in relationships
        $coverFilename = null;
        foreach ($data['relationships'] ?? [] as $rel) {
            if (($rel['type'] ?? '') === 'cover_art') {
                $coverFilename = $rel['attributes']['fileName'] ?? null;
                break;
            }
        }

        return [
            'id' => $data['id'] ?? $mangaId,
            'title' => $title,
            'description' => $description,
            'status' => $attributes['status'] ?? null,
            'cover_filename' => $coverFilename,
        ];
    }

    /**
     * Fetch English chapters for a manga.
     *
     * @param string $mangaId
     * @return array<int, array{id: string, title: ?string, chapter_number: string, volume_number: ?string, language: string}>
     */
    public function fetchChapters(string $mangaId): array
    {
        $response = Http::get("{$this->apiUrl}/manga/{$mangaId}/feed", [
            'limit' => 500,
            'translatedLanguage' => ['en'],
            'order' => [
                'chapter' => 'asc',
            ],
        ]);

        if ($response->failed()) {
            $response->throw();
        }

        $chapters = [];
        $data = $response->json('data') ?? [];

        foreach ($data as $item) {
            $attributes = $item['attributes'] ?? [];
            $chapters[] = [
                'id' => $item['id'],
                'title' => $attributes['title'] ?? null,
                'chapter_number' => $attributes['chapter'] ?? '0',
                'volume_number' => $attributes['volume'] ?? null,
                'language' => $attributes['translatedLanguage'] ?? 'en',
            ];
        }

        return $chapters;
    }

    /**
     * Fetch page filenames and hash for a chapter.
     *
     * @param string $chapterId
     * @return array{base_url: string, hash: string, pages: array<int, string>, pages_saver: array<int, string>}
     */
    public function fetchChapterPages(string $chapterId): array
    {
        $response = Http::get("{$this->apiUrl}/at-home/server/{$chapterId}");

        if ($response->failed()) {
            $response->throw();
        }

        $json = $response->json() ?? [];
        $chapterData = $json['chapter'] ?? [];

        return [
            'base_url' => $json['baseUrl'] ?? '',
            'hash' => $chapterData['hash'] ?? '',
            'pages' => $chapterData['data'] ?? [],
            'pages_saver' => $chapterData['dataSaver'] ?? [],
        ];
    }

    /**
     * Fetch the parent manga ID for a given chapter ID from MangaDex.
     */
    public function fetchMangaIdFromChapter(string $chapterId): string
    {
        $response = Http::get("{$this->apiUrl}/chapter/{$chapterId}");

        if ($response->failed()) {
            $response->throw();
        }

        $relationships = $response->json('data.relationships') ?? [];
        foreach ($relationships as $rel) {
            if (($rel['type'] ?? '') === 'manga') {
                return $rel['id'];
            }
        }

        throw new \Exception("Manga relationship not found for chapter {$chapterId}");
    }
}

