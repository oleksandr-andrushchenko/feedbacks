<?php

declare(strict_types=1);

namespace OA\Dynamodb\Attribute;

abstract class AbstractKey
{
    public function __construct(
        public ?string $prefix = null,
        /**
         * null|array<int, string>
         */
        public ?array $fields = null,
        public ?string $name = null,
        public ?string $delimiter = null,
    )
    {
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDelimiter(?string $delimiter): static
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }
}
