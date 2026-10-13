<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Number;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Tags;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\Fields\Translatable;

it('serializes its sub-fields into the schema', function () {
    $schema = Repeater::make('ingredients')
        ->addActionLabel('Add ingredient')
        ->fields([
            Number::make('amount'),
            Text::make('name'),
        ])
        ->toSchema();

    expect($schema['type'])->toBe('repeater')
        ->and($schema['name'])->toBe('ingredients')
        ->and($schema['addActionLabel'])->toBe('Add ingredient')
        ->and($schema['fields'])->toHaveCount(2)
        ->and($schema['fields'][0]['name'])->toBe('amount')
        ->and($schema['fields'][0]['type'])->toBe('number')
        ->and($schema['fields'][1]['name'])->toBe('name');
});

it('expands sub-field rules into dotted wildcard rules', function () {
    $nested = Repeater::make('ingredients')
        ->fields([
            Number::make('amount')->rules(['nullable', 'numeric']),
            Text::make('name')->rules(['required', 'string']),
            Text::make('note'),
        ])
        ->nestedRules();

    expect($nested)->toBe([
        'ingredients.*.amount' => ['nullable', 'numeric'],
        'ingredients.*.name' => ['required', 'string'],
    ]);
});

it('merges nested repeater rules into the form rules', function () {
    $form = new class extends AbstractForm
    {
        public function fields(): array
        {
            return [
                Text::make('title')->rules(['required', 'string']),
                Repeater::make('steps')->fields([
                    Text::make('text')->rules(['required', 'string']),
                ]),
            ];
        }
    };

    expect($form::rules())->toBe([
        'title' => ['required', 'string'],
        'steps' => ['array'],
        'steps.*.text' => ['required', 'string'],
    ]);
});

it('prefixes the nested rules of its sub-fields', function () {
    $nested = Repeater::make('steps')
        ->fields([
            Translatable::make('title')->locales(['en', 'nl'])->required(),
        ])
        ->nestedRules();

    expect($nested)->toBe([
        'steps.*.title' => ['required', 'array'],
        'steps.*.title.en' => ['required'],
        'steps.*.title.nl' => ['nullable'],
    ]);
});

it('prepares each row for validation and leaves other values alone', function () {
    $repeater = Repeater::make('rows')->fields([
        Tags::make('labels'),
        Text::make('note'),
    ]);

    expect($repeater->prepareForValidation([['labels' => 'a, b', 'note' => ' x '], 'not a row']))
        ->toBe([['labels' => ['a', 'b'], 'note' => ' x '], 'not a row'])
        ->and($repeater->prepareForValidation('not rows'))->toBe('not rows');
});
