<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use Symfony\Component\Serializer\Serializer;

trait SerializerProviderTrait
{
    public function getSerializer(): Serializer
    {
        return static::getContainer()->get('serializer');
    }
}