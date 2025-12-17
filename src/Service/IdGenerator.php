<?php

declare(strict_types=1);

namespace App\Service;

class IdGenerator
{
    public function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}