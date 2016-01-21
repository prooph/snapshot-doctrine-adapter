<?php
/*
 * This file is part of the prooph/-doctrine-adapter.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/10/15 - 15:37
 */

namespace ProophTest\EventStore\Snapshot\Adapter\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
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
    public function it_saves_and_reads()
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

        $adapter->save($snapshot);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 2, $now);

        $adapter->save($snapshot);

        $this->assertNull($adapter->get($aggregateType, 'invalid'));

        $readSnapshot = $adapter->get($aggregateType, 'id');

        $this->assertEquals($snapshot, $readSnapshot);

        $statement = $this->connection->prepare('SELECT * FROM foo_snapshot');
        $statement->execute();
        $snapshots = $statement->fetchAll();
        $this->assertCount(1, $snapshots);
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

        $adapter->save($snapshot);

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('bar', 'bar')
            ->setMaxResults(1);

        $stmt = $queryBuilder->execute();

        $this->assertNotNull($stmt->fetch(\PDO::FETCH_ASSOC));
    }

    /**
     * @test
     */
    public function it_deals_with_resources_on_serialized_aggregate_roots()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder */
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        /** @var Statement|\PHPUnit_Framework_MockObject_MockObject $stmt */
        $stmt = $this->getMockForAbstractClass(Statement::class);

        $aggregateRoot = new \stdClass();
        $aggregateRoot->data = 'AggregateRoot';

        $resource = fopen('php://temp', 'r+');
        fwrite($resource, serialize($aggregateRoot));
        fseek($resource, 0);

        $connection->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('select')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->willReturnSelf();
        $queryBuilder->expects($this->any())->method('where')->willReturnSelf();
        $queryBuilder->expects($this->any())->method('andWhere')->willReturnSelf();
        $queryBuilder->expects($this->any())->method('orderBy')->willReturnSelf();
        $queryBuilder->expects($this->any())->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->any())->method('setMaxResults')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('execute')->willReturn($stmt);

        $stmt->expects($this->once())->method('fetch')->with(\PDO::FETCH_ASSOC)->willReturn([
            'aggregate_root' => $resource,
            'last_version'   => 3,
            'created_at'     => '2016-01-21T09:33:00.000',
        ]);

        $adapter = new DoctrineSnapshotAdapter($connection, ['foo' => 'bar']);

        $snapshot = $adapter->get(AggregateType::fromString('foo'), 'some-uuid-non-important-here');

        $this->assertEquals('AggregateRoot', $snapshot->aggregateRoot()->data);
    }
}
