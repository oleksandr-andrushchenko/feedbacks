<?php

declare(strict_types=1);

namespace App\Command\Dynamodb;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * @see DynamodbSchemaExtractCommand
 */
class DynamodbSchemaExtractCommand extends Command
{
    public function __construct(
        private readonly string $cfFilename = 'serverless.yml',
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Extract and print DynamoDB schema from ' . $this->cfFilename);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cfPath = dirname(__DIR__, 3) . '/' . $this->cfFilename;

        if (!file_exists($cfPath)) {
            $io->error($this->cfFilename . ' not found at: ' . $cfPath);
            return Command::FAILURE;
        }

        try {
            $cf = Yaml::parseFile($cfPath, Yaml::PARSE_CUSTOM_TAGS);
        } catch (ParseException $e) {
            $io->error("YAML parsing failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $props = $cf['resources']['Resources']['DynamoDBTable']['Properties'] ?? [];

        $allowed = [
            'TableName',
            'BillingMode',
            'AttributeDefinitions',
            'KeySchema',
            'GlobalSecondaryIndexes',
        ];

        $schema = array_filter($props, static fn ($key): bool => in_array($key, $allowed, true), ARRAY_FILTER_USE_KEY);
        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $io->writeln($json);

        return Command::SUCCESS;
    }
}