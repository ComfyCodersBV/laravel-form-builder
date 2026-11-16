<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use JsonSerializable;

class Charset implements JsonSerializable, ValidationRule
{
    public function __construct(
        protected string $charset,
    ) {
        if ($charset === '' || @preg_match($this->pattern(), '') === false) {
            throw new InvalidArgumentException(sprintf(
                'The charset "%s" is not the inside of a regular expression character class. Pass for example "0-9" or "A-Z0-9".',
                $charset,
            ));
        }
    }

    public function pattern(): string
    {
        return sprintf('/^[%s]*$/u', preg_replace('#(?<!\\\\)/#', '\/', $this->charset));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $fail('validation.regex')->translate();

            return;
        }

        if (preg_match($this->pattern(), (string) $value) !== 1) {
            $fail('validation.regex')->translate();
        }
    }

    /**
     * @return array{charset: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'charset' => $this->charset,
        ];
    }
}
