<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\FormConfig;

it('rejects a disabled option given as a row', function () {
    $form = FormConfig::make([
        Select::make('unit')->options([
            ['value' => 'hall', 'label' => 'Hall'],
            ['value' => 'attic', 'label' => 'Attic', 'disabled' => true],
        ]),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['unit' => 'hall'])))->toBe(['unit' => 'hall'])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['unit' => 'attic'])))
        ->toThrow(ValidationException::class);
});

it('rejects a disabled option given in a map, a group or under the field option keys', function (Select $select) {
    $form = FormConfig::make([$select]);

    expect($form->validate(Request::create('/', 'POST', ['unit' => '1'])))->toBe(['unit' => '1'])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['unit' => '2'])))
        ->toThrow(ValidationException::class);
})->with([
    'map' => fn () => Select::make('unit')->options([
        1 => 'Hall',
        2 => ['label' => 'Attic', 'disabled' => true],
    ]),
    'group' => fn () => Select::make('unit')->optionGroups([
        'Floors' => [
            ['id' => 1, 'name' => 'Hall'],
            ['id' => 2, 'name' => 'Attic', 'disabled' => true],
        ],
    ]),
    'collection' => fn () => Select::make('unit')->options(collect([
        ['id' => 1, 'name' => 'Hall'],
        ['id' => 2, 'name' => 'Attic', 'disabled' => true],
    ])),
]);

it('rejects a disabled value among the values of a multiple select', function () {
    $form = FormConfig::make([
        Select::make('units')->multiple()->options([
            ['value' => 1, 'label' => 'Hall'],
            ['value' => 2, 'label' => 'Attic', 'disabled' => true],
        ]),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['units' => ['1']])))->toBe(['units' => ['1']])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['units' => ['1', '2']])))
        ->toThrow(ValidationException::class);
});

it('rejects a dependent option only where it is disabled', function () {
    $form = FormConfig::make([
        Select::make('post_id')->optionsFor('direction', [
            'income' => [
                ['id' => 9, 'name' => 'Bar'],
                ['id' => 4, 'name' => 'Old bar', 'disabled' => true],
            ],
            'expense' => [
                ['id' => 4, 'name' => 'Bar costs'],
            ],
        ]),
    ]);

    expect(fn () => $form->validate(Request::create('/', 'POST', ['direction' => 'income', 'post_id' => '4'])))
        ->toThrow(ValidationException::class)
        ->and($form->validate(Request::create('/', 'POST', ['direction' => 'expense', 'post_id' => '4'])))
        ->toBe(['post_id' => '4']);
});

it('places a separator between options without making it a value', function () {
    $select = Select::make('country')->options([
        ['value' => 'nl', 'label' => 'Netherlands'],
        Select::separator(),
        ['value' => 'de', 'label' => 'Germany'],
    ]);

    expect($select->toSchema()['options'][1])->toBe(['separator' => true, 'disabled' => true])
        ->and($select->getRules())->toBe([]);
});

it('adds no rule without disabled options', function () {
    expect(Select::make('unit')->options(['a' => 'A'])->getRules())->toBe([]);
});
