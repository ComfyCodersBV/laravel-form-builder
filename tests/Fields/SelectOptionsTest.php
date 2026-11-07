<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\FormConfig;

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

it('sends the options per value of the field it depends on', function () {
    $schema = Select::make('post_id')
        ->optionsFor('direction', [
            'expense' => [7 => 'Rent', 2 => 'Energy'],
            'income' => [9 => 'Bar'],
        ])
        ->toSchema();

    expect($schema)->not->toHaveKey('options')
        ->and($schema['dependentOptions'])->toBe([
            'field' => 'direction',
            'options' => [
                'expense' => [
                    ['id' => '7', 'name' => 'Rent'],
                    ['id' => '2', 'name' => 'Energy'],
                ],
                'income' => [
                    ['id' => '9', 'name' => 'Bar'],
                ],
            ],
        ]);
});

it('only accepts an option of the value the field depends on', function () {
    $form = FormConfig::make([
        Radio::make('direction')->options(['expense' => 'Expense', 'income' => 'Income']),
        Select::make('post_id')
            ->optionsFor('direction', [
                'expense' => [7 => 'Rent'],
                'income' => [9 => 'Bar'],
            ])
            ->rules(['required']),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['direction' => 'income', 'post_id' => '9'])))
        ->toBe(['direction' => 'income', 'post_id' => '9'])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['direction' => 'income', 'post_id' => '7'])))
        ->toThrow(ValidationException::class)
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['post_id' => '7'])))
        ->toThrow(ValidationException::class)
        ->and(Select::make('post_id')->toSchema()['rules'])->toBe([]);
});

it('checks every value of a multiple dependent select', function () {
    $form = FormConfig::make([
        Select::make('posts')
            ->multiple()
            ->optionsFor('direction', ['income' => [9 => 'Bar', 4 => 'Rent']]),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['direction' => 'income', 'posts' => ['9', '4']])))
        ->toBe(['posts' => ['9', '4']])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['direction' => 'income', 'posts' => ['9', '1']])))
        ->toThrow(ValidationException::class);
});

it('reads the value it depends on from the same repeater row', function () {
    $form = FormConfig::make([
        Repeater::make('lines')->fields([
            Select::make('kind'),
            Select::make('post_id')->optionsFor('kind', ['income' => [9 => 'Bar'], 'expense' => [7 => 'Rent']]),
        ]),
    ]);

    $valid = Request::create('/', 'POST', ['lines' => [
        ['kind' => 'income', 'post_id' => '9'],
        ['kind' => 'expense', 'post_id' => '7'],
    ]]);

    $invalid = Request::create('/', 'POST', ['lines' => [
        ['kind' => 'expense', 'post_id' => '9'],
    ]]);

    expect($form->validate($valid)['lines'])->toHaveCount(2)
        ->and(fn () => $form->validate($invalid))->toThrow(ValidationException::class);
});

it('refuses a dependent select that searches on the server', function () {
    Select::make('post_id')
        ->optionsFor('direction', [])
        ->searchUrl('/search')
        ->toSchema();
})->throws(LogicException::class);

it('reads a boolean value it depends on as 1 or 0', function () {
    $form = FormConfig::make([
        Select::make('plan')->optionsFor('member', [
            1 => ['member' => 'Member price'],
            0 => ['guest' => 'Guest price'],
        ]),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['member' => true, 'plan' => 'member'])))
        ->toBe(['plan' => 'member'])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['member' => false, 'plan' => 'member'])))
        ->toThrow(ValidationException::class);
});
