<?php

// SPDX-License-Identifier: EUPL-1.2
// SPDX-FileCopyrightText: 2026 public-sector-dev-crew

declare(strict_types=1);

namespace Steg\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Steg\Bundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function testMinimalDsnConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'default' => ['dsn' => 'mock://default'],
            ],
        ]]);

        $default = self::connection($config, 'default');
        self::assertSame('mock://default', $default['dsn']);
        self::assertSame(120, $default['timeout']);
        self::assertSame('EMPTY', $default['api_key']);
        self::assertNull($config['default_connection']);
    }

    public function testBaseUrlConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'vllm' => [
                    'base_url' => 'http://localhost:8000/v1',
                    'model' => 'llama-3.3-70b-awq',
                    'timeout' => 60,
                ],
            ],
        ]]);

        $vllm = self::connection($config, 'vllm');
        self::assertSame('http://localhost:8000/v1', $vllm['base_url']);
        self::assertSame('llama-3.3-70b-awq', $vllm['model']);
        self::assertSame(60, $vllm['timeout']);
    }

    public function testMultipleConnectionsWithDefaultConnection(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'vllm_local' => ['dsn' => 'vllm://localhost:8000/v1?model=llama'],
                'mock' => ['dsn' => 'mock://default'],
            ],
            'default_connection' => 'vllm_local',
        ]]);

        self::assertSame('vllm_local', $config['default_connection']);
        self::assertCount(2, self::connections($config));
    }

    public function testTimeoutMustBeGreaterThanZero(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'default' => ['dsn' => 'mock://default', 'timeout' => 0],
            ],
        ]]);
    }

    public function testMissingDsnAndBaseUrlThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"dsn" or "base_url"');

        $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'broken' => ['model' => 'some-model'],
            ],
        ]]);
    }

    public function testBaseUrlWithoutModelThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"model"');

        $this->processor->processConfiguration($this->configuration, [[
            'connections' => [
                'broken' => ['base_url' => 'http://localhost:8000/v1'],
            ],
        ]]);
    }

    public function testEmptyConnectionsThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processor->processConfiguration($this->configuration, [[
            'connections' => [],
        ]]);
    }

    /**
     * Narrowt die "connections"-Liste aus dem prozessierten Config-Baum
     * (Processor::processConfiguration liefert untypisiertes array<mixed>).
     *
     * @param array<array-key, mixed> $config
     *
     * @return array<array-key, mixed>
     */
    private static function connections(array $config): array
    {
        $connections = $config['connections'] ?? null;
        if (!\is_array($connections)) {
            self::fail('Processed config is missing the "connections" array.');
        }

        return $connections;
    }

    /**
     * Narrowt eine einzelne Connection aus dem prozessierten Config-Baum.
     *
     * @param array<array-key, mixed> $config
     *
     * @return array<array-key, mixed>
     */
    private static function connection(array $config, string $name): array
    {
        $connection = self::connections($config)[$name] ?? null;
        if (!\is_array($connection)) {
            self::fail(\sprintf('Connection "%s" is not an array in processed config.', $name));
        }

        return $connection;
    }
}
