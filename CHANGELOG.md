# Changelog

All notable changes to `laravel-plunk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-14

### Added
- Full Plunk API parity.
- Contacts, Events, and Transactional resources.
- Campaigns, Templates, and Segments resources.
- Typed exceptions (`AuthenticationException`, `BillingException`, `ConflictException`, `RateLimitException`, `ValidationException`).
- Automatic retries on rate limits (429) and server errors (5xx).
- Dual key support (`PLUNK_SECRET_KEY` and `PLUNK_PUBLIC_KEY`).
