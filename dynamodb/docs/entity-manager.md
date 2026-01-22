# EntityManager in DynamoPHP

## Overview

The **EntityManager** is the central component in **DynamoPHP’s Object Data Mapper (ODM)**. It acts as a mediator
between your PHP entities and the underlying DynamoDB database. The EntityManager is responsible for performing the core
database
operations such as persisting, retrieving, deleting, and querying entities.

In essence, the EntityManager provides the abstraction layer that handles interaction with the database. Instead of
manually interacting with DynamoDB’s SDK or API, developers work with the EntityManager, which handles the low-level
details of data persistence and retrieval.

## Operations

The EntityManager is responsible for the following tasks:

### Persisting Entities

The EntityManager is responsible for saving entities to DynamoDB. When you call the `put()` method, it will
automatically map the entity’s properties to the correct DynamoDB attributes and insert or update the record in the
corresponding DynamoDB table.

```php
$entityManager->put($user);
```

- The EntityManager examines the entity class to determine how it should be persisted (mapping the properties to the
  DynamoDB attributes).
- It inserts the data if the entity is new or updates it if the entity already exists.

### Retrieving Entities

The EntityManager retrieves entities from DynamoDB. You can retrieve a single entity using the `get()` method, which
retrieves the entity based on its primary key. Additionally, you can execute queries or scans
using `query()`, `queryOne()` and `scan()` to retrieve entities based on conditions.

Find a single entity by primary key:

```php
$user = $entityManager->get(User::class, ['id' => '123']);
```

- This retrieves the User entity with the primary key (id = "123") from the DynamoDB table.

In case the primary key is a composite key, i.e., combines multiple fields, you **must** provide all its fields:

```php
$user = $entityManager->get(User::class, ['id' => '123', 'name' => 'John Doe']);
```

- This retrieves the User entity with the primary key (id = "123" and name = "John Doe") from the DynamoDB table.

Query for multiple entities with conditions:

```php
use OA\Dynamodb\ODM\QueryArgs;

$queryArgs = (new QueryArgs())
    ->keyConditionExpression('id = :id AND begins_with(#name, :name)')
    ->expressionAttributeNames(['#name' => 'name'])
    ->expressionAttributeValues([
        ':id' => '123',
        ':name' => 'J',
    ]);
]);

$stream = $entityManager->query(User::class, $queryArgs);
$users = $stream->getResult(asArray: true);
```

- This retrieves multiple users where the name starts with "J".

Scan all items:

```php
use OA\Dynamodb\ODM\ScanArgs;

$scanArgs = new ScanArgs(); // here you can also define conditions according to your needs

$stream = $entityManager->scan(User::class, $scanArgs);
$users = $stream->getResult(asArray: true);
```

- This performs a full table scan and retrieves all users.

### Deleting Entities

To delete an entity, you use the `delete()` method, passing the entity you wish to delete. The EntityManager will
automatically delete the corresponding record from DynamoDB.

```php
$entityManager->delete($user);
```

### Querying and Scanning

The EntityManager provides powerful methods for querying and scanning DynamoDB tables. You can execute more complex
queries using the `query()` and `scan()` methods.

- **Query**: this method allows you to retrieve items based on specified conditions (such as primary key, range, or
  other conditions).
- **Scan**: this method scans the entire table and filters results based on conditions.

Example of a query with conditions:

```php
use OA\Dynamodb\ODM\QueryArgs;

$queryArgs = (new QueryArgs())
    ->keyConditionExpression('id = :id AND begins_with(#name, :name)')
    ->expressionAttributeNames(['#name' => 'name'])
    ->expressionAttributeValues([
        ':id' => '123',
        ':name' => 'J',
    ]);
]);

$stream = $entityManager->query(User::class, $queryArgs);
$users = $stream->getResult(asArray: true);
```

Example of scanning:

```php
use OA\Dynamodb\ODM\ScanArgs;

$scanArgs = new ScanArgs(); // here you can also define conditions according to your needs

$stream = $entityManager->scan(User::class, $scanArgs);
$users = $stream->getResult(asArray: true);
```

DynamoPHP leverages a fluent API that wraps AWS SDK's API and provides easy-to-use objects that work as query/scan
builders: `QueryArgs` and `ScanArgs`. They provide all available arguments to perform a query or a scan flexibly.

## Configuration

The EntityManager can be configured to connect to a specific DynamoDB instance (either local or AWS-hosted). When
configuring the EntityManager, you provide the connection parameters such as the AWS region, endpoint URL, and
credentials. **DynamoPHP** provides a factory class to facilitate and abstract all that boilerplate code.

Here’s a basic configuration example:

```php
use OA\Dynamodb\ODM\EntityManagerFactory;

$entityManager = EntityManagerFactory::create([
    'region' => 'eu-central-1',
    'endpoint' => 'http://localhost:4566',
    'credentials' => ['key' => 'key', 'secret' => 'secret'],
]);
```

Since DynamoPHP is a layer on top of [AWS PHP SDK](https://aws.amazon.com/sdk-for-php), all client configuration keys
are replicated and therefore supported.

## Best practices for using the EntityManager

- **Use Entities for Strong Typing:** always define your database models using entities. This ensures that you benefit
  from
  type safety, autocompletion, and error reduction in modern IDEs.
- **Avoid Complex Logic in Entities:** keep your entities lightweight and focused on mapping the database schema.
  Business
  logic and complex interactions should be handled outside the entity class.
- **Leverage Queries and Scans:** use the query and scan methods for retrieving data. Queries are faster and more
  efficient
  than scans, so use them when possible.
- **Configure the EntityManager Properly:** when working with different environments (local or AWS-hosted DynamoDB),
  ensure
  the EntityManager is correctly configured for your specific use case.

## Conclusion

The **EntityManager** in **DynamoPHP** serves as the heart of the Object Data Mapper (ODM), enabling you to interact
with DynamoDB in a clean and object-oriented way. It handles all CRUD operations, queries, and scans, making it easy to
manage and persist your entities to DynamoDB. With a simple, intuitive API, the EntityManager abstracts away the
complexity of working with DynamoDB, allowing developers to focus on the business logic of their application.
