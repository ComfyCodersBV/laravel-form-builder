<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Tags extends BaseField
{
    public const SEPARATOR_PATTERN = '/[,;\r\n]+/';

    protected string $type = 'tags';

    protected bool $emails = false;

    protected ?int $max = null;

    public function emails(bool $emails = true): static
    {
        $this->emails = $emails;

        if ($emails) {
            $this->attributes['emails'] = true;

            return $this;
        }

        unset($this->attributes['emails']);

        return $this;
    }

    public function suggestions(?string $url): static
    {
        if (is_null($url) || $url === '') {
            unset($this->attributes['suggestionsUrl']);

            return $this;
        }

        $this->attributes['suggestionsUrl'] = $url;

        return $this;
    }

    public function max(?int $max): static
    {
        $this->max = is_null($max) ? null : max(1, $max);

        if (is_null($this->max)) {
            unset($this->attributes['max']);

            return $this;
        }

        $this->attributes['max'] = $this->max;

        return $this;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        return collect([
            in_array('required', $rules, true) ? 'required' : 'nullable',
            'array',
            ...(is_null($this->max) ? [] : ['max:'.$this->max]),
            ...$rules,
        ])
            ->unique(fn (mixed $rule): mixed => is_string($rule) ? $rule : spl_object_id($rule))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function nestedRules(): array
    {
        $name = $this->getName();

        if (is_null($name) || $name === '') {
            return [];
        }

        return [
            "{$name}.*" => [
                'string',
                ...($this->emails ? ['email:rfc'] : []),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $name = $this->getName();

        if (is_null($name) || $name === '' || is_null($this->label) || $this->label === '') {
            return [];
        }

        return [
            "{$name}.*" => $this->label,
        ];
    }

    public function prepareForValidation(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return $this->normalizeValue($value);
    }

    /**
     * @return list<string>|mixed
     */
    public function normalizeValue(mixed $value): mixed
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = preg_split(self::SEPARATOR_PATTERN, $value) ?: [];
        }

        if (! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->filter(fn (mixed $entry): bool => is_string($entry) || is_numeric($entry))
            ->map(fn (string|int|float $entry): string => trim((string) $entry))
            ->filter(fn (string $entry): bool => $entry !== '')
            ->unique()
            ->values()
            ->all();
    }
}
