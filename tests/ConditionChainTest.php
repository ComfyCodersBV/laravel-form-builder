<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Checkbox;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

function chainedForm(): FormConfig
{
    return FormConfig::make([
        Radio::make('action')
            ->options([
                'rebook' => 'Rebook',
                'return' => 'Return',
            ])
            ->rules(['required']),
        Section::make()->if('action', 'rebook')->fields([
            Checkbox::make('express'),
        ]),
        Text::make('courier')->if('express', true)->rules(['required']),
        Text::make('tracking')->if('courier', 'other')->rules(['required']),
    ]);
}

it('leaves a field out while the field it depends on is hidden itself', function () {
    $input = [
        'action' => 'return',
        'express' => '1',
        'courier' => 'other',
    ];

    expect(chainedForm()->validate(Request::create('/', 'POST', $input)))->toBe(['action' => 'return']);
});

it('still validates the chain while every field in it is visible', function () {
    try {
        chainedForm()->validate(Request::create('/', 'POST', [
            'action' => 'rebook',
            'express' => '1',
            'courier' => 'other',
        ]));
        $this->fail('The visible tracking field passed without a value.');
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toBe(['tracking']);
    }
});

it('sends the conditions of the hidden field it depends on to the renderer', function () {
    $fields = chainedForm()->jsonSerialize()['fields'];

    expect($fields[3]['when'])->toBe([
        'field' => 'courier',
        'values' => ['other'],
        'also' => [
            ['field' => 'express', 'values' => ['true', '1']],
            ['field' => 'action', 'values' => ['rebook']],
        ],
    ])
        ->and($fields[2]['when'])->toBe([
            'field' => 'express',
            'values' => ['true', '1'],
            'also' => [
                ['field' => 'action', 'values' => ['rebook']],
            ],
        ]);
});

it('chains the conditions of sub-fields within one repeater row', function () {
    $config = FormConfig::make([
        Repeater::make('barrels')->fields([
            Text::make('kind'),
            Text::make('seal')->if('kind', 'full'),
            Text::make('seal_number')->if('seal', 'yes')->rules(['required']),
        ]),
    ]);

    expect($config->getRules()['barrels.*.seal_number'])->toBe([
        'exclude_unless:barrels.*.seal,yes',
        'exclude_unless:barrels.*.kind,full',
        'required',
    ]);
});

it('stops at conditions that depend on each other', function () {
    $config = FormConfig::make([
        Text::make('first')->if('second', 'b'),
        Text::make('second')->if('first', 'a'),
    ]);

    expect($config->getRules())->toBe([
        'first' => ['exclude_unless:second,b'],
        'second' => ['exclude_unless:first,a'],
    ]);
});

it('does not follow a field name that two fields share', function () {
    $config = FormConfig::make([
        Radio::make('action')->options(['rebook' => 'Rebook', 'return' => 'Return']),
        Text::make('carrier')->if('action', 'rebook'),
        Text::make('carrier'),
        Text::make('tracking')->if('carrier', 'other')->rules(['required']),
    ]);

    expect($config->getRules()['tracking'])->toBe(['exclude_unless:carrier,other', 'required']);
});
