# Object Data Mapping (ODM) in DynamoPHP

## Overview

**Object Data Mapping (ODM)** is a design pattern that allows developers to work with data in a database using
object-oriented programming principles. In the context of DynamoPHP, ODM is a mechanism that enables you to map data
from **Amazon DynamoDB** (a NoSQL database) into **PHP objects**.

With an ODM, you can interact with your database like you would with any other object in PHP. Instead of working with
arrays or raw database records, you manipulate instances of **entities**. The ODM handles the complexity of translating
between the object model and the underlying database.

In **DynamoPHP**, the ODM allows you to define entities (as PHP classes), persist those entities to DynamoDB, and
retrieve them from the database while interacting with them as objects rather than low-level data structures.

## Key concepts in ODM

### Entity Mapping

The most crucial aspect of ODM is **entity mapping**. An entity is a PHP class that represents a table in the database.
Each instance of an entity corresponds to a row in the table, and the properties of the entity map to the fields of the
DynamoDB record.

With **DynamoPHP**, you define entities using **PHP 8 attributes** such as _Entity_ and _Attribute_, which indicate how
the class and its properties should map to the DynamoDB schema.

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

- **Table Mapping**: the _Entity_ attribute maps the class to a DynamoDB table.
- **Attribute Mapping**: the _Attribute_ attribute is used to map each property to a DynamoDB attribute.

### Entity Manager

The **Entity Manager** is the core service that orchestrates the interaction between your **PHP objects** and
**DynamoDB**. It handles tasks like persisting objects, retrieving them, deleting records, and running queries.

The Entity Manager in **DynamoPHP** handles operations such as:

- **Put**: persist entities to DynamoDB.
- **Get**: retrieve entities by primary key.
- **Delete**: remove entities from DynamoDB.
- **Query** and **Scan**: execute queries and scans to retrieve data based on certain criteria.

Example of persisting an entity:

```php
$entityManager->put($user);
```

## Benefits of using ODM in DynamoPHP

### Abstraction of database operations

The main benefit of using **ODM** is that it abstracts away the complexity of dealing with **DynamoDB** directly.
Instead of writing raw queries or managing the low-level details of interacting with DynamoDB, you work with simple,
intuitive **PHP objects**.

### Type safety and autocompletion

By using entities with strongly-typed properties, developers benefit from type safety and autocompletion in modern IDEs.
This reduces the likelihood of errors during development and enhances the overall developer experience.

### Cleaner and more readable code

With **ODM**, you don’t need to manually map data between your database and application. This results in cleaner and
more maintainable code because you can focus on business logic rather than database interactions.

### Query and Scan abstraction

**DynamoPHP’s ODM** provides an abstraction layer for querying and scanning the database. You can use higher-level
methods like `query()` and `scan()` to fetch data without having to worry too much about DynamoDB’s underlying API
syntax.

### Easy integration with other frameworks

**DynamoPHP** is flexible and can easily be integrated with other PHP frameworks like _Symfony_ or _Laravel_. You can
utilize the ODM alongside your existing application structure.

## Conclusion

The **Object Data Mapper (ODM)** in **DynamoPHP** enables developers to work with **Amazon DynamoDB** through the use of
**PHP entities**, making database interactions more intuitive and object-oriented. By abstracting database operations,
ODM provides a higher level of abstraction and better developer experience, allowing for clean, maintainable code while
working with **DynamoDB**.
