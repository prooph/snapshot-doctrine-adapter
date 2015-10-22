<?php
/*
 * This file is part of the prooph/-doctrine-adapter.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/11/15 - 21:37
 */

namespace ProophTest\EventStore\Snapshot\Adapter\Doctrine\Schema;

use Doctrine\DBAL\Schema\Schema;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Snapshot\Adapter\Doctrine\Schema\SnapshotStoreSchema;

/**
 * Class EventStoreSchemaTest
 * @package ProophTest\EventStore\Snapshot\Adapter
 */
final class SnapshotStoreSchemaTest extends TestCase
{
    /**
     * @test
     */
    public function it_drops_snapshot_table()
    {
        $schema = $this->prophesize(Schema::class);
        $schema->dropTable('table_name');

        SnapshotStoreSchema::drop($schema->reveal(), 'table_name');
    }
}
