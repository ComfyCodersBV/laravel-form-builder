<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Text;

class FormWithRequiredArguments extends AbstractForm
{
    public function __construct(
        private readonly string $fieldName,
        private readonly bool $required,
    ) {}

    public function fields(): array
    {
        return [
            Text::make($this->fieldName)->rules($this->required ? ['required', 'string'] : ['nullable', 'string']),
        ];
    }
}
