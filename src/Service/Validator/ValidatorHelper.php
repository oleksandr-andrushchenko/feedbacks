<?php
declare(strict_types=1);

namespace App\Service\Validator;

use App\Service\Util\Array\ArrayKeyQuoter;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ValidatorHelper
{
    private ExecutionContextInterface $context;
    private string $translationDomain;

    public function __construct(
        private readonly ArrayKeyQuoter $arrayKeyQuoter,
    )
    {
    }

    public function withContext(ExecutionContextInterface $context): static
    {
        $new = clone $this;
        $new->context = $context;

        return $new;
    }

    public function withTranslationDomain(string $translationDomain): static
    {
        $new = clone $this;
        $new->translationDomain = $translationDomain;

        return $new;
    }

    public function addMessage(string $transKey, array $transParameters = []): null
    {
        $this->context->buildViolation($transKey)
            ->setParameters($this->arrayKeyQuoter->quoteKeys($transParameters))
            ->setTranslationDomain($this->translationDomain)
            ->addViolation()
        ;

        return null;
    }
}