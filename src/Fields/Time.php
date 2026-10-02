<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Time extends BaseField
{
    protected string $type = 'time';

    protected array $rules = [
        'date_format:H:i',
    ];

    public function step(int $minutes): static
    {
        $this->attributes['step'] = max(1, min($minutes, 720));

        return $this;
    }

    public function min(string $time): static
    {
        $this->attributes['min'] = $time;
        $this->rules[] = 'after_or_equal:'.$time;

        return $this;
    }

    public function max(string $time): static
    {
        $this->attributes['max'] = $time;
        $this->rules[] = 'before_or_equal:'.$time;

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->attributes['clearable'] = $clearable;

        return $this;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        if (in_array('required', $rules, true) || in_array('nullable', $rules, true)) {
            return $rules;
        }

        return [
            'nullable',
            ...$rules,
        ];
    }

    public function toSchema(): array
    {
        $this->attributes['step'] ??= 15;
        $this->attributes['clearable'] ??= ! in_array('required', $this->getRules(), true);
        $this->attributes['clearLabel'] ??= trans('form-builder::fields.clear');

        return parent::toSchema();
    }
}
