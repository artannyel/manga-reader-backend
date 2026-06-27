# Manga Reader Backend - Implementation Checklist

This checklist tracks the implementation of the Manga Reader Backend components. It is structured to follow the phases outlined in the implementation plan.

## Phase 1: Project Setup & Authentication
- [x] **Clean default files**
  - [x] Delete `app/Models/User.php`
- [x] **Sanctum & API Configuration**
  - [x] Run `php artisan install:api`
  - [x] Verify that Sanctum database migrations are generated
- [x] **Users Domain Structuring**
  - [x] Create directory `app/Domains/Users/`
  - [x] Create subdirectories: `Models/`, `Controllers/`, `DTOs/`, `Requests/`, `Services/`
- [x] **User Model & Config**
  - [x] Implement `App\Domains\Users\Models\User.php` (must include `HasApiTokens` trait)
  - [x] Update model mapping in `config/auth.php` providers to point to the new User domain class
- [x] **Authentication Layer**
  - [x] Create `RegisterRequest` for input validation
  - [x] Create `LoginRequest` for input validation
  - [x] Create `AuthService` (handles password hashing, user registration, and Sanctum tokens generation)
  - [x] Create `AuthController` (methods: `register`, `login`, `logout`)
- [x] **Routing**
  - [x] Map Auth endpoints in `routes/api.php` (`/api/register`, `/api/login`)
  - [x] Map `/api/logout` protected by `auth:sanctum` middleware
- [x] **Testing**
  - [x] Write Feature tests validating Register, Login, and Logout operations

---

## Phase 2: Manga Domain, Local Search & Feed
- [ ] **Mangas Domain Structuring**
  - [ ] Create directory `app/Domains/Mangas/`
  - [ ] Create subdirectories: `Models/`, `Controllers/`, `DTOs/`, `Repositories/`, `Services/`, `Actions/`, `Jobs/`
- [ ] **Database Setup**
  - [ ] Create migration for `mangas` table:
    - [ ] UUID primary key, title, description, cover_filename, status, last_synced_at
    - [ ] Add `last_viewed_at` (timestamp, nullable) and `views_count` (unsigned integer, default 0)
    - [ ] Add index on `title` and `last_viewed_at`
  - [ ] Run migrations: `php artisan migrate`
- [ ] **Models & Repositories**
  - [ ] Implement `Manga` model inside `app/Domains/Mangas/Models/`
  - [ ] Create `MangaRepository` to encapsulate database access
- [ ] **External MangaDex Service**
  - [ ] Add `.env` config keys: `MANGADEX_API_URL`, `MANGADEX_UPLOADS_URL`, `MANGADEX_SYNC_STALE_TTL`
  - [ ] Implement `MangaDexService` (with `fetchDetails` method using Laravel's `Http` client)
- [ ] **Endpoints & Actions**
  - [ ] Create `MangaController`
  - [ ] Implement `GET /api/manga/search` endpoint using local DB query (`WHERE title LIKE %query%`)
  - [ ] Implement `GET /api/manga` feed endpoint ordering by latest chapter's created/updated date descending
  - [ ] Create `SyncMangaAction` to synchronize details and save/update the local DB record
  - [ ] Implement `GET /api/manga/{id}` details endpoint:
    - [ ] Increment local `views_count` and set `last_viewed_at = now()`
    - [ ] Real-time sync if missing from database
    - [ ] Dispatch background sync job if stale (> 24 hours) and return existing data
    - [ ] Return cached DB records directly if fresh
- [ ] **Testing**
  - [ ] Mock MangaDex details endpoint
  - [ ] Write integration tests for:
    - [ ] Local search (with LIKE queries)
    - [ ] Feed endpoint (ordered by chapter updates)
    - [ ] Detail retrieval (real-time sync logic, views counter check)

---

## Phase 3: Chapters Domain & Image retrieval
- [ ] **Chapters Domain Structuring**
  - [ ] Create directory `app/Domains/Chapters/`
  - [ ] Create subdirectories: `Models/`, `Controllers/`, `DTOs/`, `Repositories/`, `Services/`, `Actions/`
- [ ] **Database Setup**
  - [ ] Create migration for `chapters` table (UUID primary key, foreign key `manga_id` referencing `mangas.id` on delete cascade, title, chapter_number, volume_number, language, hash, pages, pages_saver, last_synced_at)
  - [ ] Run migrations: `php artisan migrate`
- [ ] **Models & Repositories**
  - [ ] Implement `Chapter` model in `app/Domains/Chapters/Models/`
  - [ ] Create `ChapterRepository` for database queries
- [ ] **Chapters Feeding Integration**
  - [ ] Add `fetchChapters` method to `MangaDexService` (hits `GET /manga/{id}/feed`)
  - [ ] Update `SyncMangaAction` to fetch the list of English chapters and upsert them into the database during manga details sync
- [ ] **Pages Retrieval Implementation**
  - [ ] Add `fetchChapterPages` to `MangaDexService` (hits `@Home` server API `GET /at-home/server/{chapterId}`)
  - [ ] Create `SyncChapterPagesAction` (performs `@Home` API call, stores page files arrays and hash, updates `last_synced_at`)
  - [ ] Create `ChapterController` with route `GET /api/chapters/{id}/pages`
  - [ ] Implement pages resolver in `ChapterController@show`:
    - [ ] If pages/hash not stored or older than 24 hours: trigger `SyncChapterPagesAction` synchronously
    - [ ] Build and return full URLs based on requested quality (`data` or `data-saver`) using `MANGADEX_UPLOADS_URL`
- [ ] **Testing**
  - [ ] Mock chapter list feed and At-Home server API endpoints
  - [ ] Write feature tests for `/api/chapters/{id}/pages` testing URL construction and quality fallback parameters

---

## Phase 4: Sync Queues & Scheduler
- [ ] **Queue Setup**
  - [ ] Set `QUEUE_CONNECTION=database` in `.env`
  - [ ] Create and execute queue database tables (`php artisan queue:table && php artisan migrate`)
- [ ] **Background Sync Job**
  - [ ] Create job `SyncMangaDetailsJob` calling `SyncMangaAction` inside the handle method
  - [ ] Update `MangaController@show` to dispatch `SyncMangaDetailsJob` to the queue when details are stale (> 24 hours)
- [ ] **Artisan Commands**
  - [ ] Implement Artisan Command `manga:daily-sync` (fetches top 100 updated mangas from MangaDex, dispatches sync job for each)
  - [ ] Implement Artisan Command `manga:hourly-sync` (queries local mangas viewed in the last 7 days, dispatches sync job for each)
- [ ] **Scheduling Tasks**
  - [ ] Register `manga:daily-sync` to run daily in `routes/console.php`
  - [ ] Register `manga:hourly-sync` to run hourly in `routes/console.php`
- [ ] **Testing & Verification**
  - [ ] Verify that hitting a stale manga details endpoint schedules a background job in the `jobs` table
  - [ ] Verify manual and scheduled runs of `manga:daily-sync` and `manga:hourly-sync`
  - [ ] Ensure rate limits are respected by verifying throttle handling or dispatch delays
