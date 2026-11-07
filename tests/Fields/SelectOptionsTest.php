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

it('sends grouped options as rows carrying their group', function () {
    $schema = Select::make('post_id')
        ->optionGroups([
            'Costs' => [
                7 => 'Rent',
                2 => 'Energy',
            ],
            'Income' => collect([
                ['id' => 9, 'name' => 'Bar'],
            ]),
        ])
        ->toSchema();

    expect($schema['options'])->toBe([
        ['id' => '7', 'name' => 'Rent', 'group' => 'Costs'],
        ['id' => '2', 'name' => 'Energy', 'group' => 'Costs'],
        ['id' => 9, 'name' => 'Bar', 'group' => 'Income'],
    ])
        ->and($schema)->not->toHaveKey('optionOrder');
});

it('uses the option keys of the field for grouped rows', function () {
    $schema = Select::make('post_id')
        ->optionGroups([
            'Costs' => [7 => 'Rent'],
        ])
        ->optionValue('value')
        ->optionLabel('label')
        ->toSchema();

    expect($schema['options'])->toBe([
        ['value' => '7', 'label' => 'Rent', 'group' => 'Costs'],
    ]);
});
