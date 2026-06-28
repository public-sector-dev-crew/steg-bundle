<?php

declare(strict_types=1);

namespace Steg\Bundle\Client;

use Steg\Bundle\DataCollector\StegDataCollector;
use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionResponse;
use Steg\Model\ModelInfo;
use Steg\Model\StreamChunk;

/**
 * Decorates InferenceClientInterface to collect profiling data.
 *
 * Wraps every complete() call and records duration, token usage, and connection name.
 * Forwarded to StegDataCollector for display in the Symfony Profiler.
 */
final class ProfilingClient implements InferenceClientInterface
{
    public function __construct(
        private readonly InferenceClientInterface $inner,
        private readonly StegDataCollector $collector,
        private readonly string $connectionName,
    ) {
    }

    /**
     * @param list<ChatMessage> $messages
     */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse
    {
        $start = microtime(true);
        $response = $this->inner->complete($messages, $options);
        $duration = (int) round((microtime(true) - $start) * 1000);

        $this->collector->recordRequest(
            connection: $this->connectionName,
            model: $response->model,
            durationMs: $duration,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            responsePreview: mb_substr($response->content, 0, 100),
        );

        return $response;
    }

    /**
     * @param list<ChatMessage> $messages
     *
     * @return \Generator<int, StreamChunk, mixed, void>
     */
    public function stream(array $messages, ?CompletionOptions $options = null): \Generator
    {
        return $this->inner->stream($messages, $options);
    }

    /**
     * @return list<ModelInfo>
     */
    public function listModels(): array
    {
        return $this->inner->listModels();
    }

    public function isHealthy(): bool
    {
        return $this->inner->isHealthy();
    }
}
