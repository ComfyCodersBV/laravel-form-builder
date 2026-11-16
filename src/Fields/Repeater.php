<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\ConditionChain;

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

    public function table(bool $table = true): static
    {
        $this->attributes['table'] = $table;

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
        ConditionChain::apply($this->subFields);

        return array_merge(parent::toSchema(), [
            'fields' => array_map(
                fn (BaseField $field): array => $field->toSchema(),
                $this->subFields,
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function nestedRules(string $scope = ''): array
    {
        $name = $this->getName();

        if ($name === null || $name === '') {
            return [];
        }

        ConditionChain::apply($this->subFields);

        $rowScope = "{$scope}{$name}.*.";
        $rules = [];

        foreach ($this->subFields as $field) {
            $subName = $field->getName();

            if ($subName === null || $subName === '' || ! $field->submitsValue()) {
                continue;
            }

            foreach ($field->validationRules($rowScope) as $ruleName => $subRules) {
                if ($subRules === []) {
                    continue;
                }

                $rules["{$name}.*.{$ruleName}"] = $subRules;
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
