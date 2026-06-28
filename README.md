# steg-bundle

**Symfony Bundle for [Steg](https://github.com/public-sector-dev-crew/lotse-fleet) — auto-configuration, profiler panel, and DI integration.**

[![CI](https://github.com/public-sector-dev-crew/lotse-fleet/actions/workflows/ci.yml/badge.svg)](https://github.com/public-sector-dev-crew/lotse-fleet/actions)
[![License: EUPL-1.2](https://img.shields.io/badge/License-EUPL--1.2-blue.svg)](https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12)

## Requirements

- PHP 8.2+
- Symfony 7.0+ or 8.0+
- [lotse/steg](https://github.com/public-sector-dev-crew/lotse-fleet) ^1.0.1 (v1.0.0 has an incompatible `StegClient` and is not supported)

## Installation

```bash
composer require lotse/steg-bundle
```

Symfony Flex will register the bundle automatically. Without Flex, add it manually:

```php
// config/bundles.php
return [
    // ...
    Steg\Bundle\StegBundle::class => ['all' => true],
];
```

## Configuration

Create `config/packages/steg.yaml`:

```yaml
steg:
    connections:
        vllm_local:
            dsn: '%env(STEG_VLLM_DSN)%'   # e.g. vllm://gpu-server:8000/v1?model=llama-3.3-70b-awq
            timeout: 120
        ollama_dev:
            dsn: 'ollama://localhost:11434?model=qwen2.5:7b'
            timeout: 60
        mock:
            dsn: 'mock://default'
    default_connection: vllm_local
```

### DSN format

| Scheme | Example |
|--------|---------|
| `vllm://` | `vllm://gpu-server:8000/v1?model=llama-3.3-70b-awq` |
| `ollama://` | `ollama://localhost:11434?model=qwen2.5:7b` |
| `mock://` | `mock://default` |

### Alternative: explicit base_url

```yaml
steg:
    connections:
        vllm_local:
            base_url: '%env(VLLM_BASE_URL)%'
            model: '%env(VLLM_MODEL_NAME)%'
            api_key: '%env(VLLM_API_KEY)%'
            timeout: 120
```

## Usage

### Autowiring (default connection)

```php
use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;

final class MyService
{
    public function __construct(
        private readonly InferenceClientInterface $steg,
    ) {}

    public function translate(string $text): string
    {
        $response = $this->steg->complete([
            ChatMessage::system('You are a translation assistant.'),
            ChatMessage::user($text),
        ]);

        return $response->content;
    }
}
```

### Named connections via `#[Autowire]`

```php
use Steg\Client\InferenceClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MyService
{
    public function __construct(
        #[Autowire(service: 'steg.client.vllm_local')]
        private readonly InferenceClientInterface $vllm,

        #[Autowire(service: 'steg.client.ollama_dev')]
        private readonly InferenceClientInterface $ollama,
    ) {}
}
```

### Available service IDs

| Service ID | Description |
|------------|-------------|
| `steg.client.{name}` | Named connection client |
| `steg.client` | Alias for the default connection |
| `Steg\Client\InferenceClientInterface` | Alias for the default connection |

## Symfony Profiler

When `StegDataCollector` is wired, the Symfony Profiler shows a **Steg** panel with:

- Request count and total duration
- Prompt and completion token usage
- Per-request timeline (connection, model, duration, tokens, response preview)

To enable profiling, decorate your client services with `ProfilingClient` in the DI configuration.

## License

EUPL-1.2 — see [LICENSE](LICENSE).

---

Built by 👾 public sector dev crew

## Notice

This repository was developed with the assistance of AI code agents (Claude Code, Anthropic).
The code was created as part of a development sprint and is not cleared for production use without prior review.
Use at your own risk.

**License:** European Union Public Licence v. 1.2 (EUPL-1.2) — Copyright © 2026 Andreas Teumer
