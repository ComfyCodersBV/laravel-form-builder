<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Step;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('serializes as a step with a title and its fields', function () {
    $schema = Step::make('contact')
        ->title('Contact')
        ->fields([
            Text::make('email'),
        ])
        ->toSchema();

    expect($schema['type'])->toBe('step')
        ->and($schema['name'])->toBe('contact')
        ->and($schema['title'])->toBe('Contact')
        ->and($schema['fields'][0]['name'])->toBe('email');
});

it('contributes the rules of its fields, also from a section inside it, without a rule of its own', function () {
    $form = FormConfig::make([
        Step::make('when')->fields([
            Text::make('date')->label('Date')->rules(['required']),
        ]),
        Step::make('who')->fields([
            Section::make()->fields([
                Text::make('email')->label('E-mail')->rules(['required', 'email']),
            ]),
        ]),
    ])->labelsAsAttributes();

    expect($form->getRules())->toBe([
        'date' => ['required'],
        'email' => ['required', 'email'],
    ])->and($form->getAttributes())->toHaveKeys([
        'date',
        'email',
    ]);
});

it('tells the renderer to validate each step on the server only when asked', function () {
    expect(FormConfig::make()->jsonSerialize()['validateSteps'])->toBeFalse()
        ->and(FormConfig::make()->validateSteps()->jsonSerialize()['validateSteps'])->toBeTrue();
});
