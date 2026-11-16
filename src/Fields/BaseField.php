<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\RequiredIf;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @phpstan-consistent-constructor
 *
 * Field types are constructed through make(), which sets the name afterwards,
 * so a subclass that took constructor arguments could never receive them.
 */
abstract class BaseField implements JsonSerializable
{
    use Macroable;

    protected string $type;

    private ?string $name = null;

    protected ?string $label = null;

    protected ?string $condition = null;

    /**
     * @var array{field: string, values: list<string>}|null
     */
    protected ?array $visibleWhen = null;

    /**
     * @var list<array{field: string, values: list<string>}>
     */
    protected array $inheritedVisibility = [];

    protected array $rules = [];

    protected array $attributes = [];

    protected mixed $default = null;

    protected ?bool $markRequired = null;

    public function attributes(array $attributes): static
    {
        foreach (array_keys($attributes) as $key) {
            $this->guardAgainstVueDirective((string) $key);
        }

        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    protected function guardAgainstVueDirective(string $key): void
    {
        if (! Str::startsWith($key, ['@', ':', 'v-on:', 'v-bind:'])) {
            return;
        }

        $name = $this->getName() ?? 'field_name';

        throw new InvalidArgumentException(sprintf(
            'The attribute "%s" on field "%s" is a Vue directive. Event listeners and bindings cannot come from PHP: '
            .'the schema is JSON and the renderer would set it as a plain HTML attribute. Attach them in Vue through '
            .'fieldOverrides instead, for example :field-overrides="{ \'%s\': { onClick: () => preview() } }".',
            $key,
            $name,
            $name,
        ));
    }

    public function class(string $className): static
    {
        return $this->wrapperClass($className);
    }

    public function wrapperClass(string $className): static
    {
        $this->attributes['className'] = $className;

        return $this;
    }

    public function inputClass(string $className): static
    {
        $this->attributes['inputClassName'] = $className;

        return $this;
    }

    public function columnSpan(int $span): static
    {
        $this->attributes['columnSpan'] = max(1, min(Section::MAX_COLUMNS, $span));

        return $this;
    }

    public function columnWidth(string $width): static
    {
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|ch|%)$/D', $width) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The column width "%s" on field "%s" is not a CSS length. Use a number with px, rem, em, ch or %%, for example "8rem".',
                $width,
                $this->getName() ?? 'field_name',
            ));
        }

        $this->attributes['columnWidth'] = $width;

        return $this;
    }

    public function columnSpanFull(): static
    {
        $this->attributes['columnSpan'] = 'full';

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value ?? '';

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        if ($disabled) {
            $this->attributes['disabled'] = 'disabled';
        }

        return $this;
    }

    /**
     * @param  string|int|float|bool|array<int, string|int|float|bool|null>|null  $value
     */
    public function if(string $condition, string|int|float|bool|array|null $value = null): static
    {
        if (func_num_args() === 1) {
            $this->condition = $condition;
            $this->visibleWhen = null;
            $this->inheritedVisibility = [];

            return $this;
        }

        if (trim($condition) === '') {
            throw new InvalidArgumentException(sprintf(
                'The field "%s" depends on a field without a name. Pass the name of the field it depends on, for example ->if(\'action\', \'rebook\').',
                $this->getName() ?? 'field_name',
            ));
        }

        $this->visibleWhen = [
            'field' => $condition,
            'values' => self::conditionValues(is_array($value) ? $value : [$value]),
        ];
        $this->condition = null;
        $this->inheritedVisibility = [];

        return $this;
    }

    /**
     * @param  array{field: string, values: list<string>}  $visibility
     */
    private static function conditionExpression(array $visibility): string
    {
        return sprintf(
            '!Array.isArray(form[%2$s]) && %1$s.includes(String(form[%2$s] === \'\' ? null : (form[%2$s] ?? null)))',
            json_encode($visibility['values'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            json_encode($visibility['field'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    private static function conditionValues(array $values): array
    {
        $strings = [];

        foreach ($values as $value) {
            $strings = [
                ...$strings,
                ...match (true) {
                    $value === true => ['true', '1'],
                    $value === false => ['false', '0'],
                    is_null($value) => ['null'],
                    is_scalar($value) => [(string) $value],
                    default => throw new InvalidArgumentException('A condition value must be a string, number, boolean or null.'),
                },
            ];
        }

        return array_values(array_unique($strings));
    }

    /**
     * @return array{field: string, values: list<string>}|null
     */
    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhen;
    }

    /**
     * @param  list<array{field: string, values: list<string>}>  $conditions
     */
    public function inheritVisibility(array $conditions): static
    {
        $this->inheritedVisibility = is_null($this->visibleWhen)
            ? []
            : array_values(array_filter(
                $conditions,
                fn (array $condition): bool => $condition !== $this->visibleWhen && $condition['field'] !== $this->getName(),
            ));

        return $this;
    }

    /**
     * @return list<array{field: string, values: list<string>}>
     */
    public function visibilityConditions(): array
    {
        if (is_null($this->visibleWhen)) {
            return [];
        }

        return [
            $this->visibleWhen,
            ...$this->inheritedVisibility,
        ];
    }

    /**
     * @return list<string>
     */
    public function exclusionRules(string $scope = ''): array
    {
        return array_map(
            fn (array $visibility): string => sprintf(
                'exclude_unless:%s',
                implode(',', array_map(
                    self::ruleParameter(...),
                    [
                        $scope.$visibility['field'],
                        ...$visibility['values'],
                    ],
                )),
            ),
            $this->visibilityConditions(),
        );
    }

    private static function ruleParameter(string $parameter): string
    {
        if (! str_contains($parameter, ',') && ! str_contains($parameter, '"')) {
            return $parameter;
        }

        return sprintf('"%s"', str_replace('"', '""', $parameter));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(string $scope = ''): array
    {
        $rules = [];
        $name = $this->getName();

        if (! is_null($name) && $name !== '' && $this->hasOwnRuleKey()) {
            $rules[$name] = $this->getRules();
        }

        if (method_exists($this, 'nestedRules')) {
            foreach ($this->nestedRules($scope) as $nestedName => $nestedRules) {
                $rules[$nestedName] = $nestedRules;
            }
        }

        $exclusions = $this->exclusionRules($scope);

        if ($exclusions === []) {
            return $rules;
        }

        return array_map(
            fn (array $fieldRules): array => [
                ...$exclusions,
                ...$fieldRules,
            ],
            $rules,
        );
    }

    protected function hasOwnRuleKey(): bool
    {
        return $this->submitsValue();
    }

    public function help(string|HtmlString $text, bool $html = true): static
    {
        if (! $html) {
            return $this->helpText($text instanceof HtmlString ? $text->toHtml() : $text);
        }

        unset($this->attributes['helpText']);
        $this->attributes['help'] = $text instanceof HtmlString ? $text->toHtml() : e($text);

        return $this;
    }

    public function helpText(string $text): static
    {
        $this->attributes['help'] = e($text);
        $this->attributes['helpText'] = $text;

        return $this;
    }

    public function markRequired(?bool $mark = true): static
    {
        $this->markRequired = $mark;

        return $this;
    }

    public function marksRequired(): bool
    {
        if (! is_null($this->markRequired)) {
            return $this->markRequired;
        }

        if (! config('form-builder.required_indicator', true)) {
            return false;
        }

        return $this->requiredByRules();
    }

    protected function requiredByRules(): bool
    {
        foreach ($this->getRules() as $rule) {
            if ($rule === 'required') {
                return true;
            }

            if ($rule instanceof RequiredIf && $rule->condition === true) {
                return true;
            }
        }

        return false;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function hideLabel(bool $hidden = true): static
    {
        if ($hidden) {
            $this->attributes['hideLabel'] = true;

            return $this;
        }

        unset($this->attributes['hideLabel']);

        return $this;
    }

    public static function make(?string $name = null): static
    {
        $instance = new static;
        $instance->name = $name;

        return $instance;
    }

    public function placeholder(string $placeholder): static
    {
        $this->attributes['placeholder'] = $placeholder;

        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        if ($readonly) {
            $this->attributes['readonly'] = 'readonly';
        }

        return $this;
    }

    public function required(bool $required = true): static
    {
        if ($required) {
            $this->rules[] = 'required';
        }

        return $this;
    }

    /**
     * Override theme classes for this field only.
     *
     * Keys not given fall back to the form-wide theme, and keys absent there
     * fall back to the renderer's own defaults.
     *
     * @param  array<string, string>  $classes
     */
    public function theme(array $classes): static
    {
        $this->attributes['theme'] = array_merge(
            $this->attributes['theme'] ?? [],
            $classes,
        );

        return $this;
    }

    public function rules(array|string|object ...$rules): static
    {
        $this->rules = collect($rules)
            ->map(function ($item) {
                if (! is_string($item)) {
                    return $item;
                }

                return explode('|', $item);
            })
            ->merge($this->rules)
            ->flatten()
            ->unique()
            ->toArray();

        return $this;
    }

    public function submitsValue(): bool
    {
        return true;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function validationLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [];
    }

    public function prepareForValidation(mixed $value): mixed
    {
        return $value;
    }

    public function normalizeValue(mixed $value): mixed
    {
        return $value;
    }

    public function toSchema(): array
    {
        $schema = [
            'condition' => $this->schemaCondition(),
            ...$this->schemaVisibility(),
            'default' => $this->default,
            'label' => $this->label,
            'name' => $this->getName(),
            'rules' => array_values(array_filter($this->getRules(), 'is_string')),
            'type' => $this->type,
        ];

        if ($this->marksRequired()) {
            $schema['markRequired'] = true;
        }

        return array_merge($schema, $this->attributes);
    }

    private function schemaCondition(): ?string
    {
        $conditions = $this->visibilityConditions();

        if ($conditions === []) {
            return $this->condition;
        }

        return implode(' && ', array_map(self::conditionExpression(...), $conditions));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function schemaVisibility(): array
    {
        if (is_null($this->visibleWhen)) {
            return [];
        }

        if ($this->inheritedVisibility === []) {
            return [
                'when' => $this->visibleWhen,
            ];
        }

        return [
            'when' => [
                ...$this->visibleWhen,
                'also' => $this->inheritedVisibility,
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toSchema();
    }
}
