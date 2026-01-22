<?php

declare(strict_types=1);

namespace OA\Dynamodb\ODM;

class ScanArgs extends AbstractOpArgs
{
    public function segment(int $segment): static
    {
        $this->args['Segment'] = $segment;

        return $this;
    }

    public function totalSegments(int $totalSegments): static
    {
        $this->args['TotalSegments'] = $totalSegments;

        return $this;
    }
}
