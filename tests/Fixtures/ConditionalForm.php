<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Text;

class ConditionalForm extends AbstractForm
{
    public function fields(): array
    {
        return [
            Radio::make('action')
                ->options([
                    'rebook' => 'Rebook',
                    'return' => 'Return',
                ])
                ->rules(['required']),
            Text::make('destination')
                ->if('action', 'rebook')
                ->rules(['required', 'string']),
            Repeater::make('barrels')->fields([
                Text::make('kind')->rules(['required']),
                Text::make('weight')
                    ->if('kind', 'full')
                    ->rules(['required', 'numeric']),
            ]),
        ];
    }
}
