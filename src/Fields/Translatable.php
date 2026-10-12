<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Locale;

class Translatable extends BaseField
{
    protected string $type = 'translatable';

    /** @var list<string> */
    protected array $locales = [];

    /** @var array<string, string> */
    protected array $localeLabels = [];

    protected ?string $defaultLocale = null;

    /** @var list<string>|null */
    protected ?array $requiredLocales = null;

    protected bool $required = false;

    protected ?BaseField $field = null;

    /**
     * @param  list<string>  $locales
     */
    public function locales(array $locales): static
    {
        $this->locales = array_values(array_unique(array_map(strval(...), $locales)));

        return $this;
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function localeLabels(array $labels): static
    {
        $this->localeLabels = array_merge($this->localeLabels, $labels);

        return $this;
    }

    public function defaultLocale(string $locale): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * @param  BaseField|class-string<BaseField>  $field
     */
    public function field(BaseField|string $field): static
    {
        if (is_string($field)) {
            if (! is_subclass_of($field, BaseField::class)) {
                throw new InvalidArgumentException("[{$field}] is not a form-builder field.");
            }

            $field = $field::make();
        }

        $this->field = $field;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @param  list<string>  $locales
     */
    public function requiredLocales(array $locales): static
    {
        $this->required = $locales !== [];
        $this->requiredLocales = $locales;

        return $this;
    }

    public function stacked(bool $stacked = true): static
    {
        $this->attributes['display'] = $stacked ? 'stacked' : 'tabs';

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getLocales(): array
    {
        if ($this->locales !== []) {
            return $this->locales;
        }

        $configured = config('form-builder.translatable.locales');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map(strval(...), $configured));
        }

        return [app()->getLocale()];
    }

    public function getDefaultLocale(): string
    {
        $locales = $this->getLocales();

        if (! is_null($this->defaultLocale) && in_array($this->defaultLocale, $locales, true)) {
            return $this->defaultLocale;
        }

        return in_array(app()->getLocale(), $locales, true) ? app()->getLocale() : $locales[0];
    }

    /**
     * @return list<string>
     */
    public function getRequiredLocales(): array
    {
        if ($this->innerRequired()) {
            return $this->getLocales();
        }

        if (! $this->required) {
            return [];
        }

        if (is_null($this->requiredLocales)) {
            return [$this->getDefaultLocale()];
        }

        return array_values(array_intersect($this->getLocales(), $this->requiredLocales));
    }

    public function getField(): BaseField
    {
        return $this->field ??= Text::make();
    }

    public function getRules(): array
    {
        return array_values(array_unique([
            $this->required ? 'required' : 'nullable',
            'array',
            ...parent::getRules(),
        ]));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function nestedRules(): array
    {
        $name = $this->getName();

        if (is_null($name) || $name === '') {
            return [];
        }

        $innerRules = array_values(array_filter(
            $this->getField()->getRules(),
            fn (mixed $rule): bool => ! in_array($rule, ['required', 'nullable'], true),
        ));

        $required = $this->getRequiredLocales();

        $rules = [];

        foreach ($this->getLocales() as $locale) {
            $rules["{$name}.{$locale}"] = [
                in_array($locale, $required, true) ? 'required' : 'nullable',
                ...$innerRules,
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $name = $this->getName();

        if (is_null($name) || $name === '') {
            return [];
        }

        $label = $this->label ?? str_replace('_', ' ', $name);

        $attributes = [];

        foreach ($this->getLocales() as $locale) {
            $attributes["{$name}.{$locale}"] = trans('form-builder::fields.translatable-attribute', [
                'label' => $label,
                'locale' => $this->localeName($locale),
            ]);
        }

        return $attributes;
    }

    public function localeName(string $locale): string
    {
        if (isset($this->localeLabels[$locale])) {
            return $this->localeLabels[$locale];
        }

        $configured = config('form-builder.translatable.locale_names');

        if (is_array($configured) && is_string($configured[$locale] ?? null)) {
            return $configured[$locale];
        }

        $key = 'form-builder::locales.'.strtolower(str_replace('_', '-', $locale));

        if (Lang::has($key)) {
            return trans($key);
        }

        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayLanguage($locale, app()->getLocale());

            if ($name !== false && $name !== $locale) {
                return mb_convert_case(mb_substr($name, 0, 1), MB_CASE_UPPER).mb_substr($name, 1);
            }
        }

        return strtoupper($locale);
    }

    public function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($this->getLocales() as $locale) {
            $normalized[$locale] = $this->getField()->normalizeValue($value[$locale] ?? null);
        }

        return $normalized;
    }

    public function toSchema(): array
    {
        $field = $this->getField()->toSchema();

        unset($field['name'], $field['condition'], $field['default']);

        return array_merge(parent::toSchema(), [
            'locales' => array_map(
                fn (string $locale): array => [
                    'value' => $locale,
                    'label' => $this->localeLabels[$locale] ?? strtoupper($locale),
                ],
                $this->getLocales(),
            ),
            'defaultLocale' => $this->getDefaultLocale(),
            'requiredLocales' => $this->getRequiredLocales(),
            'field' => $field,
        ]);
    }

    private function innerRequired(): bool
    {
        return in_array('required', $this->getField()->getRules(), true);
    }
}
