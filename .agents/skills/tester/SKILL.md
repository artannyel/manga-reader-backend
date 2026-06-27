---
name: tester
description: Skill for designing and implementing tests in PHPUnit, validating all endpoints, sync jobs, and mocking external services.
---

# Tester Skill

This skill is used to write and execute PHPUnit tests.

## Guidelines

1. **Endpoint Testing**:
   - Write tests for authenticated routes ensuring a 401 response is returned when no token is present.
   - Write tests for login/register validating success and validation failure states.

2. **Sync & Queue Testing**:
   - Write unit tests for background sync Jobs.
   - Mock HTTP requests to the MangaDex API using `Http::fake()` to ensure we do not hit the live API during tests.

3. **Database Assertion**:
   - Verify that mangas and chapters are correctly saved to the database on sync events.
