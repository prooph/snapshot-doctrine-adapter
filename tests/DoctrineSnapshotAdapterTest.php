<?php
/*
 * This file is part of the prooph/service-bus.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/10/15 - 15:37
 */

namespace ProophTest\EventStore\Snapshot\Adapter\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema\SnapshotStoreSchema;
use Prooph\EventStore\Snapshot\Snapshot;

/**
 * Class DoctrineSnapshotAdapterTest
 * @package ProophTest\EventStore\Snapshot\Adapter\Doctrine
 */
final class DoctrineSnapshotAdapterTest extends TestCase
{
    /**
     * @var DoctrineSnapshotAdapter
     */
    private $adapter;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        $connection = [
            'driver' => 'pdo_sqlite',
            'dbname' => ':memory:'
        ];

        $this->connection = DriverManager::getConnection($connection);
    }

    /**
     * @test
     */
    public function it_adds_and_reads()
    {
        $schema = new Schema();

        SnapshotStoreSchema::create($schema, 'foo_snapshot');

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeQuery($sql);
        }

        $adapter = new DoctrineSnapshotAdapter($this->connection);

        $aggregateType = AggregateType::fromString('foo');

        $aggregateRoot = new \stdClass();
        $aggregateRoot->foo = 'bar';

        $time = microtime(true);
        if (false === strpos($time, '.')) {
            $time .= '.0000';
        }
        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 1, $now);

        $adapter->add($snapshot);

        $this->assertNull($adapter->get($aggregateType, 'invalid'));

        $readSnapshot = $adapter->get($aggregateType, 'id');

        $this->assertEquals($snapshot, $readSnapshot);
    }

    /**
     * @test
     */
    public function it_uses_custom_snapshot_table_map()
    {
        $schema = new Schema();

        SnapshotStoreSchema::create($schema, 'bar');

        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
            $this->connection->executeQuery($sql);
        }

        $adapter = new DoctrineSnapshotAdapter($this->connection, ['foo' => 'bar']);

        $aggregateType = AggregateType::fromString('foo');

        $aggregateRoot = new \stdClass();
        $aggregateRoot->foo = 'bar';

        $time = microtime(true);
        if (false === strpos($time, '.')) {
            $time .= '.0000';
        }
        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 1, $now);

        $adapter->add($snapshot);

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('bar', 'bar')
            ->setMaxResults(1);

        $stmt = $queryBuilder->execute();

        $this->assertNotNull($stmt->fetch(\PDO::FETCH_ASSOC));
    }
}
