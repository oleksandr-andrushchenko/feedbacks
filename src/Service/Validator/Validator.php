<?php
declare(strict_types=1);

namespace App\Service\Validator;

use App\Exception\ValidatorException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    )
    {
    }

    /**
     * @param mixed $value
     * @param Constraint|array|null $constraints
     * @param string|GroupSequence|array|null $groups
     * @return void
     * @throws ValidatorException
     */
    public function validate(mixed $value, Constraint|array $constraints = null, string|GroupSequence|array $groups = null): void
    {
        $violations = $this->validator->validate($value, $constraints, $groups);

        if ($violations->count() > 0) {
            throw new ValidatorException($violations);
        }
    }
}