<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Repeater extends BaseField
{
    protected string $type = 'repeater';

    /** @var list<BaseField> */
    protected array $subFields = [];

    public function __construct()
    {
        $this->rules([
            'array',
        ]);
    }

    /**
     * @param  list<BaseField>  $fields
     */
    public function fields(array $fields): static
    {
        $this->subFields = $fields;

        return $this;
    }

    public function addActionLabel(string $label): static
    {
        $this->attributes['addActionLabel'] = $label;

        return $this;
    }

    public function itemLabel(string $label): static
    {
        $this->attributes['itemLabel'] = $label;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->attributes['inline'] = $inline;

        return $this;
    }

    public function min(int $min): static
    {
        $this->attributes['min'] = $min;

        return $this;
    }

    public function max(int $max): static
    {
        $this->attributes['max'] = $max;

        return $this;
    }

    public function addable(bool $addable = true): static
    {
        $this->attributes['addable'] = $addable;

        return $this;
    }

    public function deletable(bool $deletable = true): static
    {
        $this->attributes['deletable'] = $deletable;

        return $this;
    }

    public function reorderable(bool $reorderable = true): static
    {
        $this->attributes['reorderable'] = $reorderable;

        return $this;
    }

    public function toSchema(): array
    {
        return array_merge(parent::toSchema(), [
            'fields' => array_map(
                fn (BaseField $field): array => $field->toSchema(),
                $this->subFields,
            ),
        ]);
    }

    /**
     * Expand the sub-field rules into dotted, wildcard rules
     * (e.g. `ingredients.*.name`) so each row is validated server-side.
     *
     * @return array<string, array<int, mixed>>
     */
    public function nestedRules(): array
    {
        $name = $this->getName();

        if ($name === null || $name === '') {
            return [];
        }

        $rules = [];

        foreach ($this->subFields as $field) {
            $subName = $field->getName();

            if ($subName === null || $subName === '') {
                continue;
            }

            $subRules = $field->getRules();

            if ($subRules !== []) {
                $rules["{$name}.*.{$subName}"] = $subRules;
            }

            if (! method_exists($field, 'nestedRules')) {
                continue;
            }

            foreach ($field->nestedRules() as $nestedName => $nestedRules) {
                $rules["{$name}.*.{$nestedName}"] = $nestedRules;
            }
        }

        return $rules;
    }

    public function prepareForValidation(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn (mixed $row): mixed => $this->prepareRow($row), $value);
    }

    private function prepareRow(mixed $row): mixed
    {
        if (! is_array($row)) {
            return $row;
        }

        foreach ($this->subFields as $field) {
            $subName = $field->getName();

            if (is_null($subName) || $subName === '' || ! array_key_exists($subName, $row)) {
                continue;
            }

            $row[$subName] = $field->prepareForValidation($row[$subName]);
        }

        return $row;
    }
}
