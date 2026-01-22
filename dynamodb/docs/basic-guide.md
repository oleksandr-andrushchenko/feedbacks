# Basic Usage Guide

**DynamoPHP** is a lightweight, attribute-based _Object Data Mapper_ designed to simplify development with Amazon
DynamoDB. Inspired by modern data-mapping libraries, it brings a familiar, expressive API to PHP for modeling DynamoDB
entities.

## Contents

- [Installation](#installation)
- [Concepts](#concepts)
- [Getting Started](#getting-started)

## Installation

Install **DynamoPHP** via Composer:

```bash
composer require edumarques/dynamophp
```

## Concepts

### Entity

An Entity in **DynamoPHP** is a PHP class that maps to a single item (also known as document or row) in a DynamoDB
table. Entities are defined using PHP attributes.

### Attribute

An Attribute is an item's property (also known as field or column) that should be persisted in a DynamoDB table.
Attributes are defined using PHP attributes.

### PartitionKey and SortKey

Compose the Primary Key of items, and reference how items are stored in and retrieved from the table. They are defined
by fields in Entity's PHP attribute.

### Entity Manager

The Entity Manager is the main interface for performing operations like saving, fetching, querying, and deleting items
from DynamoDB.

## Getting Started

To get started with **DynamoPHP**, you basically need to:

1. Define your entity model using attributes.
2. Create an instance of the EntityManager.
3. Use the entity manager to read/write data.

Here is an example of a simple entity:

```php
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    table: 'users',
    partitionKey: new PartitionKey(['id']),
    sortKey: new SortKey(['createdAt'])
)]
class User
{
    #[Attribute]
    public string $id;
    
    #[Attribute(name: 'name')]
    public string $fullName;

    #[Attribute]
    public DateTimeInterface $createdAt;
}
```

Each entity must:

- Declare a table name.
- Define a partition key and, if the table schema requires, a sort key, via PartitionKey and SortKey objects in their
  respective fields.
- Optionally, set persistent properties with #[Attribute].

Create an instance of EntityManager using the factory:

```php
use OA\Dynamodb\ODM\EntityManagerFactory;

$entityManager = EntityManagerFactory::create([
    'region' => 'eu-central-1',
    'endpoint' => 'http://localhost:4566',
    'credentials' => ['key' => 'key', 'secret' => 'secret'],
]);
```

This internally creates a DynamoDB client using the AWS SDK and wires up all dependencies.

Run the operations you need, for instance:

```php
// Create/update entity
$user = new User();
$user->id = 'user-123';
$user->fullName = 'John Doe';
$user->createdAt = new DateTime();

$entityManager->put($user);

// Fetch entity by primary key
$entity = $entityManager->get(User::class, [
    'id' => 'user-123',
    'createdAt' => new DateTime(),
]);
```

That's it! The Entity Manager will handle all the wiring of dependencies and operations, including serialization and
communication with the DynamoDB client.
