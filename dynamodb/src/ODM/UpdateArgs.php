<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

class UpdateArgs extends AbstractOpArgs
{
    public function returnValues(string $value): static
    {
        $this->args['ReturnValues'] = $value;
        return $this;
    }

    public function updateExpression(string $value): static
    {
        $this->args['UpdateExpression'] = $value;
        return $this;
    }
}
