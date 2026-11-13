<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

class RequestBackedForm extends AbstractForm
{
    public static int $builds = 0;

    public function fields(): array
    {
        self::$builds++;

        return [
            Text::make('title')->label('Title')->rules(['required', 'string']),
        ];
    }

    public function configure(FormConfig $form): void
    {
        $form->labelsAsAttributes();
    }
}
