# Roadmap — lotse/steg-bundle

## Open

### Integration Tests (Bundle-level)
**Status:** Pending — `tests/Integration/` exists but is empty; suite removed from `phpunit.xml.dist` on 2026-03-27.

**Scope:**
- Bootstrap a minimal Symfony Kernel with `StegBundle` registered
- Assert DI container wires `StegClient` / `InferenceClientInterface` correctly
- Assert tagged services, compiler passes, and bundle configuration are processed without errors
- Use `symfony/framework-bundle` + `symfony/test-pack` (dev deps)

**Why it was deferred:** No integration tests existed yet; the empty directory caused CI to fail (`Test directory not found`). The Unit suite covers all current functionality.

**When to tackle:** After the bundle reaches feature-complete state (autowiring, semantic config, optional Monolog integration).

---

## Done

### v0.1.x — Initial scaffold + CI fix
- Bundle skeleton, DI extension, Unit tests
- CI matrix restricted to PHP 8.4 (mirrors `lotse/steg` requirement)
- `php` constraint in `composer.json` corrected to `>=8.4`
