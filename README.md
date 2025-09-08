# Platforma System API

A lightweight PHP API using Symfony components and Doctrine ORM with SQLite. Includes JWT auth, request logging, admin tools, and a small domain (Notes, Franchises, Packages) designed to be easy to integrate from a frontend.

## Overview

- Routing: Symfony Routing (front controller in `public/index.php`)
- HTTP: Symfony HttpFoundation
- ORM: Doctrine ORM 3 (attribute mappings) + SQLite (file DB)
- Auth: JWT for all routes except `/auth/login`
- Logging: All requests logged (success and error); admin endpoint to view logs
- Domain: Notes, Packages, Franchises with statuses, comments, links, and Tasks

## Quick start

1. Install dependencies
2. Initialize the database
3. Seed an admin user
4. Start PHP's built-in server

### Commands (Windows PowerShell)

```powershell
# Install
composer install

# Recreate schema (drops and creates)
php .\bin\init-db.php

# Seed admin user
php .\bin\seed-admin.php

# Start server on http://127.0.0.1:8000
php -S 127.0.0.1:8000 -t public
```

## Configuration

- DB location: `var/data.sqlite`
- JWT settings (override via `.env`):
  - `JWT_SECRET`
  - `JWT_TTL` (seconds)
  - `JWT_ALG` (e.g., HS256)
- CORS (override via `.env`):
  - `CORS_ALLOWED_ORIGINS` (comma-separated list, e.g. `http://localhost:5173,https://system.platforma360.ru`)

See `.env.example` for reference.

## Authentication

- POST `/auth/login`
  - body: `{ "email": string, "password": string }`
  - returns: `{ token, user: { id, email, role } }`
- All other endpoints require a Bearer token (except anonymous fallback described below).

All other endpoints require a Bearer token in the `Authorization` header.

## Error model

Errors are returned as JSON with a consistent shape:

```json
{
  "error": { "code": 403, "message": "forbidden" },
  "path": "/example",
  "method": "GET",
  "requestId": "a1b2c3d4e5f6a7b8"
}
```

## Admin endpoints

- GET `/admin/logs` (admin only)
  - query: `limit` (1-200), `offset`
  - returns: `{ items, limit, offset }`
- POST `/admin/ban-ip` (admin only)
  - body: `{ ip: string }`

## Domain endpoints (for frontend)

All endpoints return JSON.

### Notes
- GET `/notes` → List notes
- POST `/notes/create` → Create a note

### Packages
- GET `/packages` → List packages
- POST `/packages/create` → Create a package
  - body: `{ name: string, type: "paid"|"test-drive", leadCount?: number }`

### Franchises

Franchise object:

```json
{
  "id": number,
  "name": string,
  "code": string,
  "status": "published"|"testing"|"unpublished",
  "email": string|null,
  "webhookUrl": string|null,
  "telegramId": string|null,
  "description": string|null,
  "cost": number|null,
  "investment": number|null,
  "paybackPeriod": number|null,
  "monthlyIncome": number|null,
  "publishedDurationDays": number|null,
  "createdAt": string,
  "links": [{ "id": number, "url": string, "label": string|null }],
  "comments": [{ "id": number, "content": string, "createdAt": string }]
}
```

Endpoints:

- GET `/franchises`
  - query:
    - `limit` (1–200), `offset` (default 0)
    - `status`: published|testing|unpublished
    - `q`: case-insensitive search in name or code
  - returns: `{ items, limit, offset }`

- POST `/franchises/create`
  - body (all optional except name, code):
    ```json
    {
      "name": "Umbrella Corp",
      "code": "UMB01",
      "status": "published|testing|unpublished",
      "email": "a@b.com",
      "webhookUrl": "https://...",
      "telegramId": "123456",
      "description": "...",
      "cost": 1000,
      "investment": 2000,
      "paybackPeriod": 12,
      "monthlyIncome": 300
    }
    ```
  - also accepts arrays:
    - `links`: `[{ "url": string, "label"?: string }]`
    - `comments`: `[{ "content": string }]`

- POST `/franchises/{id}/comments`
  - body: `{ content: string }`
  - returns: `{ id, franchiseId, content, createdAt }`

- POST `/franchises/{id}/links`
  - body: `{ url: string, label?: string }`
  - returns: `{ id, franchiseId, url, label }`

### Tasks

- POST `/tasks` → Create a task
  - body:
    ```json
    {
      "assignedToId": 2,
      "description": "Call the client and confirm details",
      "deadline": "2025-12-31T17:00:00+03:00",
      "franchiseId": 3
    }
    ```
  - returns: `{ id, description, deadline, createdAt, createdBy: { id, email }, assignedTo: { id, email }, franchise?: { id, name, code } }`

- GET `/tasks/my` → List tasks assigned to the current user
  - returns: `[{ ...task }, ...]` sorted by `deadline` asc, then `id` desc

### Users

- GET `/users` → List users to assign tasks
  - query: `q` (search by email, optional), `limit` (1–200, default 100), `offset` (default 0)
  - returns: `{ items: [{ id, email }], limit, offset }`

## Request logging

Every handled request is logged with method, path, status, optional message, userId (if known), and IP. Logs are visible to admins via `/admin/logs`.

## Running locally

- Built-in server at `http://127.0.0.1:8000`
- The front controller (`public/index.php`) boots routes and the Kernel with DI container.
Routes are defined with explicit HTTP methods (GET/POST/PUT/DELETE). If you get a 405, double-check you're using the allowed method.

## Frontend integration tips

- Always include `Authorization: Bearer <token>` for authenticated flows.
- For unauthenticated browsing, the API still responds using an anonymous user; consider displaying limited UI.
- Handle 401 (invalid token) and 403 (banned/forbidden) with redirects/notifications.
- Use pagination in lists and debounce search queries using the `q` param.
- Respect the error model shape for consistent UI error handling.

## Project structure

- `public/` – front controller and assets
- `config/` – parameters and routes
- `src/` – Kernel, controllers, entities, DI, infrastructure
- `bin/` – scripts: init DB, seed admin, smoke tests
- `var/` – SQLite database file

## Troubleshooting

- If you see a 405, the HTTP method isn't allowed for that path (e.g., using POST where only GET is allowed).
- If DB errors occur, recreate the schema: `php .\bin\init-db.php`.
- Ensure `.env` overrides are correct for JWT if tokens fail to verify.
- Admin-only endpoints require a token for a user with role `admin`.
- If CORS preflight fails, ensure your frontend origin is listed in `CORS_ALLOWED_ORIGINS` and that you're using allowed methods/headers.
