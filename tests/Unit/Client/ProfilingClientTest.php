<?php

declare(strict_types=1);

namespace Steg\Bundle\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Steg\Bundle\Client\ProfilingClient;
use Steg\Bundle\DataCollector\StegDataCollector;
use Steg\Client\MockClient;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

final class ProfilingClientTest extends TestCase
{
    public function testCompleteRecordsRequest(): void
    {
        $collector = new StegDataCollector();
        $inner = new MockClient(response: 'Hello from mock!', model: 'mock-model');
        $client = new ProfilingClient($inner, $collector, 'test_connection');

        $response = $client->complete([ChatMessage::user('Hi')]);

        self::assertSame('Hello from mock!', $response->content);
        self::assertSame(1, $collector->getRequestCount());

        $requests = $collector->getRequests();
        self::assertSame('test_connection', $requests[0]['connection']);
        self::assertSame('mock-model', $requests[0]['model']);
        self::assertGreaterThanOrEqual(0, $requests[0]['duration_ms']);
    }

    public function testCompleteWithOptionsRecordsRequest(): void
    {
        $collector = new StegDataCollector();
        $inner = new MockClient(response: 'Precise response');
        $client = new ProfilingClient($inner, $collector, 'vllm_local');

        $client->complete([ChatMessage::user('test')], CompletionOptions::precise());

        self::assertSame(1, $collector->getRequestCount());
    }

    public function testMultipleCompletesAccumulate(): void
    {
        $collector = new StegDataCollector();
        $inner = MockClient::withResponses(['first', 'second', 'third']);
        $client = new ProfilingClient($inner, $collector, 'mock');

        $client->complete([ChatMessage::user('q1')]);
        $client->complete([ChatMessage::user('q2')]);
        $client->complete([ChatMessage::user('q3')]);

        self::assertSame(3, $collector->getRequestCount());
    }

    public function testStreamDelegatesWithoutRecording(): void
    {
        $collector = new StegDataCollector();
        $inner = new MockClient(response: 'streamed content');
        $client = new ProfilingClient($inner, $collector, 'mock');

        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));

        self::assertNotEmpty($chunks);
        // stream() does not record to profiler (no full response available)
        self::assertSame(0, $collector->getRequestCount());
    }

    public function testIsHealthyDelegatesToInner(): void
    {
        $collector = new StegDataCollector();
        $inner = new MockClient();
        $client = new ProfilingClient($inner, $collector, 'mock');

        self::assertTrue($client->isHealthy());
    }

    public function testListModelsDelegatesToInner(): void
    {
        $collector = new StegDataCollector();
        $inner = new MockClient();
        $client = new ProfilingClient($inner, $collector, 'mock');

        $models = $client->listModels();

        self::assertCount(1, $models);
    }

    public function testResponsePreviewIsTruncatedAt100Chars(): void
    {
        $longResponse = str_repeat('a', 200);
        $collector = new StegDataCollector();
        $inner = new MockClient(response: $longResponse);
        $client = new ProfilingClient($inner, $collector, 'mock');

        $client->complete([ChatMessage::user('test')]);

        $requests = $collector->getRequests();
        $preview = $requests[0]['response_preview'];
        self::assertIsString($preview);
        self::assertSame(100, mb_strlen($preview));
    }
}
