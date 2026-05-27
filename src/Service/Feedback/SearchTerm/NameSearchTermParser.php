<?php
declare(strict_types=1);

namespace App\Service\Feedback\SearchTerm;

use App\Enum\Feedback\SearchTermType;
use App\Transfer\Feedback\SearchTermTransfer;

class NameSearchTermParser implements SearchTermParserInterface
{
    public function supportsSearchTerm(SearchTermTransfer $searchTerm, array $context = []): bool
    {
        if ($searchTerm->getType() === null) {
            return $this->supportsPersonName($searchTerm->getText())
                || $this->supportsOrganizationName($searchTerm->getText())
                || $this->supportsPlaceName($searchTerm->getText());
        }

        if (in_array($searchTerm->getText(), [SearchTermType::person_name, SearchTermType::organization_name, SearchTermType::place_name], true)) {
            return true;
        }

        return false;
    }

    private function supportsPersonName(string $personName): bool
    {
        $result = preg_match($this->getPattern($first = 'A-Za-z\pL\x{00C0}-\x{00FF}', $first), $personName);

        if ($result === 1) {
            return true;
        }

        return false;
    }

    private function getPattern(string $first, string $next): string
    {
        return sprintf('/^[%s][%s\'\-]+([\ %s][%s\'\-]+)*$/iu', $first, ...array_fill(0, 3, $next));
    }

    private function supportsOrganizationName(string $orgName): bool
    {
        if (preg_match('/[A-Za-z\pL\x{00C0}-\x{00FF}]+/', $orgName) === 0) {
            return false;
        }

        $result = preg_match($this->getPattern($first = '\+«\"A-Za-z0-9\pL\x{00C0}-\x{00FF}', ',\.\/\'\-’\(\)&«»\`\"' . $first), $orgName);

        if ($result === 1) {
            return true;
        }

        return false;
    }

    private function supportsPlaceName(string $placeName): bool
    {
        if (preg_match('/[A-Za-z\pL\x{00C0}-\x{00FF}]+/', $placeName) === 0) {
            return false;
        }

        $result = preg_match($this->getPattern($first = 'A-Za-z0-9\pL\x{00C0}-\x{00FF}', ',\.\/' . $first), $placeName);

        if ($result === 1) {
            return true;
        }

        return false;
    }

    public function parseWithGuessType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        if ($this->supportsPersonName($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::person_name)
            ;
        }

        if ($this->supportsOrganizationName($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::organization_name)
            ;
        }

        if ($this->supportsPlaceName($searchTerm->getText())) {
            $searchTerm
                ->addType(SearchTermType::place_name)
            ;
        }
    }

    public function parseWithKnownType(SearchTermTransfer $searchTerm, array $context = []): void
    {
        // TODO: Implement parseWithKnownType() method.
    }
}
