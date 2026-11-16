<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use InvalidArgumentException;

class Submit extends Button
{
    protected string $type = 'submit';

    public function emit(string $event): static
    {
        throw new InvalidArgumentException(sprintf(
            'A submit button submits the form and cannot emit "%s". Use a Button with ->emit() for an action that does not submit, or listen to @success on the form.',
            $event,
        ));
    }

    public function toSchema(): array
    {
        $this->label ??= trans('form-builder::buttons.save');

        return parent::toSchema();
    }
}
