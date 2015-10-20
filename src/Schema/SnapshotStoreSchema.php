<?php
/*
 * This file is part of prooph/snapshot-doctrine-adapter.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/11/15 - 9:22 PM
 */

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema;

use Doctrine\DBAL\Schema\Schema;

/**
 * Class SnapshotStoreSchema
 *
 * Use this helper in a doctrine migrations script to set up the snapshot store schema
 *
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema
 */
final class SnapshotStoreSchema
{
    /**
     * Use this method when you work with a single stream strategy
     *
     * @param Schema $schema
     * @param string $snapshotName Defaults to 'snapshot'
     */
    public static function create(Schema $schema, $snapshotName = 'snapshot')
    {
        $snapshot = $schema->createTable($snapshotName);

        $snapshot->addColumn('aggregate_type', 'string');
        $snapshot->addColumn('aggregate_id', 'string');
        $snapshot->addColumn('last_version', 'integer');
        $snapshot->addColumn('created_at', 'string');
        $snapshot->addColumn('aggregate_root', 'blob');

        $snapshot->addIndex(['aggregate_type', 'aggregate_id']);
    }

    /**
     * Drop a stream schema
     *
     * @param Schema $schema
     * @param string $snapshotName Defaults to 'snapshot'
     */
    public static function dropStream(Schema $schema, $snapshotName = 'snapshot')
    {
        $schema->dropTable($snapshotName);
    }
}
