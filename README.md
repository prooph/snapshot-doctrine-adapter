# snapshot-doctrine-adapter

Doctrine Adapter for the Snapshot Store

[![Build Status](https://travis-ci.org/prooph/snapshot-doctrine-adapter.svg?branch=master)](https://travis-ci.org/prooph/snapshot-doctrine-adapter)
[![Coverage Status](https://coveralls.io/repos/prooph/snapshot-doctrine-adapter/badge.svg?branch=master&service=github)](https://coveralls.io/github/prooph/snapshot-doctrine-adapter?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

## Set Up

How to use the adapter is explained in the [prooph/event-store docs](https://github.com/prooph/event-store/blob/master/docs/snapshots.md). To help you with setting up the snapshot tables we ship a [SnapshotStoreSchema](src/Schema/SnapshotStoreSchema.php) helper with the package. You can use it in a doctrine migrations script or manually by passing in a `Doctrine\DBAL\Schema\Schema` and executing the generated SQL afterwards.

## Interop Factory

Some general notes about how to use interop factories shipped with prooph components can be found in the [event store docs](https://github.com/prooph/event-store/blob/master/docs/interop_factories.md).
Use the [doctrine snapshot adapter factory](src/Container/DoctrineSnapshotAdapterFactory.php) to set up the adapter. If your IoC container supports callable factories
you can register the factory under a service id of your choice and configure this service id as `$config['prooph']['snapshot_store']['adpater']['type'] = <adapter_service_id>`.

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- File issues at [https://github.com/prooph/snapshot-doctrine-adapter/issues](https://github.com/prooph/snapshot-doctrine-adapter/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

# Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

# Dependencies

Please refer to the project [composer.json](composer.json) for the list of dependencies.

# License

Released under the [New BSD License](LICENSE).
