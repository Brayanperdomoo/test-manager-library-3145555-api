# Manager Library API

Laravel 13 JSON API for Manager Library. It uses PostgreSQL, UTC instants, UUID identifiers, English error messages, and the `/api/v1` contract.

## Implemented workflows

- Eight-hour signed bearer tokens and role checks; the admin account is provisioned from deployment environment variables.
- Five fixed categories, searchable and paginated books, member registration/search, transactional multi-item loans, item-by-item returns, overdue items, and snapshot-based most-borrowed reporting.
- Database constraints protect available-copy bounds, unique identifiers, and loan-item composition. Loan items retain the title, ISBN, and category name captured when lent.

## Run

The recommended workflow is `docker compose up --build` from the infrastructure repository. Laravel migrations and the initial admin seeding run when the API container starts. For local development, install PHP 8.3+, Composer, PostgreSQL 16, copy `.env.example` to `.env`, configure the database and secrets, then run `composer install`, `php artisan migrate`, and `php artisan serve`.

Health check: `GET /api/v1/health`. API reference: [specification and contract](https://github.com/code-sena/test-manager-library-3145555-docs).
