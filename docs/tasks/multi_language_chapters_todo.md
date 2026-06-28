# Task Checklist: Multi-Language Chapters and Fallback Resolution (Backend)

- [ ] **Phase 1: MangaDex Service Pagination & Translation Fallback**
  - [ ] Remove `'translatedLanguage' => ['en']` in `MangaDexService@fetchChapters` and implement feed offset pagination.
  - [ ] Implement localization priority resolution method `resolveLocalizedText` in `MangaDexService`.
  - [ ] Apply `resolveLocalizedText` to resolve title and description in `MangaDexService@fetchDetails`.

- [ ] **Phase 2: DTO and Controller Updates**
  - [ ] Update `MangaDetailsDto` to calculate and return `available_languages` from chapters.

- [ ] **Phase 3: Language-Specific Chapter Page Retrieval**
  - [ ] Add `language` validation to `ChapterPagesRequest`.
  - [ ] Add alternative language chapter lookups inside `ChapterController@showPages` based on query inputs.

- [ ] **Phase 4: Verification & Testing**
  - [ ] Write integration test cases for localized fallback and parameter-based pages query.
  - [ ] Execute test suite and verify 100% success.
