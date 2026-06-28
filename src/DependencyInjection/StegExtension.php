<?php

declare(strict_types=1);

namespace Steg\Bundle\DependencyInjection;

use Steg\Client\InferenceClientInterface;
use Steg\Factory\StegClientFactory;
use Steg\StegClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class StegExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** @var array<string, array{dsn: string|null, base_url: string|null, model: string|null, api_key: string, timeout: int}> $connections */
        $connections = $config['connections'];

        $connectionNames = array_keys($connections);
        $defaultConnection = \is_string($config['default_connection'] ?? null)
            ? $config['default_connection']
            : $connectionNames[0];

        foreach ($connections as $name => $connectionConfig) {
            $serviceId = \sprintf('steg.client.%s', $name);

            if (null !== $connectionConfig['dsn']) {
                $definition = new Definition(StegClient::class);
                $definition->setFactory([StegClientFactory::class, 'fromDsn']);
                $definition->setArguments([$connectionConfig['dsn']]);
            } else {
                /** @var string $baseUrl */
                $baseUrl = $connectionConfig['base_url'];
                /** @var string $model */
                $model = $connectionConfig['model'];

                $definition = new Definition(StegClient::class);
                $definition->setFactory([StegClientFactory::class, 'fromConfig']);
                $definition->setArguments([[
                    'base_url' => $baseUrl,
                    'model' => $model,
                    'api_key' => $connectionConfig['api_key'],
                    'timeout' => $connectionConfig['timeout'],
                ]]);
            }

            $definition->setPublic(false);
            $container->setDefinition($serviceId, $definition);
        }

        // Default connection aliases
        $defaultServiceId = \sprintf('steg.client.%s', $defaultConnection);
        $container->setAlias('steg.client', $defaultServiceId)->setPublic(true);
        $container->setAlias(StegClient::class, $defaultServiceId)->setPublic(false);
        $container->setAlias(InferenceClientInterface::class, $defaultServiceId)->setPublic(false);
    }
}
