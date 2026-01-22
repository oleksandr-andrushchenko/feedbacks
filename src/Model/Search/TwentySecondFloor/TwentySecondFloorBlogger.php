<?php

declare(strict_types=1);

namespace App\Model\Search\TwentySecondFloor;

readonly class TwentySecondFloorBlogger
{
    public function __construct(
        private string $name,
        private string $href,
        private ?string $photo = null,
        private ?string $desc = null,
        private ?int $followers = null,
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function getFollowers(): ?int
    {
        return $this->followers;
    }
}