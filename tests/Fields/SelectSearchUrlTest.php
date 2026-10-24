<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Select;

it('describes a select that searches its options on the server', function () {
    $schema = Select::make('accessor')
        ->searchUrl('https://app.test/accessors')
        ->minSearchLength(2)
        ->selected('user:5', 'Jane Doe')
        ->toSchema();

    expect($schema['type'])->toBe('select')
        ->and($schema['searchUrl'])->toBe('https://app.test/accessors')
        ->and($schema['minSearchLength'])->toBe(2)
        ->and($schema['selected'])->toBe([
            'value' => 'user:5',
            'label' => 'Jane Doe',
        ]);
});

it('never sends the options of a select that searches on the server', function () {
    $schema = Select::make('accessor')
        ->options([
            '1' => 'Alice',
            '2' => 'Bob',
        ])
        ->searchUrl('https://app.test/accessors')
        ->toSchema();

    expect($schema)->not->toHaveKey('options');
});

it('keeps the options of a select without a search url', function () {
    $schema = Select::make('accessor')
        ->options([
            '1' => 'Alice',
        ])
        ->searchUrl('https://app.test/accessors')
        ->searchUrl(null)
        ->toSchema();

    expect($schema['options'])->toBe([
        '1' => 'Alice',
    ])
        ->and($schema)->not->toHaveKey('searchUrl');
});

it('ignores an empty search url', function () {
    expect(Select::make('accessor')->searchUrl('')->toSchema())->not->toHaveKey('searchUrl');
});

it('uses the value as label of a selected option without one and forgets an empty selection', function () {
    expect(Select::make('accessor')->searchUrl('/search')->selected(7)->toSchema()['selected'])->toBe([
        'value' => 7,
        'label' => '7',
    ])
        ->and(Select::make('accessor')->searchUrl('/search')->selected(7, 'Seven')->selected(null)->toSchema())
        ->not->toHaveKey('selected');
});

it('needs at least one character before searching', function () {
    expect(Select::make('accessor')->searchUrl('/search')->minSearchLength(0)->toSchema()['minSearchLength'])->toBe(1);
});

it('refuses a multiple select that searches on the server', function () {
    Select::make('accessors')
        ->multiple()
        ->searchUrl('/search')
        ->toSchema();
})->throws(LogicException::class);
