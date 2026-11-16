<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

class ErrorBagForm extends AbstractForm
{
    public function fields(): array
    {
        return [
            Text::make('email')->rules(['required', 'email']),
        ];
    }

    public function configure(FormConfig $form): void
    {
        $form->errorBag('newsletter');
    }
}
