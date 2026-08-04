<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Submit extends Button
{
    protected string $type = 'submit';

    public function toSchema(): array
    {
        $this->label ??= trans('form-builder::buttons.save');

        return parent::toSchema();
    }
}
