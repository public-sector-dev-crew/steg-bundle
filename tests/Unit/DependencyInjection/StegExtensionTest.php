<?php

declare(strict_types=1);

namespace Steg\Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Steg\Bundle\DependencyInjection\StegExtension;
use Steg\Bundle\StegBundle;
use Steg\Client\InferenceClientInterface;
use Steg\Factory\StegClientFactory;
use Steg\StegClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class StegExtensionTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function buildContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $bundle = new StegBundle();
        $bundle->build($container);

        $extension = new StegExtension();
        $extension->load([$config], $container);

        return $container;
    }

    public function testSingleConnectionRegistersService(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        self::assertTrue($container->hasDefinition('steg.client.mock'));
    }

    public function testDefaultAliasPointsToFirstConnection(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        self::assertTrue($container->hasAlias('steg.client'));
        self::assertSame('steg.client.mock', (string) $container->getAlias('steg.client'));
    }

    public function testInferenceClientInterfaceAliasIsRegistered(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        self::assertTrue($container->hasAlias(InferenceClientInterface::class));
        self::assertSame('steg.client.mock', (string) $container->getAlias(InferenceClientInterface::class));
    }

    public function testStegClientAliasIsRegistered(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        self::assertTrue($container->hasAlias(StegClient::class));
    }

    public function testExplicitDefaultConnectionIsUsed(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'first' => ['dsn' => 'mock://default?response=first'],
                'second' => ['dsn' => 'mock://default?response=second'],
            ],
            'default_connection' => 'second',
        ]);

        self::assertSame('steg.client.second', (string) $container->getAlias('steg.client'));
        self::assertSame('steg.client.second', (string) $container->getAlias(InferenceClientInterface::class));
    }

    public function testMultipleConnectionsRegisteredAsServices(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'vllm_local' => ['dsn' => 'mock://default?response=vllm'],
                'ollama_dev' => ['dsn' => 'mock://default?response=ollama'],
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        self::assertTrue($container->hasDefinition('steg.client.vllm_local'));
        self::assertTrue($container->hasDefinition('steg.client.ollama_dev'));
        self::assertTrue($container->hasDefinition('steg.client.mock'));
    }

    public function testBaseUrlConnectionRegistersService(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'vllm' => [
                    'base_url' => 'http://localhost:8000/v1',
                    'model' => 'llama-3.3-70b-awq',
                    'timeout' => 60,
                ],
            ],
        ]);

        self::assertTrue($container->hasDefinition('steg.client.vllm'));
        $definition = $container->getDefinition('steg.client.vllm');
        self::assertSame([StegClientFactory::class, 'fromConfig'], $definition->getFactory());
    }

    public function testDsnConnectionUsesFromDsnFactory(): void
    {
        $container = $this->buildContainer([
            'connections' => [
                'mock' => ['dsn' => 'mock://default'],
            ],
        ]);

        $definition = $container->getDefinition('steg.client.mock');
        self::assertSame([StegClientFactory::class, 'fromDsn'], $definition->getFactory());
    }
}
