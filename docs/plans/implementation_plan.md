# Manga Reader Backend - Implementation Plan

This document outlines the step-by-step development process divided into four logical phases. Each phase is self-contained and allows testing before moving to the next.

---

## Phase 1: Project Setup & Authentication

### Goal
Configure the API routing, install Laravel Sanctum, set up the database connection, delete the conflicting default User model, and implement modular user authentication.

### Steps
1. **Clean up Default Laravel Files**:
   - Delete `app/Models/User.php`.
2. **Install API Support & Sanctum**:
   - Run `php artisan install:api` to install Laravel Sanctum, configure migrations, create `routes/api.php`, and update `bootstrap/app.php`.
   - Verify Sanctum database table migrations are created under `database/migrations/`.
3. **Create the Users Domain**:
   - Create directories under `app/Domains/Users/` for `Models`, `Controllers`, `DTOs`, `Requests`, and `Services`.
   - Define `App\Domains\Users\Models\User` extending `Authenticatable` and using `HasApiTokens`.
   - Update `config/auth.php` providers section to use the new Domain User class:
     ```php
     'providers' => [
         'users' => [
             'driver' => 'eloquent',
             'model' => App\Domains\Users\Models\User::class,
         ],
     ],
     ```
4. **Implement Auth Actions & Services**:
   - Create `RegisterRequest` and `LoginRequest` for input validation.
   - Create `AuthService` to handle registration user creation, password verification, and issuing persistent tokens.
   - Create `AuthController` with methods `register`, `login`, and `logout`.
5. **Register Routes**:
   - Register auth routes in `routes/api.php`.
   - Protect logout route with the `auth:sanctum` middleware.
6. **Testing**:
   - Write basic unit and feature tests validating successful/failed register, login, and logout.

---

## Phase 2: Manga Domain, Local Search & Feed

### Goal
Create the Manga database schema, write the external MangaDex API integration client, implement local database searching, build the home feed endpoint, and build the real-time detail sync.

### Steps
1. **Create the Mangas Domain**:
   - Create directories under `app/Domains/Mangas/` (`Models`, `Controllers`, `DTOs`, `Repositories`, `Services`, `Actions`, `Jobs`).
2. **Manga Table Migration**:
   - Create migration for `mangas` table using MangaDex UUID string as the primary key.
   - Include `last_viewed_at` (timestamp, nullable) and `views_count` (unsigned integer, default 0).
   - Index `title` and `last_viewed_at` fields.
   - Define the `Manga` model.
3. **MangaDex API Client (`MangaDexService`)**:
   - Create `MangaDexService` in `app/Domains/Mangas/Services/` using Laravel `Http` client.
   - Read configurations (`MANGADEX_API_URL`) from `.env`.
   - Implement `fetchDetails` method calling `GET /manga/{id}?includes[]=cover_art`.
4. **Local Manga Search Endpoint**:
   - Implement `MangaController@search`.
   - Perform a database query to search local mangas by title (`WHERE title LIKE %query%`).
5. **Manga Feed Endpoint**:
   - Implement `MangaController@index` mapped to `GET /api/manga`.
   - Join `mangas` with `chapters` table, group by manga, and order by `MAX(chapters.created_at)` descending to return mangas with recently added chapters first.
6. **Manga Details Retrieval Endpoint**:
   - Implement `MangaController@show`.
   - For every request, increment local `views_count` and set `last_viewed_at = now()`.
   - Create `SyncMangaAction` to handle fetching from MangaDex and saving to DB.
   - Implement Detail logic:
     - If missing: call `SyncMangaAction` synchronously.
     - If exists but `last_synced_at` > 24 hours: return DB data and dispatch background update job (defined in Phase 4).
     - If fresh: return database record directly.
7. **Testing**:
   - Mock MangaDex API details endpoint in tests.
   - Test local database search, feed, and details sync logic (including views incrementation).

---

