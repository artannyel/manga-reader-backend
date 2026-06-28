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
     * Fetch recently updated mangas from MangaDex (paginated).
     *
     * @param int $total
     * @return array<int, string>
     */
    public function fetchRecentlyUpdated(int $total = 100): array
    {
        $mangaIds = [];
        $limit = 100;
        for ($offset = 0; $offset < $total; $offset += $limit) {
            $chunkLimit = min($limit, $total - $offset);
            $response = Http::get("{$this->apiUrl}/manga", [
                'limit' => $chunkLimit,
                'offset' => $offset,
                'order' => [
                    'latestUploadedChapter' => 'desc',
                ],
                'includes' => ['cover_art'],
            ]);

            if ($response->failed()) {
                $response->throw();
            }

            $data = $response->json('data') ?? [];
            if (empty($data)) {
                break;
            }

            foreach ($data as $item) {
                $mangaIds[] = $item['id'];
            }

            if (count($data) < $chunkLimit) {
                break;
            }

            usleep(200000);
        }
        return $mangaIds;
    }

    /**
     * Fetch manga details and its cover art from MangaDex.
     *
     * @param string $mangaId
     * @return array{id: string, title: string, description: array, status: ?string, cover_filename: ?string}
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

        $title = $this->resolveLocalizedText($attributes['title'] ?? []);
        $description = $attributes['description'] ?? [];

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
     * Fetch chapters for a manga in all languages.
     *
     * @param string $mangaId
     * @return array<int, array{id: string, title: ?string, chapter_number: string, volume_number: ?string, language: string, pages_count: int}>
     */
    public function fetchChapters(string $mangaId): array
    {
        $chapters = [];
        $limit = 500;
        $offset = 0;

        while (true) {
            $response = Http::get("{$this->apiUrl}/manga/{$mangaId}/feed", [
                'limit' => $limit,
                'offset' => $offset,
                'order' => [
                    'chapter' => 'asc',
                ],
            ]);

            if ($response->failed()) {
                $response->throw();
            }

            $data = $response->json('data') ?? [];
            if (empty($data)) {
                break;
            }

            foreach ($data as $item) {
                $attributes = $item['attributes'] ?? [];
                $chapters[] = [
                    'id' => $item['id'],
                    'title' => $attributes['title'] ?? null,
                    'chapter_number' => $attributes['chapter'] ?? '0',
                    'volume_number' => $attributes['volume'] ?? null,
                    'language' => $attributes['translatedLanguage'] ?? 'en',
                    'pages_count' => $attributes['pages'] ?? 0,
                ];
            }

            if (count($data) < $limit) {
                break;
            }

            $offset += $limit;
            usleep(200000);
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

    /**
     * Resolve localized text based on language priority: pt-br -> pt -> en -> first available.
     *
     * @param array<string, string> $localizedMap
     * @return string
     */
    private function resolveLocalizedText(array $localizedMap): string
    {
        if (empty($localizedMap)) {
            return '';
        }

        foreach (['pt-br', 'pt', 'en'] as $lang) {
            if (isset($localizedMap[$lang]) && $localizedMap[$lang] !== '') {
                return $localizedMap[$lang];
            }
        }

        return (string) reset($localizedMap);
    }
}

