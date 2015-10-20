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
use Interop\Config\RequiresContainerId;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;

/**
 * Class DoctrineSnapshotAdapterFactory
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine\Container
 */
final class DoctrineSnapshotAdapterFactory implements RequiresContainerId
{
    use ConfigurationTrait;

    /**
     * @param ContainerInterface $container
     * @return DoctrineSnapshotAdapter
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $snapshotAdapterConfig = $this->optionsWithFallback($config);

        if (!isset($snapshotAdapterConfig['options'])) {
            throw ConfigurationException::configurationError(
                'Snapshot adapter options missing'
            );
        }

        if (!is_array($snapshotAdapterConfig['options'])
            && !$snapshotAdapterConfig['options'] instanceof \ArrayAccess
        ) {
            throw ConfigurationException::configurationError(
                'Snapshot adapter options must be an array or implement ArrayAccess'
            );
        }

        $adapterOptions = $snapshotAdapterConfig['options'];

        if (isset($adapterOptions['connection_alias']) && $container->has($adapterOptions['connection_alias'])) {
            $connection = $container->get($adapterOptions['connection_alias']);
        } elseif (isset($adapterOptions['connection']) && is_array($adapterOptions['connection'])) {
            $connection = DriverManager::getConnection($adapterOptions['connection']);
        }

        if (!isset($connection)) {
            throw ConfigurationException::configurationError(sprintf(
                '%s was not able to locate or create a valid Doctrine\DBAL\Connection',
                __CLASS__
            ));
        }

        $snapshotTableMap = isset($adapterOptions['snapshot_table_map'])
            ? $adapterOptions['snapshot_table_map']
            : [];

        return new DoctrineSnapshotAdapter($connection, $snapshotTableMap);
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
        return 'event_store';
    }

    /**
     * @inheritdoc
     */
    public function containerId()
    {
        return 'snapshot_adapter';
    }
}
