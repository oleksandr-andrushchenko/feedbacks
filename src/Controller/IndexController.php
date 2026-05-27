<?php
declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function __construct(
        private readonly string $faviconFilename,
    )
    {
    }

    public function favicon(): Response
    {
        return new Response('');
        $response = new BinaryFileResponse($this->faviconFilename);
        $response->setExpires(new DateTimeImmutable('2030-01-01 23:59:59'));
        $response->setAutoLastModified();
        $response->setAutoEtag();

        return $response;
    }
}
