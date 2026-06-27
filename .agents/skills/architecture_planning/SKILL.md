---
name: architecture_planning
description: Architecture and planning skill for Spec-Driven Development (SDD) to define specifications, designs, implementation plans, and tasks.
---

# Architecture & Planning Skill

This skill is used to design backend components and generate SDD documentation.

## Guidelines

1. **Spec Generation**:
   - Create clear, language-agnostic functional specifications for the endpoints (inputs, outputs, error conditions).
   - Document any environment variables or external API interactions (such as MangaDex API endpoints).

2. **System Design**:
   - Define database schema migrations (tables, fields, foreign keys, indexes).
   - Structure domain modularity: map out domains (`Users`, `Mangas`, `Chapters`) and their structural classes (`Controllers`, `DTOs`, `Models`, `Repositories`, `Requests`, `Services`, `Actions`, `Jobs`).
   - Define the queue-based sync flow and schedule.

3. **Implementation Plan**:
   - Write a step-by-step phased approach for implementing the backend without breaking existing features.
   - Define exactly what should be done in each phase.

4. **Task Checklist**:
   - Create a detailed todo-list file tracking completion state for each task in the plan.
