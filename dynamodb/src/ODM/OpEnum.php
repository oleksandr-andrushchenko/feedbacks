<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

enum OpEnum
{
    case QUERY;
    case SCAN;
    case UPDATE;
}
