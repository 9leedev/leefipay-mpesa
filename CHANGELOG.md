# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.2] - 2026-08-09

### Added

- GitHub Actions CI matrix for Laravel 9–13 (package PHPUnit + fresh Laravel app integration)
- README compatibility table and workflow status badge

### Fixed

- Webhook route no longer depends on the Laravel `api` middleware group (missing from Laravel 11+ skeletons)

## [0.1.1] - 2026-08-09

### Added

- Laravel 13 compatibility (`illuminate/* ^13.0`, Testbench 11, PHPUnit 12)

## [0.1.0] - 2026-08-09

### Added

- Initial Laravel package for the LeefiPay Open API (`/api/v1`)
- Sanctum Bearer authentication via `LEEFIPAY_API_KEY`
- STK Push, payment status, transaction verify, retry, cash, and manual payments
- Payment channels, connection test, auth me/revoke/token helpers
- HMAC webhook receiver + Laravel events
- PHPUnit suite with HTTP fakes
- Laravel 9–12 compatibility via Composer constraints
