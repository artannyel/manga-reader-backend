# Manga Reader Backend - API Specification

This document defines the REST API endpoints, request schemas, response formats, and error behaviors for the Manga Reader Backend. All endpoints (except login and registration) require authentication using **Laravel Sanctum** bearer tokens.

---

## Global API Rules

### Base Path
All API routes are prefixed with `/api`.

### Headers
Every request should include the following headers:
- `Accept: application/json`
- `Content-Type: application/json`
- `Authorization: Bearer <token>` (Required for all routes except Register and Login)

### Common HTTP Status Codes
- `200 OK`: Request succeeded. Response body contains requested data.
- `201 Created`: Resource successfully created (used for Register).
- `401 Unauthorized`: Missing or invalid Sanctum token.
- `404 Not Found`: Requesting a resource that does not exist in local database and cannot be resolved from MangaDex.
- `422 Unprocessable Content`: Validation failed. Response contains validation details.
- `500 Internal Server Error`: An error occurred on the server or during communication with MangaDex.

---

## Endpoint Specification

### 1. Authentication Endpoints

#### 1.1 Register User
- **Method**: `POST`
- **Path**: `/api/register`
- **Authentication**: None
- **Request Body**:
  ```json
  {
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "securepassword123",
    "password_confirmation": "securepassword123"
  }
  ```
- **Validation Rules**:
  - `name`: Required, string, max 255.
  - `email`: Required, string, email, unique:users, max 255.
  - `password`: Required, string, min 8, confirmed.
- **Success Response (201 Created)**:
  ```json
  {
    "message": "User registered successfully",
    "token": "1|abc123xyz...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "created_at": "2026-06-27T12:53:17.000000Z"
    }
  }
  ```
- **Error Response (422 Unprocessable Content)**:
  ```json
  {
    "message": "The email has already been taken. (and/or other validation errors)",
    "errors": {
      "email": [
        "The email has already been taken."
      ],
      "password": [
        "The password field confirmation does not match."
      ]
    }
  }
  ```

#### 1.2 Login User
- **Method**: `POST`
- **Path**: `/api/login`
- **Authentication**: None
- **Request Body**:
  ```json
  {
    "email": "john.doe@example.com",
    "password": "securepassword123"
  }
  ```
- **Validation Rules**:
  - `email`: Required, string, email.
  - `password`: Required, string.
- **Success Response (200 OK)**:
  ```json
  {
    "message": "Login successful",
    "token": "2|def456uvw...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "created_at": "2026-06-27T12:53:17.000000Z"
    }
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Invalid credentials"
  }
  ```
- **Error Response (422 Unprocessable Content)**:
  ```json
  {
    "message": "The email field is required.",
    "errors": {
      "email": [
        "The email field is required."
      ]
    }
  }
  ```

#### 1.3 Logout User
- **Method**: `POST`
- **Path**: `/api/logout`
- **Authentication**: Bearer Token
- **Request Body**: None (Empty)
- **Success Response (200 OK)**:
  ```json
  {
    "message": "Logged out successfully"
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Unauthenticated."
  }
  ```

---

### 2. Manga Endpoints

#### 2.1 Search Manga (Local Database Search)
Queries the local database for mangas matching the query. Does **not** call the external MangaDex API.
- **Method**: `GET`
- **Path**: `/api/manga/search`
- **Authentication**: Bearer Token
- **Query Parameters**:
  - `title` (string, required): Title search query.
  - `limit` (integer, optional, default: 10, max: 100): Pagination limit.
  - `offset` (integer, optional, default: 0): Pagination offset.
- **Query Behavior**:
  - Queries local database: `SELECT * FROM mangas WHERE title LIKE %title% LIMIT {limit} OFFSET {offset}`.
- **Success Response (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": "391b0423-ae2d-49ab-b118-09193234b35e",
        "title": "Manga Title Name",
        "description": "A brief summary of the manga story...",
        "status": "ongoing",
        "cover_filename": "d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
        "cover_url": "https://uploads.mangadex.org/covers/391b0423-ae2d-49ab-b118-09193234b35e/d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
        "last_viewed_at": "2026-06-27T12:53:17.000000Z",
        "views_count": 42
      }
    ],
    "meta": {
      "limit": 10,
      "offset": 0,
      "total": 1
    }
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Unauthenticated."
  }
  ```
- **Error Response (422 Unprocessable Content)**:
  ```json
  {
    "message": "The title field is required.",
    "errors": {
      "title": [
        "The title field is required."
      ]
    }
  }
  ```

#### 2.2 Get Manga Feed
Returns a list of local mangas ordered by their latest chapter's release/created date descending. This is the main feed shown when the user opens the Flutter app.
- **Method**: `GET`
- **Path**: `/api/manga`
- **Authentication**: Bearer Token
- **Query Parameters**:
  - `limit` (integer, optional, default: 10, max: 100): Pagination limit.
  - `offset` (integer, optional, default: 0): Pagination offset.
- **Query Behavior**:
  - Queries local database. Joins `mangas` with `chapters` to order mangas by the maximum `chapters.created_at` or `chapters.updated_at` in descending order.
- **Success Response (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": "391b0423-ae2d-49ab-b118-09193234b35e",
        "title": "Manga Title Name",
        "description": "A brief summary of the manga story...",
        "status": "ongoing",
        "cover_filename": "d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
        "cover_url": "https://uploads.mangadex.org/covers/391b0423-ae2d-49ab-b118-09193234b35e/d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
        "latest_chapter_date": "2026-06-27T12:00:00.000000Z",
        "last_viewed_at": "2026-06-27T12:53:17.000000Z",
        "views_count": 42
      }
    ],
    "meta": {
      "limit": 10,
      "offset": 0,
      "total": 1
    }
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Unauthenticated."
  }
  ```

