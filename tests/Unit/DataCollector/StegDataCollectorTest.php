<?php

declare(strict_types=1);

namespace Steg\Bundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Steg\Bundle\DataCollector\StegDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StegDataCollectorTest extends TestCase
{
    public function testInitialStateIsEmpty(): void
    {
        $collector = new StegDataCollector();

        self::assertSame(0, $collector->getRequestCount());
        self::assertSame(0, $collector->getTotalDurationMs());
        self::assertSame(0, $collector->getTotalPromptTokens());
        self::assertSame(0, $collector->getTotalCompletionTokens());
        self::assertSame([], $collector->getRequests());
    }

    public function testRecordRequestStoresData(): void
    {
        $collector = new StegDataCollector();

        $collector->recordRequest(
            connection: 'vllm_local',
            model: 'llama-3.3-70b-awq',
            durationMs: 350,
            promptTokens: 120,
            completionTokens: 45,
            responsePreview: 'Hello, world!',
        );

        self::assertSame(1, $collector->getRequestCount());
        self::assertSame(350, $collector->getTotalDurationMs());
        self::assertSame(120, $collector->getTotalPromptTokens());
        self::assertSame(45, $collector->getTotalCompletionTokens());

        $requests = $collector->getRequests();
        self::assertCount(1, $requests);
        self::assertSame('vllm_local', $requests[0]['connection']);
        self::assertSame('llama-3.3-70b-awq', $requests[0]['model']);
        self::assertSame(350, $requests[0]['duration_ms']);
        self::assertSame('Hello, world!', $requests[0]['response_preview']);
    }

    public function testMultipleRequestsAccumulateTotals(): void
    {
        $collector = new StegDataCollector();

        $collector->recordRequest('conn_a', 'model-a', 100, 50, 20, 'first');
        $collector->recordRequest('conn_b', 'model-b', 200, 80, 30, 'second');

        self::assertSame(2, $collector->getRequestCount());
        self::assertSame(300, $collector->getTotalDurationMs());
        self::assertSame(130, $collector->getTotalPromptTokens());
        self::assertSame(50, $collector->getTotalCompletionTokens());
    }

    public function testResetClearsAllData(): void
    {
        $collector = new StegDataCollector();
        $collector->recordRequest('conn', 'model', 100, 10, 5, 'preview');

        $collector->reset();

        self::assertSame(0, $collector->getRequestCount());
        self::assertSame(0, $collector->getTotalDurationMs());
        self::assertSame([], $collector->getRequests());
    }

    public function testGetNameReturnsSteg(): void
    {
        $collector = new StegDataCollector();

        self::assertSame('steg', $collector->getName());
    }

    public function testCollectDoesNothing(): void
    {
        $collector = new StegDataCollector();
        $collector->recordRequest('conn', 'model', 100, 10, 5, 'preview');

        // collect() is a no-op — data is recorded via recordRequest()
        $collector->collect(new Request(), new Response());

        self::assertSame(1, $collector->getRequestCount());
    }
}
