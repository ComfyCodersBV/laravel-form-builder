<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use JsonSerializable;

class DependentOption implements DataAwareRule, JsonSerializable, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @param  array<string, list<string>>  $allowedValues
     */
    public function __construct(
        protected string $name,
        protected string $dependsOn,
        protected array $allowedValues,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = $this->allowedValues[$this->controllingValue($attribute)] ?? [];
        $values = is_array($value) ? $value : [$value];

        foreach ($values as $entry) {
            if (! is_scalar($entry) || ! in_array((string) $entry, $allowed, true)) {
                $fail('validation.in')->translate();

                return;
            }
        }
    }

    protected function controllingValue(string $attribute): string
    {
        $prefix = Str::endsWith($attribute, '.'.$this->name)
            ? Str::beforeLast($attribute, $this->name)
            : '';

        $value = data_get($this->data, $prefix.$this->dependsOn);

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return array{dependsOn: string, allowedValues: array<string, list<string>>}
     */
    public function jsonSerialize(): array
    {
        return [
            'dependsOn' => $this->dependsOn,
            'allowedValues' => $this->allowedValues,
        ];
    }
}
