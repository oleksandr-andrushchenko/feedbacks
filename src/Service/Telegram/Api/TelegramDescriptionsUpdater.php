<?php

declare(strict_types=1);

namespace App\Service\Telegram\Api;

use App\Service\Telegram\Telegram;
use Symfony\Contracts\Translation\TranslatorInterface;

class TelegramDescriptionsUpdater
{
    public function __construct(
        private readonly string $stage,
        private readonly TranslatorInterface $translator,
        private ?array $myNames = null,
        private ?array $myDescriptions = null,
        private ?array $myShortDescriptions = null,
    )
    {
    }

    /**
     * @param Telegram $telegram
     * @return void
     */
    public function updateTelegramDescriptions(Telegram $telegram): void
    {
        $this->myNames = [];
        $this->myDescriptions = [];
        $this->myShortDescriptions = [];

        $domain = sprintf('tg.%s', $telegram->getName()->name);

        foreach ($telegram->getOptions()->getLanguageCodes() as $languageCode) {
            $name = $this->translator->trans(sprintf('name.%s', $this->stage), domain: $domain, locale: $languageCode);
            $this->myNames[] = $name;
            $telegram->setMyName([
                'name' => $name,
                'language_code' => $languageCode,
            ]);
            $description = $this->translator->trans('description', domain: $domain, locale: $languageCode);
            $this->myDescriptions[] = $description;
            $telegram->setMyDescription([
                'description' => $description,
                'language_code' => $languageCode,
            ]);
            $shortDescription = $this->translator->trans('short_description', domain: $domain, locale: $languageCode);
            $this->myShortDescriptions[] = $shortDescription;
            $telegram->setMyShortDescription([
                'short_description' => $shortDescription,
                'language_code' => $languageCode,
            ]);
        }
    }

    /**
     * @return array|null
     */
    public function getMyNames(): ?array
    {
        return $this->myNames;
    }

    /**
     * @return array|null
     */
    public function getMyDescriptions(): ?array
    {
        return $this->myDescriptions;
    }

    /**
     * @return array|null
     */
    public function getMyShortDescriptions(): ?array
    {
        return $this->myShortDescriptions;
    }
}