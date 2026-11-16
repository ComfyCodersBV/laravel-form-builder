<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\Str;
use TranquilTools\FormBuilder\Fields\Traits\HasTextfieldOptions;
use TranquilTools\FormBuilder\Rules\Charset;

class Text extends BaseField
{
    use HasTextfieldOptions;

    protected string $type = 'text';

    public function maxLength(int $value): static
    {
        $this->attributes['maxlength'] = $value;
        $this->rules[] = 'max:'.$value;

        return $this;
    }

    public function minLength(int $value): static
    {
        $this->attributes['minlength'] = $value;
        $this->rules[] = 'min:'.$value;

        return $this;
    }

    public function type(string $type): static
    {
        $allowed = [
            'color',
            'email',
            'password',
            'text',
        ];

        $this->type = in_array($type, $allowed, true) ? $type : 'text';

        return $this;
    }

    public function charset(string $charset): static
    {
        $this->rules = [
            ...array_filter($this->rules, fn (mixed $existing): bool => ! $existing instanceof Charset),
            new Charset($charset),
        ];
        $this->attributes['charset'] = $charset;

        return $this;
    }

    public function uppercase(bool $uppercase = true): static
    {
        if (! $uppercase) {
            unset($this->attributes['uppercase']);

            return $this;
        }

        $this->attributes['uppercase'] = true;

        return $this;
    }

    /**
     * @param  iterable<int|string, string|int|float>  $suggestions
     */
    public function suggestions(iterable $suggestions): static
    {
        $values = [];

        foreach ($suggestions as $suggestion) {
            $values[] = (string) $suggestion;
        }

        $values = array_values(array_unique(array_filter($values, fn (string $value): bool => $value !== '')));

        if ($values === []) {
            unset($this->attributes['suggestions']);

            return $this;
        }

        $this->attributes['suggestions'] = $values;

        return $this;
    }

    public function prepareForValidation(mixed $value): mixed
    {
        if (! ($this->attributes['uppercase'] ?? false) || ! is_string($value)) {
            return $value;
        }

        return Str::upper($value);
    }

    public function email(): static
    {
        return $this->type('email');
    }

    public function password(): static
    {
        return $this->type('password');
    }
}
