DynamoPHP Symfony Bundle
================
![PHP Version](https://img.shields.io/packagist/dependency-v/edumarques/dynamophp-symfony/php?version=dev-main&color=%23777BB3)
![License](https://img.shields.io/github/license/edumarques/dynamophp-symfony)
![Build Status](https://github.com/edumarques/dynamophp-symfony/actions/workflows/base.yml/badge.svg)

---

The **DynamoPHP Symfony Bundle** integrates [DynamoPHP](https://github.com/edumarques/dynamophp)
into [Symfony](https://symfony.com) applications, providing seamless configuration and service registration for Amazon
DynamoDB operations.

## Features

- Auto-wiring of DynamoPHP services
- Configurable AWS SDK DynamoDB client, Marshaler and Serializer
- Sandbox application for testing and development

## Installation

Install via Composer:

```shell
composer require edumarques/dynamophp-symfony
```

If your Symfony application is not configured to use Symfony Flex for automatic bundle registration, you need to
register it manually in `config/bundles.php`:

```php
# config/bundles.php
return [
    // ... other bundles
    EduardoMarques\DynamoPHPBundle\DynamoPHPBundle::class => ['all' => true],
];
```

## Configuration

After installation, you must configure the bundle by creating a `dynamo_php.yaml` file in your `config/packages/`
directory:

```yaml
# config/packages/dynamo_php.yaml
dynamo_php:
  client: dynamodb_client # set the service ID of the AWS DynamoDB client registered in your app
  marshaler: marshaler # set the service ID of the AWS Marshaler registered in your app
  serializer: serializer # set the service ID of the Symfony serializer registered in your app
```

## Usage

Once configured, you can inject DynamoPHP services into your Symfony services or controllers. For example, to use the
EntityManager:

```php
use OA\Dynamodb\ODM\EntityManager;

class YourService
{
    public function __construct(
        private EntityManager $entityManager,
    ) {
    }

    // Your methods here
}
```

## Contributing

Contributors are always welcome! For more information on how you can contribute, please read
our [contribution guideline](CONTRIBUTING.md).

For any questions, feel free to reach out to me directly by
email: [eduardomarqs1@gmail.com](mailto:eduardomarqs1@gmail.com).

For more information on DynamoPHP, visit the [DynamoPHP repository](https://github.com/edumarques/dynamophp).
