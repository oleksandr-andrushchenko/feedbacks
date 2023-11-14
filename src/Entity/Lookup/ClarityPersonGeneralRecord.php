<?php

declare(strict_types=1);

namespace App\Entity\Lookup;

class ClarityPersonGeneralRecord
{
    public function __construct(
        /**
         * @var ClarityPersonEdr[]
         */
        private array $edrs = [],
    )
    {
    }

    public function getEdrs(): array
    {
        return $this->edrs;
    }

    public function addEdr(ClarityPersonEdr $edr): self
    {
        $this->edrs[] = $edr;

        return $this;
    }
}