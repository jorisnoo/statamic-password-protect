# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Security

- hash stored passwords and migrate legacy plaintext settings
- throttle password verification attempts
- protect REST, GraphQL, and Glide delivery routes
- require CP permission for authenticated-user bypasses and enforce CP path boundaries
- invalidate authorization after password and enabled-state changes
- clear existing static caches before enabling protection

## [0.1.0](https://github.com/jorisnoo/statamic-password-protect/releases/tag/v0.1.0) (2026-03-23)

### Features

- add optional page title and simplify password form ui ([abbb6bd](https://github.com/jorisnoo/statamic-password-protect/commit/abbb6bdc2bd35714765f1a89878d599564da218c))
