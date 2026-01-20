<?php

declare(strict_types=1);

namespace App\Model;

class ImportResult
{
    public function __construct(
        private int $createdCount = 0,
        private int $updatedCount = 0,
        private int $deletedCount = 0,
        private int $restoredCount = 0,
        private int $unchangedCount = 0,
        private int $skippedCount = 0,
        private int $failedCount = 0,
        private int $webhookSyncedCount = 0,
    )
    {
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function incCreatedCount(): void
    {
        $this->createdCount++;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function incUpdatedCount(): void
    {
        $this->updatedCount++;
    }

    public function getDeletedCount(): int
    {
        return $this->deletedCount;
    }

    public function incDeletedCount(): void
    {
        $this->deletedCount++;
    }

    public function getRestoredCount(): int
    {
        return $this->restoredCount;
    }

    public function incRestoredCount(): void
    {
        $this->restoredCount++;
    }

    public function getUnchangedCount(): int
    {
        return $this->unchangedCount;
    }

    public function incUnchangedCount(): void
    {
        $this->unchangedCount++;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function incSkippedCount(): void
    {
        $this->skippedCount++;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function incFailedCount(): void
    {
        $this->failedCount++;
    }

    public function incWebhookSyncedCount(): void
    {
        $this->webhookSyncedCount++;
    }

    public function getWebhookSyncedCount(): int
    {
        return $this->webhookSyncedCount;
    }
}
