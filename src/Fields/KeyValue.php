<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class KeyValue extends BaseField
{
    protected string $type = 'keyvalue';

    public function __construct()
    {
        $this->rules([
            'array',
        ]);

        $this->attributes['maskedKeyPattern'] = config('form-builder.key_value.masked_key_pattern', 'password|secret|token');
    }

    public function keyLabel(string $label): static
    {
        $this->attributes['keyLabel'] = $label;

        return $this;
    }

    public function valueLabel(string $label): static
    {
        $this->attributes['valueLabel'] = $label;

        return $this;
    }

    public function keyPlaceholder(string $placeholder): static
    {
        $this->attributes['keyPlaceholder'] = $placeholder;

        return $this;
    }

    public function valuePlaceholder(string $placeholder): static
    {
        $this->attributes['valuePlaceholder'] = $placeholder;

        return $this;
    }

    public function maskedValuePlaceholder(string $placeholder): static
    {
        $this->attributes['maskedValuePlaceholder'] = $placeholder;

        return $this;
    }

    /**
     * Regex source (no delimiters, matched case-insensitively) used to detect which
     * row keys render their value input masked. Overrides the
     * `vue-form-builder.key_value.masked_key_pattern` config default for this field.
     */
    public function maskedKeyPattern(string $pattern): static
    {
        $this->attributes['maskedKeyPattern'] = $pattern;

        return $this;
    }

    public function addActionLabel(string $label): static
    {
        $this->attributes['addActionLabel'] = $label;

        return $this;
    }

    public function editableKeys(bool $editable = true): static
    {
        $this->attributes['editableKeys'] = $editable;

        return $this;
    }

    public function editableValues(bool $editable = true): static
    {
        $this->attributes['editableValues'] = $editable;

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

    public function readonlyValueKeys(array $keys): static
    {
        $this->attributes['readonlyValueKeys'] = $keys;

        return $this;
    }
}
