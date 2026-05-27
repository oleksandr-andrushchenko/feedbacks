<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class PhoneNumberSearchTermParser implements SearchTermParserInterface
{
    public function __construct(
        private readonly PhoneNumberSearchTermTextNormalizer $phoneNumberSearchTermTextNormalizer,
    )
    {
    }

    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supports($searchTerm->getText());
        }

        if ($searchTerm->getType() === SearchTermType::phone_number) {
            return true;
        }

        return false;
    }

    private function supports(string $number): bool
    {
        return preg_match("/^\+?[0-9.\-() ]+$/", $number) === 1;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($this->supports($searchTerm->getText())) {
            $searchTerm->addType(SearchTermType::phone_number);
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($searchTerm->getType() === SearchTermType::phone_number) {
            $text = $searchTerm->getText();
            $countryCodes = $context['country_codes'] ?? [];
            $normalizedText = $this->phoneNumberSearchTermTextNormalizer->normalizePhoneNumberSearchTermText($text, $countryCodes);

            if ($normalizedText !== $text) {
                $searchTerm->setNormalizedText($normalizedText);
            }
        }
    }
}