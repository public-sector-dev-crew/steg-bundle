# Changelog

All notable changes to `lotse/steg-bundle` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] — 2026-05-06

### Changed
- Bumped `lotse/steg` constraint from `^1.0` to `^1.0.1`. The `InferenceClientInterface::class` DI alias relies on `StegClient` implementing the interface, which was only added in Steg v1.0.1. Installing this bundle against Steg v1.0.0 produced a TypeError at request time when consumers typehinted the interface in their constructors.

### Documentation
- README now states the minimum required Steg version explicitly.

## [0.1.0] — 2026-05-06

### Added
- `StegBundle` — Symfony Bundle class
- `StegExtension` — DI registration of StegClient services per connection
- `Configuration` — TreeBuilder with DSN/base_url validation and timeout > 0 constraint
- `StegDataCollector` — Symfony Profiler data collector (request count, duration, tokens)
- `ProfilingClient` — Decorating client that records complete() calls to the DataCollector
- Profiler panel template (`templates/data_collector/steg.html.twig`)
- 27 unit tests (ConfigurationTest, StegExtensionTest, StegDataCollectorTest, ProfilingClientTest)
- GitHub Actions CI (PHP 8.2–8.4, PHPUnit, PHPStan Level 9, CS-Fixer)