#### 2.3 Get Manga Details
Fetches detailed info and list of chapters. Contains real-time sync / stale cache check, and tracks active status.
- **Method**: `GET`
- **Path**: `/api/manga/{id}`
- **Authentication**: Bearer Token
- **Path Parameter**:
  - `id`: UUID (MangaDex Manga ID)
- **Tracking Logic**:
  - When this route is requested, the system increments the local `views_count` and updates `last_viewed_at = now()` on the manga record.
- **Sync Logic**:
  1. Check database for Manga with `id`.
  2. If **not found** in database:
     - Instantly call MangaDex API (`GET https://api.mangadex.org/manga/{id}?includes[]=cover_art`) and fetch all English chapters (`GET https://api.mangadex.org/manga/{id}/feed?limit=500&translatedLanguage[]=en&order[chapter]=asc`).
     - Import manga and chapters into the database. Set `last_synced_at = now()`, `last_viewed_at = now()`, and `views_count = 1`.
     - Return the freshly saved data.
  3. If **found** in database:
     - Increment `views_count` and set `last_viewed_at = now()`.
     - Check if `last_synced_at` is older than **24 hours** (`86400` seconds).
     - If **stale**: Return the currently stored DB data immediately to the user (low latency), but dispatch a background queue job `SyncMangaDetailsJob` to update details and chapters in the background.
     - If **fresh**: Return the DB data directly.
- **Success Response (200 OK)**:
  ```json
  {
    "id": "391b0423-ae2d-49ab-b118-09193234b35e",
    "title": "Manga Title Name",
    "description": "A brief summary of the manga story...",
    "status": "ongoing",
    "cover_filename": "d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
    "cover_url": "https://uploads.mangadex.org/covers/391b0423-ae2d-49ab-b118-09193234b35e/d32b5f63-0d29-45e0-8a1a-4d7a18f5d05e.jpg",
    "last_synced_at": "2026-06-27T12:53:17.000000Z",
    "last_viewed_at": "2026-06-27T12:53:17.000000Z",
    "views_count": 43,
    "chapters": [
      {
        "id": "7615967b-1cb2-47ee-9976-b9a35e4d2091",
        "title": "A New Beginning",
        "chapter_number": "1",
        "volume_number": "1",
        "language": "en"
      }
    ]
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Unauthenticated."
  }
  ```
- **Error Response (404 Not Found)**:
  ```json
  {
    "message": "Manga not found"
  }
  ```

---

### 3. Chapter Endpoints

#### 3.1 Get Chapter Pages
Retrieves page URLs for a specific chapter. Computes image server URLs on the fly using `.env` configurations.
- **Method**: `GET`
- **Path**: `/api/chapters/{id}/pages`
- **Authentication**: Bearer Token
- **Path Parameter**:
  - `id`: UUID (MangaDex Chapter ID)
- **Query Parameter**:
  - `quality` (string, optional, default: `data`): Image resolution quality. Allowed values: `data` (original quality) or `data-saver` (compressed quality).
- **Resolution Logic**:
  1. Fetch the chapter from the database.
  2. If the chapter **does not exist**:
     - Call MangaDex API (`GET https://api.mangadex.org/chapter/{id}`) to fetch parent manga reference, then fetch page filenames via `@Home` server API (`GET https://api.mangadex.org/at-home/server/{id}`).
     - Import the chapter data into the database (if parent manga isn't in DB, fetch/import it first, then import the chapter).
     - Store the chapter's `hash`, `pages` (array of original filenames), and `pages_saver` (array of compressed filenames).
  3. If the chapter **exists** but has empty pages/hash OR `last_synced_at` is older than 24 hours:
     - Fetch pages metadata via MangaDex At-Home server API (`GET https://api.mangadex.org/at-home/server/{id}`).
     - Update the chapter's `hash`, `pages`, `pages_saver`, and `last_synced_at`.
  4. Generate and return URLs using `MANGADEX_UPLOADS_URL` environment configuration:
     - Base Format: `{MANGADEX_UPLOADS_URL}/{quality}/{hash}/{filename}`
     - If `quality=data`, use filenames from `pages`.
     - If `quality=data-saver`, use filenames from `pages_saver`.
- **Success Response (200 OK)**:
  ```json
  {
    "chapter_id": "7615967b-1cb2-47ee-9976-b9a35e4d2091",
    "quality": "data",
    "hash": "6463973cb439600eb9c9b0e148e64c4c",
    "pages": [
      "https://uploads.mangadex.org/data/6463973cb439600eb9c9b0e148e64c4c/1-8df83db2aa.png",
      "https://uploads.mangadex.org/data/6463973cb439600eb9c9b0e148e64c4c/2-7489fb821b.png",
      "https://uploads.mangadex.org/data/6463973cb439600eb9c9b0e148e64c4c/3-cf83a218cb.png"
    ]
  }
  ```
- **Error Response (401 Unauthorized)**:
  ```json
  {
    "message": "Unauthenticated."
  }
  ```
- **Error Response (404 Not Found)**:
  ```json
  {
    "message": "Chapter not found"
  }
  ```
- **Error Response (422 Unprocessable Content)**:
  ```json
  {
    "message": "The selected quality is invalid.",
    "errors": {
      "quality": [
        "The selected quality is invalid. Allowed values: data, data-saver."
      ]
    }
  }
  ```
