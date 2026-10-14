# Manager Library API

Laravel backend for the Manager Library assessment. This repository owns the JSON API and business workflows; its behavior is defined by the [API contract and product specification](https://github.com/code-sena/test-manager-library-3145555-docs).

## Target stack

- PHP with Laravel
- PostgreSQL 16
- JSON API under `/api/v1`

## Development status

Implementation is in progress. Build the API from the business requirements and HTTP contract, preserving the specified roles, validation, error codes, transactions, and concurrency guarantees. All code, documentation, and user-facing messages are in English.

The assessment evaluates Spec-Driven Development (SDD): translate the reference specification into React and Laravel rather than copying its Python or .NET implementation choices.

## Related repositories

- [Product specification](https://github.com/code-sena/test-manager-library-3145555-docs)
- `test-manager-library-3145555-app` — React operations interface
- `test-manager-library-3145555-infra` — local containers and PostgreSQL
- `test-manager-library-3145555-tool` — demo-data seeder using this API
