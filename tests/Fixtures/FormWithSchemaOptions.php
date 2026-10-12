<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Date;
use TranquilTools\FormBuilder\Fields\Translatable;
use TranquilTools\FormBuilder\FormConfig;

class FormWithSchemaOptions extends AbstractForm
{
    public function __construct(
        private readonly int $columns,
    ) {}

    public function configure(FormConfig $form): void
    {
        $form
            ->columns($this->columns)
            ->labelsAsAttributes();
    }

    public function fields(): array
    {
        return [
            Date::make('arrival_on')
                ->label('Arrival')
                ->minDate('2026-10-01')
                ->maxDate('2026-10-31')
                ->format('ddd D MMM YYYY')
                ->validateBounds(),
            Translatable::make('title')
                ->label('Title')
                ->localeLabels(['nl' => 'Nederlands']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messageAttributes(): array
    {
        return self::attributes(2);
    }
}
