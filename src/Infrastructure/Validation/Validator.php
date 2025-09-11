<?php declare(strict_types=1);

namespace App\Infrastructure\Validation;

use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validatable;

final class Validator
{
    /**
     * @param array<string,mixed> $data
     * @param array<string,Validatable> $rules
     * @return array<string,string>
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            try {
                $rule->assert($data[$field] ?? null);
            } catch (ValidationException $e) {
                $errors[$field] = $e->getMessage();
            }
        }
        return $errors;
    }
}
