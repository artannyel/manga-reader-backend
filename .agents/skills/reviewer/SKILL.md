---
name: reviewer
description: Skill for reviewing and validating Laravel code, ensuring architectural rules and clean coding principles are followed.
---

# Reviewer Skill

This skill is used to validate implemented code.

## Guidelines

1. **Verify Modularity**:
   - Check if any new code is added to standard Laravel folders when it should be in a Domain.
   - Confirm that the default `app/Models/User.php` has been deleted and replaced with a domain-specific model.

2. **Verify Security**:
   - Ensure all routes (except login/register) are wrapped with `auth:sanctum` middleware.
   - Verify sensitive parameters are not exposed and inputs are validated.

3. **Code Quality**:
   - Ensure `declare(strict_types=1);` is used.
   - Ensure no raw database queries are inside Controllers.
   - Ensure external HTTP responses are checked for failure and handled gracefully.
