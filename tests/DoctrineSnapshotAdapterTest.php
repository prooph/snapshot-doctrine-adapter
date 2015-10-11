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

namespace ProophTest\EventStore\Adpater\MongoDb;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\DoctrineSnapshotAdapter;
use Prooph\EventStore\Snapshot\Snapshot;

/**
 * Class MongoDbAdapterTest
 * @package ProophTest\EventStore\Adpater\Doctrine
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

        $schema = new Schema();
        $table = $schema->createTable('foo_snapshot');
        $table->addColumn('aggregate_type', 'string');
        $table->addColumn('aggregate_id', 'string');
        $table->addColumn('last_version', 'integer');
        $table->addColumn('created_at', 'string');
        $table->addColumn('aggregate_root', 'blob');

        $sqls = $schema->toSql($this->connection->getDatabasePlatform());

        foreach ($sqls as $sql) {
            $this->connection->executeQuery($sql);
        }

        $this->adapter = new DoctrineSnapshotAdapter($this->connection);
    }

    /**
     * @test
     */
    public function it_adds_and_reads()
    {
        $aggregateType = AggregateType::fromString('foo');

        $aggregateRoot = new \stdClass();
        $aggregateRoot->foo = 'bar';

        $time = microtime(true);
        if (false === strpos($time, '.')) {
            $time .= '.0000';
        }
        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 1, $now);

        $this->adapter->add($snapshot);

        $this->assertNull($this->adapter->get($aggregateType, 'invalid'));

        $readSnapshot = $this->adapter->get($aggregateType, 'id');

        $this->assertEquals($snapshot, $readSnapshot);
    }

    /**
     * @test
     */
    public function it_uses_custom_snapshot_grid_fs_map_and_write_concern()
    {
        $schema = new Schema();
        $table = $schema->createTable('bar');
        $table->addColumn('aggregate_type', 'string');
        $table->addColumn('aggregate_id', 'string');
        $table->addColumn('last_version', 'integer');
        $table->addColumn('created_at', 'string');
        $table->addColumn('aggregate_root', 'blob');

        $sqls = $schema->toSql($this->connection->getDatabasePlatform());

        foreach ($sqls as $sql) {
            $this->connection->executeQuery($sql);
        }

        $this->adapter = new DoctrineSnapshotAdapter(
            $this->connection,
            [
                'foo' => 'bar'
            ]
        );

        $aggregateType = AggregateType::fromString('foo');

        $aggregateRoot = new \stdClass();
        $aggregateRoot->foo = 'bar';

        $time = microtime(true);
        if (false === strpos($time, '.')) {
            $time .= '.0000';
        }
        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 1, $now);

        $this->adapter->add($snapshot);

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('bar')
            ->setMaxResults(1);

        $stmt = $queryBuilder->execute();

        $this->assertNotNull($stmt->fetch(\PDO::FETCH_ASSOC));
    }
}
