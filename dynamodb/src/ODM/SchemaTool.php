<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

class SchemaTool
{
    public function __construct(
        private EntityManager $em,
    )
    {
    }

    /**
     * @return void
     * @todo: improve: get tables from the entities metadata
     */
    public function dropTables(): void
    {
        $table = $this->em->getMetadataLoader()->getDefaultTable();
        $this->em->getClient()->deleteTable(['TableName' => $table]);
    }

    /**
     * @param array $schema
     * @return void
     * @todo: improve: generate schema from the entities metadata
     */
    public function createTables(array $schema): void
    {
        $table = $this->em->getMetadataLoader()->getDefaultTable();
        $schema['TableName'] = $table;
        $this->em->getClient()->createTable($schema);
    }
}