<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasInline;
use TranquilTools\FormBuilder\Fields\Traits\HasOptions;

class Checkboxes extends BaseField
{
    use HasInline;
    use HasOptions;

    protected string $type = 'checkboxes';

    public function buttons(bool $buttons = true): static
    {
        $this->attributes['buttons'] = $buttons;

        return $this;
    }

    /**
     * @param  array<string, string>  $columns
     */
    public function columns(array $columns): static
    {
        if ($columns === []) {
            unset($this->attributes['optionColumns']);

            return $this;
        }

        $this->attributes['optionColumns'] = array_map(
            fn (int|string $key, string $label): array => [
                'key' => (string) $key,
                'label' => $label,
            ],
            array_keys($columns),
            $columns,
        );

        return $this;
    }

    public function selectAll(bool $selectAll = true): static
    {
        if (! $selectAll) {
            unset($this->attributes['selectAll']);

            return $this;
        }

        $this->attributes['selectAll'] = true;

        return $this;
    }
}
