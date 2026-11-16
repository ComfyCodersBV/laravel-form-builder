<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use InvalidArgumentException;

class DeleteButton extends Button
{
    protected string $type = 'delete';

    public function emit(string $event): static
    {
        throw new InvalidArgumentException(sprintf(
            'A delete button sends a DELETE request and cannot emit "%s". Use a Button with ->emit() and ->confirmTitle() to handle the action in Vue instead.',
            $event,
        ));
    }

    public function toSchema(): array
    {
        $this->label ??= trans('form-builder::buttons.delete');
        $this->cancelLabel ??= trans('form-builder::buttons.cancel');
        $this->confirmTitle ??= trans('form-builder::buttons.confirm-title');
        $this->confirmMessage ??= trans('form-builder::buttons.confirm-message');

        return parent::toSchema();
    }
}
