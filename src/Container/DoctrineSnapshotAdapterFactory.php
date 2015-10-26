<?php
/*
 * This file is part of the prooph/snapshot-doctrine-adapter.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/20/15 - 19:12
 */

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine\Container;

use Doctrine\DBAL\DriverManager;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;

/**
 * Class DoctrineSnapshotAdapterFactory
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine\Container
 */
final class DoctrineSnapshotAdapterFactory implements RequiresConfig, RequiresMandatoryOptions, ProvidesDefaultOptions
{
    use ConfigurationTrait;

    /**
     * @param ContainerInterface $container
     * @return DoctrineSnapshotAdapter
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $config = $this->options($config)['adapter']['options'];

        if (isset($config['connection_alias']) && $container->has($config['connection_alias'])) {
            $connection = $container->get($config['connection_alias']);
        } elseif (isset($config['connection']) && is_array($config['connection'])) {
            $connection = DriverManager::getConnection($config['connection']);
        }

        if (!isset($connection)) {
            throw ConfigurationException::configurationError(sprintf(
                '%s was not able to locate or create a valid Doctrine\DBAL\Connection',
                __CLASS__
            ));
        }

        return new DoctrineSnapshotAdapter($connection, $config['snapshot_table_map']);
    }

    /**
     * @inheritdoc
     */
    public function vendorName()
    {
        return 'prooph';
    }

    /**
     * @inheritdoc
     */
    public function packageName()
    {
        return 'snapshot_store';
    }

    /**
     * @inheritdoc
     */
    public function mandatoryOptions()
    {
        return ['adapter' => ['options']];
    }

    /**
     * @inheritdoc
     */
    public function defaultOptions()
    {
        return ['adapter' => ['options' => ['snapshot_table_map' => []]]];
    }
}
