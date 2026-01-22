# Entity in DynamoPHP

## Overview

In **DynamoPHP**, the **Entity** represents the core building block of the **Object Data Mapper (ODM)**. An entity is a
**PHP class** that defines the data structure to be stored and retrieved from **Amazon DynamoDB**. Entities are used to
map the properties of a PHP class to attributes in a DynamoDB table, and they form the primary mechanism for interacting
with the database.

**DynamoPHP’s Entity** system uses **PHP 8 attributes** to define how class properties should be mapped to DynamoDB
fields.

## Role in Object Data Mapping

The **Object Data Mapper (ODM)** pattern allows developers to work with database data as objects. The ODM serves as a
bridge between the object-oriented PHP code and the relational (or NoSQL) database. In the context of **DynamoPHP**, the
ODM interacts with DynamoDB.

Entities play a crucial role by representing database records as PHP objects. Each entity is an abstraction of a
DynamoDB item and allows developers to interact with DynamoDB using standard PHP object manipulation, such as calling
methods and accessing properties.

Entities enable DynamoPHP to:

- **Map class properties to DynamoDB attributes:** the entity defines the structure of the data.
- **Persist objects to DynamoDB:** the ODM maps the state of an object to a DynamoDB table.
- **Query and retrieve data:** the ODM translates queries and scans into DynamoDB queries.

In DynamoPHP, the Entity class is not just an object; it is the interface between the application logic and DynamoDB,
enabling the application to work with objects rather than raw data structures.

## Anatomy of an Entity

### Entity

The **Entity** attribute is used to designate a PHP class as a DynamoDB entity. It specifies the DynamoDB table that
the entity will be mapped to. The table name is provided as an argument to the attribute.

Example:

```php
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;

#[Entity(table: 'users', partitionKey: new PartitionKey(['id']))]
class User
{
    public string $id;
    
    // Class definition...
}
```

- **Table Mapping**: the **table** argument specifies which DynamoDB table the entity represents.
- **Key Mapping**: the **partitionKey** argument specifies how the partition key is designed and its fields.

### Attribute

Each property of the entity class is annotated with the **Attribute** attribute. This attribute tells the ODM how the
property should be mapped to DynamoDB. You can specify properties like the name of the DynamoDB field.

Example:

```php
use OA\Dynamodb\Attribute\Attribute;
use OA\Dynamodb\Attribute\Entity;
use OA\Dynamodb\Attribute\PartitionKey;

#[Entity(table: 'users', partitionKey: new PartitionKey(['id']))]
class User
{
    #[Attribute]
    public string $id;
    
    #[Attribute(name: 'name')]
    public string $fullName;
}
```

- **Property Mapping**: the **name** argument is used to map the property to a DynamoDB attribute. This argument is
  optional and, if not provided, DynamoPHP will use the property's name as DynamoDB attribute's name.

## Inheritance

**DynamoPHP** supports _PHP attribute inheritance_. So if you are leveraging class inheritance in your application,
**DynamoPHP attributes** will also be passed down and inherited from parent to child classes.

## Best practices

- **Define entities as simple value objects:** keep entities as lightweight as possible by using simple data types and
  avoiding complex business logic within the entity class itself.
- **Ensure proper primary key definition:** always specify the primary key using the _primaryKey_ and _sortKey_ (if
  applicable) arguments, as this is crucial for correct DynamoDB operations.
- **Use consistent naming conventions:** align your class and property names with your DynamoDB table and attribute
  names to make the code more intuitive and easier to maintain.

## Conclusion

**Entities** in **DynamoPHP** are the cornerstone of the library’s **Object Data Mapping (ODM)** functionality. They
enable developers to interact with **DynamoDB** in an object-oriented manner, abstracting away the need to deal with raw
data structures. By leveraging **PHP 8 attributes**, DynamoPHP simplifies the mapping process between PHP classes and
DynamoDB, ensuring a smooth and efficient development experience.
