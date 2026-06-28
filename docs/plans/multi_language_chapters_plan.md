# Implementation Plan: Multi-Language Chapters and Fallback Resolution

This plan describes the backend implementation steps to support storing chapters in all languages, providing localized fallbacks, and serving language-specific pages.

---

## Phase 1: MangaDex Service Pagination & Translation Fallback
1. **Deduplicate & Paginate Chapters Fetch**:
   * Modify `MangaDexService@fetchChapters` to loop-paginate through pages of 500 chapters using `limit` and `offset` parameters until no more chapters are returned.
   * Remove the hardcoded `'translatedLanguage' => ['en']` filter to fetch all available translations from MangaDex.
2. **Implement Localization Priority Helper**:
   * Create a helper method in `MangaDexService` that resolves a localized text map (array of string values indexed by languages) by evaluating languages in the order: `pt-br` -> `pt` -> `en` -> first available.
3. **Apply Fallback to Title & Description**:
   * Update `MangaDexService@fetchDetails` to resolve the title and description using the new localization priority helper.

## Phase 2: DTO and Controller Updates
1. **MangaDetailsDto Update**:
   * Add `availableLanguages` string array parameter to `MangaDetailsDto`.
   * Populate `availableLanguages` inside `fromModel()` by fetching unique `language` values from the loaded `chapters` collection, sorted alphabetically.
   * Map `available_languages` to the output array in `toArray()`.

## Phase 3: Language-Specific Chapter Page Retrieval
1. **ChapterController Request Validation**:
   * Update `ChapterPagesRequest` to validate the optional `language` parameter as a string (e.g. `nullable|string`).
2. **Language Switching Logic in showPages**:
   * In `ChapterController@showPages`, if a `language` parameter is present and differs from the current chapter's language:
     * Query the `ChapterRepository` to find a chapter with the matching `manga_id`, `chapter_number`, and the requested `language`.
     * If such a chapter is found, replace the active chapter object with the matched one.
     * If not found, log a debug message and proceed using the original chapter ID.

## Phase 4: Verification & Testing
1. **Unit and Integration Tests**:
   * Update `MangaDexServiceTest` and `ChapterControllerTest` to verify localized fallbacks and language-specific pages retrieval.
   * Execute `./vendor/bin/sail test` to ensure all tests pass.
