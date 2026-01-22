DynamoPHP
================
![PHP Version](https://img.shields.io/packagist/dependency-v/edumarques/dynamophp/php?version=dev-main&color=%23777BB3)
![License](https://img.shields.io/github/license/edumarques/dynamophp)
![Build Status](https://github.com/edumarques/dynamophp/actions/workflows/base.yml/badge.svg)
![Coverage](https://codecov.io/gh/edumarques/dynamophp/graph/badge.svg?token=E20936W7JD)

---

**DynamoPHP** is a strongly-typed, attribute-based Object Data Mapper for Amazon DynamoDB. It is built on top of modern
PHP, which enables definition of entities using PHP 8+ attributes and interaction with DynamoDB using a clean, expressive
API.

Inspired by data mappers like [Doctrine](https://www.doctrine-project.org) and modeled after patterns in libraries such
as [TypeDORM](https://github.com/typedorm/typedorm).

## Features

- **Entity Management:** automatically handles CRUD operations.
- **Index Support:** work with Global Secondary Indexes (GSI) and Local Secondary Indexes (LSI).
- **Query Builder:** build complex queries with minimal boilerplate.

## Installation

You can install **DynamoPHP** via Composer:

```shell
composer require edumarques/dynamophp
```

## Documentation

For detailed usage, please refer to the sections below.

Quickly get started:

- [Basic Usage Guide](docs/basic-guide.md)

Learn how DynamoPHP works as an ODM for mapping your objects to DynamoDB:

- [Object Data Mapping](docs/odm.md)

Learn how to define and manage your entities:

- [Entity](docs/entity.md)

Learn how to use Global Secondary Indexes (GSI) and Local Secondary Indexes (LSI):

- [Indexes](docs/indexes.md)

Learn how the EntityManager is used for managing entities and interactions with DynamoDB:

- [Entity Manager](docs/entity-manager.md)

_Documentation is an ongoing effort. Our docs will continue to evolve as the project grows — contributions and
improvements are welcome and encouraged!_

## Contributing

Contributors are always welcome! For more information on how you can contribute, please read
our [contribution guideline](CONTRIBUTING.md).

For any questions, feel free to reach out to me directly by
email: [eduardomarqs1@gmail.com](mailto:eduardomarqs1@gmail.com).

## Integration

For seamless integration with [Symfony](https://symfony.com) applications, a dedicated **Symfony Bundle** is
available: [edumarques/dynamophp-symfony](https://github.com/edumarques/dynamophp-symfony).
