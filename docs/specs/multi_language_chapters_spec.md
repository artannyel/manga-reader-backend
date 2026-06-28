# Spec-Driven Development: Multi-Language Chapters and Fallback Resolution

This specification details the backend architecture changes required to fetch, store, and serve manga chapters in all available languages, along with localized fallbacks for descriptions and chapter page retrieval.

---

## 1. Localized Metadata Fallback Rules
When synchronizing or retrieving manga details, the backend must resolve the localized title and description according to the following priority list:
1. `pt-br` (Brazilian Portuguese)
2. `pt` (Portuguese)
3. `en` (English)
4. First available language returned by MangaDex

---

## 2. Endpoint Updates

### 2.1 Get Manga Details
* **Path**: `GET /api/manga/{id}`
* **Description**: Returns manga details with its chapters and an array of all available languages found in its chapter list.
* **Success Response Schema (200 OK)**:
  ```json
  {
    "id": "f349008f-0896-4ec8-bc37-56733525dfc7",
    "title": "Manga Title (PT-BR fallback resolved)",
    "description": "Manga Description (PT-BR fallback resolved)",
    "status": "ongoing",
    "status_translated": "Em andamento",
    "cover_filename": "cover.jpg",
    "cover_url": "https://uploads.mangadex.org/covers/...",
    "last_synced_at": "2026-06-28T14:15:32.000000Z",
    "last_viewed_at": "2026-06-28T14:15:32.000000Z",
    "views_count": 5,
    "available_languages": ["pt-br", "en", "es"],
    "chapters": [
      {
        "id": "4b9ae6c6-db67-461b-9319-dcb9f28ba15c",
        "title": "Capítulo 1 (PT-BR/EN resolved)",
        "chapter_number": "1",
        "volume_number": "1",
        "language": "pt-br"
      },
      {
        "id": "5ce62b9e-91c6-4cab-8129-68c9e31e240d",
        "title": "Chapter 1",
        "chapter_number": "1",
        "volume_number": "1",
        "language": "en"
      }
    ]
  }
  ```

### 2.2 Get Chapter Pages
* **Path**: `GET /api/chapters/{id}/pages`
* **Query Parameters**:
  * `quality` (string, optional): `data` (default) or `data-saver`
  * `language` (string, optional): Requested language for the pages (e.g. `pt-br`)
* **Behavior**:
  1. Look up the initial chapter by `{id}`.
  2. If `language` query parameter is provided:
     * Search the database for a chapter with the same `manga_id` and `chapter_number` as the initial chapter, but with the requested `language`.
     * If found, swap the target chapter to the matched alternative language chapter.
     * If not found, log a warning and continue using the initial chapter `{id}`.
  3. Load and return pages for the resolved target chapter (syncing with MangaDex if missing/stale).
