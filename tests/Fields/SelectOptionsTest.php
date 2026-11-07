<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Select;

it('sends the order of options given as a map', function () {
    $schema = Select::make('renter_id')
        ->options([
            12 => 'Anna',
            3 => 'Bram',
            'x' => 'Cor',
        ])
        ->toSchema();

    expect($schema['options'])->toBe([
        12 => 'Anna',
        3 => 'Bram',
        'x' => 'Cor',
    ])
        ->and($schema['optionOrder'])->toBe(['12', '3', 'x'])
        ->and(Select::make('size')->options(['S', 'M'])->toSchema())->not->toHaveKey('optionOrder');
});
