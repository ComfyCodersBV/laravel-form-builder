<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use LogicException;
use TranquilTools\FormBuilder\Fields\Traits\HasMultiple;
use TranquilTools\FormBuilder\Fields\Traits\HasOptions;
use TranquilTools\FormBuilder\Rules\DependentOption;

class Select extends BaseField
{
    use HasMultiple;
    use HasOptions;

    protected string $type = 'select';

    /** @var array<int|string, Collection|array<int|string, mixed>>|null */
    protected ?array $optionGroups = null;

    protected ?string $dependsOn = null;

    /** @var array<int|string, Collection|array<int|string, mixed>> */
    protected array $optionsPerValue = [];

    /**
     * @param  array<int|string, Collection|array<int|string, mixed>>  $groups
     */
    public function optionGroups(array $groups): static
    {
        $this->optionGroups = $groups;

        return $this;
    }

    /**
     * @param  array<int|string, Collection|array<int|string, mixed>>  $optionsPerValue
     */
    public function optionsFor(string $field, array $optionsPerValue): static
    {
        $this->dependsOn = $field;
        $this->optionsPerValue = $optionsPerValue;

        return $this;
    }

    public function optionCode(string $column = 'code'): static
    {
        if ($column === '') {
            unset($this->attributes['optionCode']);

            return $this;
        }

        $this->attributes['optionCode'] = $column;

        return $this;
    }

    public function getRules(): array
    {
        $name = $this->getName();

        if (is_null($this->dependsOn) || is_null($name) || $name === '') {
            return parent::getRules();
        }

        return [
            ...parent::getRules(),
            new DependentOption($name, $this->dependsOn, $this->allowedValuesPerOption()),
        ];
    }

    public function searchUrl(?string $url): static
    {
        if (is_null($url) || $url === '') {
            unset($this->attributes['searchUrl']);

            return $this;
        }

        $this->attributes['searchUrl'] = $url;

        return $this;
    }

    public function minSearchLength(int $length): static
    {
        $this->attributes['minSearchLength'] = max(1, $length);

        return $this;
    }

    public function selected(string|int|null $value, ?string $label = null, string|int|null $code = null): static
    {
        if (is_null($value) || $value === '') {
            unset($this->attributes['selected']);

            return $this;
        }

        $this->attributes['selected'] = [
            'value' => $value,
            'label' => is_null($label) || $label === '' ? (string) $value : $label,
        ];

        if (! is_null($code) && $code !== '') {
            $this->attributes['selected']['code'] = (string) $code;
        }

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->attributes['clearable'] = $clearable;

        return $this;
    }

    public function toSchema(): array
    {
        if ($this->attributes['clearable'] ?? false) {
            $this->attributes['clearLabel'] ??= trans('form-builder::fields.clear');
        }

        $this->attributes['searchPlaceholder'] ??= trans('form-builder::fields.search-placeholder');
        $this->attributes['noResultsLabel'] ??= trans('form-builder::fields.no-results');
        $this->attributes['choosePlaceholder'] ??= trans('form-builder::fields.choose-option');

        $schema = $this->optionsSchema(parent::toSchema());

        if (! isset($schema['searchUrl'])) {
            return $schema;
        }

        if ($schema['multiple'] ?? false) {
            throw new LogicException("Select [{$this->getName()}] cannot search on the server and select multiple options.");
        }

        if (! is_null($this->dependsOn)) {
            throw new LogicException("Select [{$this->getName()}] cannot search on the server and depend on [{$this->dependsOn}].");
        }

        unset($schema['options'], $schema['optionOrder']);

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function optionsSchema(array $schema): array
    {
        if (! is_null($this->dependsOn)) {
            unset($schema['options']);

            return [
                ...$schema,
                'dependentOptions' => [
                    'field' => $this->dependsOn,
                    'options' => array_map(
                        fn (Collection|array $options): array => $this->optionRows($options),
                        $this->optionsPerValue,
                    ),
                ],
            ];
        }

        if (! is_null($this->optionGroups)) {
            return [
                ...$schema,
                'options' => $this->groupedRows($this->optionGroups),
            ];
        }

        $options = $schema['options'] ?? null;

        if (! is_array($options) || array_is_list($options)) {
            return $schema;
        }

        return [
            ...$schema,
            'optionOrder' => array_map(fn (int|string $value): string => (string) $value, array_keys($options)),
        ];
    }

    /**
     * @param  array<int|string, Collection|array<int|string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    protected function groupedRows(array $groups): array
    {
        $rows = [];

        foreach ($groups as $group => $options) {
            foreach ($this->optionRows($options) as $row) {
                $rows[] = [
                    ...$row,
                    'group' => (string) $group,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  Collection|array<int|string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    protected function optionRows(Collection|array $options): array
    {
        $options = $options instanceof Collection ? $options->all() : $options;
        $rows = [];

        foreach ($options as $value => $option) {
            $rows[] = $this->optionRow($value, $option, array_is_list($options));
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function optionRow(int|string $value, mixed $option, bool $fromList): array
    {
        if ($option instanceof Arrayable) {
            return $option->toArray();
        }

        if (is_array($option)) {
            return $option;
        }

        $value = $fromList ? $option : $value;

        return [
            $this->valueKey() => is_scalar($value) ? (string) $value : '',
            $this->labelKey() => is_scalar($option) ? (string) $option : '',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function allowedValuesPerOption(): array
    {
        $allowed = [];

        foreach ($this->optionsPerValue as $controllingValue => $options) {
            $allowed[(string) $controllingValue] = array_map(
                fn (array $row): string => is_scalar($row[$this->valueKey()] ?? null) ? (string) $row[$this->valueKey()] : '',
                $this->optionRows($options),
            );
        }

        return $allowed;
    }

    protected function valueKey(): string
    {
        return $this->attributes['optionValue'] ?? 'id';
    }

    protected function labelKey(): string
    {
        return $this->attributes['optionLabel'] ?? 'name';
    }
}
