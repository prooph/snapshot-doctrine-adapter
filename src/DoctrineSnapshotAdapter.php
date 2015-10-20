<?php
/*
 * This file is part of the prooph/-doctrine-adapter.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/11/15 - 14:04
 */

namespace Prooph\EventStore\Snapshot\Adapter\Doctrine;

use Doctrine\DBAL\Connection;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Snapshot\Adapter\Adapter;
use Prooph\EventStore\Snapshot\Snapshot;

/**
 * Class DoctrineSnapshotAdapter
 * @package Prooph\EventStore\Snapshot\Adapter\Doctrine
 */
final class DoctrineSnapshotAdapter implements Adapter
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Custom sourceType to snapshot mapping
     *
     * @var array
     */
    private $snapshotTableMap = [];

    /**
     * @param Connection $connection
     * @param array $snapshotTableMap
     */
    public function __construct(Connection $connection, array $snapshotTableMap = [])
    {
        $this->connection = $connection;
        $this->snapshotTableMap = $snapshotTableMap;
    }

    /**
     * Get the aggregate root if it exists otherwise null
     *
     * @param AggregateType $aggregateType
     * @param string $aggregateId
     * @return Snapshot
     */
    public function get(AggregateType $aggregateType, $aggregateId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $table = $this->getTable($aggregateType);
        $queryBuilder
            ->select('*')
            ->from($table, $table)
            ->where('aggregate_type = :aggregate_type')
            ->andWhere('aggregate_id = :aggregate_id')
            ->orderBy('last_version', 'DESC')
            ->setParameter('aggregate_type', $aggregateType->toString())
            ->setParameter('aggregate_id', $aggregateId)
            ->setMaxResults(1);

        $stmt = $queryBuilder->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return;
        }

        return new Snapshot(
            $aggregateType,
            $aggregateId,
            unserialize($result['aggregate_root']),
            (int) $result['last_version'],
            \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $result['created_at'], new \DateTimeZone('UTC'))
        );
    }

    /**
     * Add a snapshot
     *
     * @param Snapshot $snapshot
     * @return void
     */
    public function add(Snapshot $snapshot)
    {
        $table = $this->getTable($snapshot->aggregateType());

        $this->connection->insert(
            $table,
            [
                'aggregate_type' => $snapshot->aggregateType()->toString(),
                'aggregate_id' => $snapshot->aggregateId(),
                'last_version' => $snapshot->lastVersion(),
                'created_at' => $snapshot->createdAt()->format('Y-m-d\TH:i:s.u'),
                'aggregate_root' => serialize($snapshot->aggregateRoot()),
            ],
            [
                'string',
                'string',
                'integer',
                'string',
                'blob',
            ]
        );
    }

    /**
     * Get table name for given aggregate type
     *
     * @param AggregateType $aggregateType
     * @return string
     */
    private function getTable(AggregateType $aggregateType)
    {
        if (isset($this->snapshotTableMap[$aggregateType->toString()])) {
            $tableName = $this->snapshotTableMap[$aggregateType->toString()];
        } else {
            $tableName = strtolower($this->getShortAggregateTypeName($aggregateType));
            if (strpos($tableName, "_snapshot") === false) {
                $tableName.= "_snapshot";
            }
        }
        return $tableName;
    }

    /**
     * @param AggregateType $aggregateType
     * @return string
     */
    private function getShortAggregateTypeName(AggregateType $aggregateType)
    {
        $streamName = str_replace('-', '_', $aggregateType->toString());
        return implode('', array_slice(explode('\\', $streamName), -1));
    }
}
