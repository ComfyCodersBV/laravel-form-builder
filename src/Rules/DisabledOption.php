<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use JsonSerializable;

class DisabledOption implements JsonSerializable, ValidationRule
{
    /**
     * @param  list<string>  $disabledValues
     */
    public function __construct(
        protected array $disabledValues,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $entry) {
            if (is_scalar($entry) && in_array((string) $entry, $this->disabledValues, true)) {
                $fail('validation.in')->translate();

                return;
            }
        }
    }

    /**
     * @return array{disabledValues: list<string>}
     */
    public function jsonSerialize(): array
    {
        return [
            'disabledValues' => $this->disabledValues,
        ];
    }
}
