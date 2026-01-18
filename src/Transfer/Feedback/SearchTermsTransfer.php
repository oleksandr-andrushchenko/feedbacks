<?php

declare(strict_types=1);

namespace App\Transfer\Feedback;

class SearchTermsTransfer
{
    public function __construct(
        /**
         * @var null|SearchTermTransfer[]
         */
        private ?array $items = null
    )
    {
    }

    public function hasItems(): bool
    {
        return $this->items !== null;
    }

    public function countItems(): ?int
    {
        if ($this->hasItems()) {
            return count($this->getItems());
        }

        return null;
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @return SearchTermTransfer[]
     */
    public function getItemsAsArray(): array
    {
        return $this->items ?? [];
    }

    public function addItem(SearchTermTransfer $searchTerm): self
    {
        if ($this->items === null) {
            $this->items = [];
        }

        $this->items[] = $searchTerm;

        return $this;
    }

    public function removeItem(SearchTermTransfer $searchTerm): self
    {
        foreach ($this->items as $index => $term) {
            if ($term !== $searchTerm) {
                continue;
            }

            unset($this->items[$index]);
            break;
        }

        $this->items = array_values($this->items);

        if (count($this->items) === 0) {
            $this->items = null;
        }

        return $this;
    }

    public function getLastItem(): ?SearchTermTransfer
    {
        if ($this->items === null || count($this->items) === 0) {
            return null;
        }

        return $this->items[count($this->items) - 1];
    }

    public function getFirstItem(): ?SearchTermTransfer
    {
        if ($this->items === null || count($this->items) === 0) {
            return null;
        }

        return $this->items[0];
    }

    public function shiftFirstItem(): ?SearchTermTransfer
    {
        if ($this->items === null || count($this->items) === 0) {
            return null;
        }

        return array_shift($this->items);
    }
}
