# Contributing

Clone the repository, install its development dependencies, and run the checks for your change:

```sh
composer install
vendor/bin/phpunit
composer cs-check
composer stan-setup
composer stan
```

Use a test database. Integration tests and fixtures can delete or replace data. The CI workflow covers SQLite, MySQL, PostgreSQL, and the lowest supported dependencies.

## Documentation

The site uses VitePress. From `docs/`:

```sh
npm ci
npm run docs:dev
npm run docs:build
```

Keep setup commands in Installation and working examples in Getting started or the relevant reference page. Verify examples against the implementation and check links after a build.
