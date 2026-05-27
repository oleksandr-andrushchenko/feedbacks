<?php
declare(strict_types=1);

namespace App\Tests\Traits;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait WebClientProviderTrait
{
    // todo: static::$client
    public function getWebClient(): KernelBrowser
    {
        return static::getContainer()->get('test.client');
    }
}