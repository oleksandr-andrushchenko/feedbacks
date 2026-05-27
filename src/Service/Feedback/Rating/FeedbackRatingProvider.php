<?php
declare(strict_types=1);

namespace App\Service\Feedback\Rating;

use App\Enum\Feedback\Rating;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackRatingProvider
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function getRatingComposeName(Rating $rating, string $localeCode = null): string
    {
        $icon = $this->getRatingIcon($rating);
        $name = $this->getRatingName($rating, $localeCode);

        return $icon . ' ' . $name;
    }

    public function getRatingIcon(Rating $rating): ?string
    {
        return $this->translator->trans($rating->name, domain: 'feedbacks.rating_icon', locale: 'en');
    }

    public function getRatingName(Rating $rating, string $localeCode = null): string
    {
        return $this->translator->trans($rating->name, domain: 'feedbacks.rating', locale: $localeCode);
    }
}
