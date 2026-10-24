<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use LogicException;
use TranquilTools\FormBuilder\Fields\Traits\HasMultiple;
use TranquilTools\FormBuilder\Fields\Traits\HasOptions;

class Select extends BaseField
{
    use HasMultiple;
    use HasOptions;

    protected string $type = 'select';

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

    public function selected(string|int|null $value, ?string $label = null): static
    {
        if (is_null($value) || $value === '') {
            unset($this->attributes['selected']);

            return $this;
        }

        $this->attributes['selected'] = [
            'value' => $value,
            'label' => is_null($label) || $label === '' ? (string) $value : $label,
        ];

        return $this;
    }

    public function toSchema(): array
    {
        $this->attributes['searchPlaceholder'] ??= trans('form-builder::fields.search-placeholder');
        $this->attributes['noResultsLabel'] ??= trans('form-builder::fields.no-results');
        $this->attributes['choosePlaceholder'] ??= trans('form-builder::fields.choose-option');

        $schema = parent::toSchema();

        if (! isset($schema['searchUrl'])) {
            return $schema;
        }

        if ($schema['multiple'] ?? false) {
            throw new LogicException("Select [{$this->getName()}] cannot search on the server and select multiple options.");
        }

        unset($schema['options']);

        return $schema;
    }
}
