# Project-Scoped Rules: Manga Reader Backend

This project implements a Laravel backend that manages manga reading from the MangaDex API using a queue-based sync mechanism to respect rate limits.

## Architectural Rules

1. **Modular Architecture (Domain-Driven Design)**:
   - Code must be organized under `app/Domains/` instead of placing everything in standard Laravel folders.
   - For example: `app/Domains/Users`, `app/Domains/Mangas`, `app/Domains/Chapters`.
   - Each Domain should contain its own:
     - `Models/`
     - `Controllers/` (or mapped to Controllers)
     - `DTOs/`
     - `Repositories/`
     - `Requests/`
     - `Services/`
     - `Actions/`
     - `Jobs/` / `Events/` / `Listeners/`
   - **CRITICAL**: The default `app/Models/User.php` file must be deleted to avoid conflict with the domain-specific User model at `app/Domains/Users/Models/User.php`.

2. **Authentication**:
   - Use Laravel Sanctum with persistent tokens.
   - All endpoints require authentication (using Sanctum middleware), except for login and register.
   - Users have basic data: `name`, `email`, `password`.

3. **MangaDex API Sync & Queues**:
   - The Flutter mobile app does not consume MangaDex directly. It communicates only with our backend.
   - Search results are proxied through our backend and cached for a configurable duration (e.g., 6 hours).
   - When a user views details of a manga, the backend imports/sincronizes the manga and its chapters to the database.
   - All page image URLs are constructed pointing to `https://uploads.mangadex.org` configured in the `.env` file (e.g. `MANGADEX_UPLOADS_URL`). We do not host images; we store metadata (filenames, hash) and construct URLs on-demand.
   - A scheduled task runs hourly to check for updates for all registered/searched mangas in our database.
   - Background jobs are used to execute the MangaDex API synchronization.

## Agent Specialist Roles

1. **Architecture & Planning Agent (architect)**: Responsible for writing specifications, system designs, implementation plans, and task breakdowns in Spec-Driven Development (SDD).
2. **Coder Agent (coder)**: Responsible for implementing PHP/Laravel code adhering strictly to modular domain patterns, Sanctum configuration, and the specifications.
3. **Reviewer Agent (reviewer)**: Responsible for inspecting and validating code quality, design pattern alignment, and ensuring no loose models or standard violations exist.
4. **Tester Agent (tester)**: Responsible for writing unit, integration, and feature tests using PHPUnit, ensuring thorough coverage and mocked external MangaDex API responses.
