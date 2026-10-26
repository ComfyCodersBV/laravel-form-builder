<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Select;

it('describes a clearable select with a translated clear label', function () {
    $schema = Select::make('country')
        ->options([
            'nl' => 'The Netherlands',
        ])
        ->clearable()
        ->toSchema();

    expect($schema['clearable'])->toBeTrue()
        ->and($schema['clearLabel'])->toBe(trans('form-builder::fields.clear'));
});

it('keeps a select without clearable free of a clear button', function () {
    $schema = Select::make('country')
        ->clearable(false)
        ->toSchema();

    expect($schema['clearable'])->toBeFalse()
        ->and($schema)->not->toHaveKey('clearLabel');

    expect(Select::make('country')->toSchema())->not->toHaveKeys([
        'clearable',
        'clearLabel',
    ]);
});

it('keeps a clear label that is set on the field', function () {
    $schema = Select::make('country')
        ->clearable()
        ->attributes([
            'clearLabel' => 'Clear country',
        ])
        ->toSchema();

    expect($schema['clearLabel'])->toBe('Clear country');
});
