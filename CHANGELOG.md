# Changelog

All notable changes to this project will be documented in this file.

## [0.2.2](https://github.com/jorisnoo/statamic-password-protect/releases/tag/0.2.2) (2026-08-24)

### Bug Fixes

- always redirect to home page after password verification ([5ebc5e9](https://github.com/jorisnoo/statamic-password-protect/commit/5ebc5e92c89fdf0fe93c03f67955aa1dadcc4fda))
## [0.2.1](https://github.com/jorisnoo/statamic-password-protect/releases/tag/0.2.1) (2026-08-21)

### Bug Fixes

- remove password length restrictions ([0441e1c](https://github.com/jorisnoo/statamic-password-protect/commit/0441e1c2def117e58928b719fca7b3ead8c8c56a))
## [0.2.0](https://github.com/jorisnoo/statamic-password-protect/releases/tag/0.2.0) (2026-07-10)

### Bug Fixes

- harden password protection ([a4227af](https://github.com/jorisnoo/statamic-password-protect/commit/a4227afed7a8f21391e0a389746a9753ba740c64))

### Continuous Integration

- add release workflow ([1aa218e](https://github.com/jorisnoo/statamic-password-protect/commit/1aa218ebb09297f38bccf06dc01d3f1687e35cdc))

### Chores

- **deps:** bump actions/checkout from 6 to 7 ([b8dee63](https://github.com/jorisnoo/statamic-password-protect/commit/b8dee63fa5d75ca23f8ef98b9eeb4494af3cd91c))
- rename LICENSE to LICENSE.md, tidy metadata, add dependabot config and justfile ([eddd1c9](https://github.com/jorisnoo/statamic-password-protect/commit/eddd1c95e4c0bd186a4d06c6d8c5d24c4479f268))
## Unreleased

### Security

- hash stored passwords and migrate legacy plaintext settings
- throttle password verification attempts
- protect REST, GraphQL, and Glide delivery routes
- require CP permission for authenticated-user bypasses and enforce CP path boundaries
- invalidate authorization after password and enabled-state changes
- clear existing static caches before enabling protection

## [0.1.0](https://github.com/jorisnoo/statamic-password-protect/releases/tag/0.1.0) (2026-03-23)

### Features

- add optional page title and simplify password form ui ([abbb6bd](https://github.com/jorisnoo/statamic-password-protect/commit/abbb6bdc2bd35714765f1a89878d599564da218c))
