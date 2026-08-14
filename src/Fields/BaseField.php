<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;

abstract class BaseField implements JsonSerializable
{
    use Macroable;

    protected string $type;

    private ?string $name = null;

    protected ?string $label = null;

    protected ?string $condition = null;

    protected array $rules = [];

    protected array $attributes = [];

    protected mixed $default = null;

    public function attributes(array $attributes): static
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    public function class(string $className): static
    {
        $this->attributes['className'] = $className;

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

    public function if(string $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function help(string|HtmlString $text): static
    {
        $this->attributes['help'] = $text instanceof HtmlString ? $text->toHtml() : e($text);

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

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

    public function rules(array|string ...$rules): static
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function toSchema(): array
    {
        $schema = [
            'condition' => $this->condition,
            'default' => $this->default,
            'label' => $this->label,
            'name' => $this->getName(),
            'rules' => $this->getRules(),
            'type' => $this->type,
        ];

        return array_merge($schema, $this->attributes);
    }

    public function jsonSerialize(): array
    {
        return $this->toSchema();
    }
}
