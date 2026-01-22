# Indexes in DynamoPHP

## Overview

In **DynamoDB**, indexes are essential for improving the performance of queries and allowing more flexible data
retrieval. Without indexes, DynamoDB would be limited to querying data by the primary key (which consists of a partition
key and an optional sort key). While this approach works for basic queries, it becomes inefficient for more complex
query patterns that require retrieving data by attributes other than the primary key.

To address this limitation, DynamoDB supports two types of indexes:

- **Global Secondary Indexes (GSI)**
- **Local Secondary Indexes (LSI)**

Both indexes allow you to perform queries on non-primary key attributes, enabling more complex querying capabilities.

## Types of Indexes

### Global Secondary Indexes (GSI)

A **Global Secondary Index (GSI)** allows you to query your DynamoDB table using any attribute (or combination of
attributes), even if they aren’t part of the primary key. GSIs can have their own partition key and sort key, which
means they are not restricted by the table’s primary key structure.

Benefits of GSIs:

- Flexibility to index any attribute.
- Can use any combination of attributes for the partition key and sort key.
- Can be created at any time, even after the table is created.

In **DynamoPHP**, you can create and configure a GSI for an entity by specifying it in your entity class.

Here’s an example of how to define a GSI for an entity:

```php
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\PartitionKey;

#[Entity(
    table: 'users',
    partitionKey: new PartitionKey(['id']),
    indexes: [
        new GlobalIndex(
            name: 'GSI1',
            partitionKey: new PartitionKey(fields: ['fullName'], name: 'name'),
        ),
    ]
)]
class User
{
    #[Attribute]
    public string $id;
    
    #[Attribute(name: 'name')]
    public string $fullName;
}
```

In this example, the **Global Secondary Index (GSI)** is defined on the _name_ attribute (partition key). This allows
querying users by their name efficiently.

### Local Secondary Indexes (LSI)

A **Local Secondary Index (LSI)** allows you to query your table using the same partition key but with a different sort
key. LSIs are useful when you need to query based on a different sort order or an additional attribute, but they are
restricted to the same partition key as the table.

Benefits of LSIs:

- Provides an alternative sort key for queries.
- Useful when you want to query data that belongs to the same partition but sorted by a different attribute.

Here’s how you could define an LSI:

```php
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\LocalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    table: 'users',
    partitionKey: new PartitionKey(['id']),
    sortKey: new SortKey(['creationDate']),
    indexes: [
        new LocalIndex(name: 'LSI1', sortKey: new SortKey(fields: ['fullName'], name: 'name')),
    ]
)]
class User
{
    #[Attribute]
    public string $id;
    
    #[Attribute(name: 'name')]
    public string $fullName;
    
    #[Attribute]
    public DateTimeInterface $creationDate;
}
```

In this example, the **Local Secondary Index (LSI)** is defined with the same partition key (_id_) but using a different
sort key, _name_ instead of _creationDate_.

### Combining both types

In DynamoPHP, you can also combine both GSI and LSI, making your entity more robust and flexible.

Here is an example of how to do it:

```php
use DateTimeInterface;
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\GlobalIndex;
use OA\Dynamodb\Attribute\LocalIndex;
use OA\Dynamodb\Attribute\PartitionKey;
use OA\Dynamodb\Attribute\SortKey;

#[Entity(
    table: 'users',
    partitionKey: new PartitionKey(['id']),
    sortKey: new SortKey(['creationDate']),
    indexes: [
        new GlobalIndex(
            name: 'GSI1',
            partitionKey: new PartitionKey(fields: ['fullName'], name: 'name'),
            sortKey: new SortKey(fields: ['creationDate'], name: 'creationDate'),
        ),
        new LocalIndex(name: 'LSI1', sortKey: new SortKey(fields: ['fullName'], name: 'name')),
    ]
)]
class User
{
    #[Attribute]
    public string $id;
    
    #[Attribute(name: 'name')]
    public string $fullName;
    
    #[Attribute]
    public DateTimeInterface $creationDate;
}
```

You can assign as many indexes as you wish, as long as they exist in the table.

## Best practices for using Indexes

- **Use GSIs for flexible queries:** if you need to query on attributes other than the primary key, use Global Secondary
  Indexes (GSI).
- **Use LSIs for alternative sorting:** use Local Secondary Indexes (LSI) when you need to query the same partition key
  but require a different sort order or another attribute for sorting.
- **Limit the number of indexes:** while indexes are powerful, they come with a cost in terms of storage and write
  performance. Use them judiciously to balance query performance with storage requirements.
- **Choose partition and sort keys carefully:** the choice of partition and sort keys can significantly affect the
  performance of your queries. Ensure that your indexes support the most common query patterns of your application.

## Conclusion

Indexes in **DynamoPHP** provide powerful tools for optimizing query performance and enabling complex data retrieval
patterns. By defining **Global Secondary Indexes (GSI)** and **Local Secondary Indexes (LSI)** on your entities, you can
efficiently query and sort your data in DynamoDB based on various attributes. DynamoPHP’s integration with DynamoDB
indexes makes it easy to define and use, allowing developers to build scalable and performant applications.
