---
name: coder
description: Skill for writing high-quality PHP/Laravel code in a modular architecture using DTOs, Services, Repositories, Actions, and Sanctum.
---

# Coder Skill

This skill is used to implement coding tasks specified in plans.

## Guidelines

1. **Domain Modularity**:
   - Write files strictly inside the corresponding Domain folders (e.g. `app/Domains/Users`, `app/Domains/Mangas`).
   - Use correct namespaces corresponding to these domains.

2. **Coding Standards**:
   - Use strict typing: `declare(strict_types=1);` at the top of PHP files.
   - Separate concerns: Controllers should only parse input, invoke Actions/Services, and return DTOs or resources. Database queries should be handled in Repositories or Models.
   - Business logic must reside in Services or Actions.
   - Use FormRequests for validation.

3. **External APIs & Queues**:
   - Utilize HTTP Client with proper error handling to interface with MangaDex.
   - Dispatch background jobs to process syncing to prevent blocking request cycles.
