<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasTextfieldOptions;

class Text extends BaseField
{
    use HasTextfieldOptions;

    protected string $type = 'text';

    public function maxLength(int $value): static
    {
        $this->attributes['maxlength'] = $value;
        $this->rules[] = 'max:'.$value;

        return $this;
    }

    public function minLength(int $value): static
    {
        $this->attributes['minlength'] = $value;
        $this->rules[] = 'min:'.$value;

        return $this;
    }

    public function type(string $type): static
    {
        $allowed = [
            'color',
            'email',
            'password',
            'text',
        ];

        $this->type = in_array($type, $allowed, true) ? $type : 'text';

        return $this;
    }

    public function email(): static
    {
        return $this->type('email');
    }

    public function password(): static
    {
        return $this->type('password');
    }
}