## Phase 3: Chapters Domain & Image retrieval

### Goal
Create the Chapters database schema, link chapters to mangas, import chapter feeds, and construct page URLs.

### Steps
1. **Create Chapters Domain**:
   - Create directories under `app/Domains/Chapters/` (`Models`, `Controllers`, `DTOs`, `Repositories`, `Services`, `Actions`).
2. **Chapters Table Migration**:
   - Create migration for `chapters` table. Use MangaDex UUID as primary key, include foreign key constraint to `mangas.id` with cascade delete.
   - Define the `Chapter` model.
3. **Integrate Chapter Sync into Manga Sync**:
   - Update `MangaDexService` to add `fetchChapters` method calling `GET /manga/{id}/feed?limit=500&translatedLanguage[]=en&order[chapter]=asc`.
   - Update `SyncMangaAction` to fetch, parse, and upsert chapters during manga details synchronization.
4. **Chapter Pages Endpoint**:
   - Create `ChapterController` and register route `GET /api/chapters/{id}/pages`.
   - Update `MangaDexService` to add `fetchChapterPages` calling the @Home server endpoint: `GET /at-home/server/{chapterId}`.
   - Create `SyncChapterPagesAction` to call the At-Home endpoint, update `hash`, `pages` (array), `pages_saver` (array), and `last_synced_at` in the DB.
   - In `ChapterController@show`, retrieve pages. If null or stale, run `SyncChapterPagesAction` synchronously.
   - Construct complete URLs using `MANGADEX_UPLOADS_URL`:
     - Original: `{uploads_url}/data/{hash}/{filename}`
     - Compressed: `{uploads_url}/data-saver/{hash}/{filename}`
     - Return the list of generated URLs to the client.
5. **Testing**:
   - Mock `@Home` and `feed` responses.
   - Verify chapter listing and page URL construction.

---

## Phase 4: Sync Queues & Scheduler

### Goal
Offload stale data refreshes to background jobs and set up scheduled tasks (daily discovery and hourly active updates) to keep the manga database up-to-date.

### Steps
1. **Configure Queue Storage**:
   - Set `QUEUE_CONNECTION=database` in `.env`.
   - Run `php artisan queue:table` and `php artisan migrate` to create standard job tables.
2. **Create Sync Job**:
   - Create `App\Domains\Mangas\Jobs\SyncMangaDetailsJob`.
   - In the `handle()` method, call `SyncMangaAction` to synchronize manga details and chapter list.
   - Update `MangaController@show` to dispatch `SyncMangaDetailsJob` asynchronously if the manga details are stale (older than 24 hours).
3. **Daily Sync Command (`manga:daily-sync`)**:
   - Create Artisan command `manga:daily-sync` (e.g. `App\Domains\Mangas\Console\Commands\DailySyncCommand`).
   - Implement command logic:
     - Fetch the 100 most recently updated mangas on MangaDex using `GET https://api.mangadex.org/manga?limit=100&order[latestUploadedChapter]=desc&includes[]=cover_art`.
     - For each manga, dispatch a `SyncMangaDetailsJob` to import or update details and chapters.
4. **Hourly Sync Command (`manga:hourly-sync`)**:
   - Create Artisan command `manga:hourly-sync` (e.g. `App\Domains\Mangas\Console\Commands\HourlySyncCommand`).
   - Implement command logic:
     - Query all `id` (UUIDs) from `mangas` table where `last_viewed_at >= now() - 7 days` (active mangas).
     - Dispatch a `SyncMangaDetailsJob` for each active manga, applying rate-limiting delays between dispatches.
5. **Schedule Commands**:
   - Edit `routes/console.php` to schedule the commands:
     - `manga:daily-sync` daily.
     - `manga:hourly-sync` hourly.
6. **Testing & Validation**:
   - Start the queue worker using `php artisan queue:work` or `php artisan queue:listen`.
   - Test both commands manually to confirm correct job creation in the queue.
