<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasTextfieldOptions;

class Number extends BaseField
{
    use HasTextfieldOptions;

    protected string $type = 'number';

    public function minValue(int|float $value): static
    {
        $this->attributes['min'] = $value;
        $this->rules[] = 'min:'.$value;

        return $this;
    }

    public function maxValue(int|float $value): static
    {
        $this->attributes['max'] = $value;
        $this->rules[] = 'max:'.$value;

        return $this;
    }

    public function step(int|float|string $step = 1): static
    {
        $this->attributes['step'] = $step;

        return $this;
    }

    public function stepper(bool $stepper = true): static
    {
        $this->attributes['stepper'] = $stepper;

        return $this;
    }

    public function unsigned(): static
    {
        $this->minValue(0);

        return $this;
    }

    public function toSchema(): array
    {
        $this->attributes['decrementLabel'] ??= trans('form-builder::fields.decrement');
        $this->attributes['incrementLabel'] ??= trans('form-builder::fields.increment');

        return parent::toSchema();
    }
}
