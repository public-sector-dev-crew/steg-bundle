<?php

declare(strict_types=1);

namespace Steg\Bundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

final class StegDataCollector extends DataCollector
{
    public function __construct()
    {
        $this->reset();
    }

    public function recordRequest(
        string $connection,
        string $model,
        int $durationMs,
        int $promptTokens,
        int $completionTokens,
        string $responsePreview,
    ): void {
        /** @var array{requests: list<array<string, mixed>>, total_duration_ms: int, total_prompt_tokens: int, total_completion_tokens: int} $data */
        $data = $this->data;

        $data['requests'][] = [
            'connection' => $connection,
            'model' => $model,
            'duration_ms' => $durationMs,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'response_preview' => $responsePreview,
            'timestamp' => microtime(true),
        ];
        $data['total_duration_ms'] += $durationMs;
        $data['total_prompt_tokens'] += $promptTokens;
        $data['total_completion_tokens'] += $completionTokens;

        $this->data = $data;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        // Data is collected via recordRequest() during the request lifecycle.
    }

    public function reset(): void
    {
        $this->data = [
            'requests' => [],
            'total_duration_ms' => 0,
            'total_prompt_tokens' => 0,
            'total_completion_tokens' => 0,
        ];
    }

    public function getName(): string
    {
        return 'steg';
    }

    public function getRequestCount(): int
    {
        /** @var array{requests: list<mixed>} $data */
        $data = $this->data;

        return \count($data['requests']);
    }

    public function getTotalDurationMs(): int
    {
        /** @var array{total_duration_ms: int} $data */
        $data = $this->data;

        return $data['total_duration_ms'];
    }

    public function getTotalPromptTokens(): int
    {
        /** @var array{total_prompt_tokens: int} $data */
        $data = $this->data;

        return $data['total_prompt_tokens'];
    }

    public function getTotalCompletionTokens(): int
    {
        /** @var array{total_completion_tokens: int} $data */
        $data = $this->data;

        return $data['total_completion_tokens'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRequests(): array
    {
        /** @var array{requests: list<array<string, mixed>>} $data */
        $data = $this->data;

        return $data['requests'];
    }
}
