<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Button;
use TranquilTools\FormBuilder\Fields\File;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Step;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('refuses a field named after a property of the Inertia form', function (string $name) {
    FormConfig::make([
        Text::make($name),
    ])->jsonSerialize();
})->with([
    'data',
    'errors',
    'reset',
    'submit',
    'post',
    'delete',
    'processing',
    'transform',
    'validate',
    'touch',
    'setErrors',
    'query',
    'only',
])->throws(InvalidArgumentException::class);

it('names the field and the way out in the exception', function () {
    expect(fn () => FormConfig::make([Text::make('errors')])->jsonSerialize())
        ->toThrow(InvalidArgumentException::class, 'The field "errors" uses a name the Inertia form (useForm) reserves');
});

it('refuses a reserved name inside a section or step', function () {
    FormConfig::make([
        Step::make('details')->fields([
            Section::make()->fields([
                Text::make('get'),
            ]),
        ]),
    ])->jsonSerialize();
})->throws(InvalidArgumentException::class, '"get"');

it('refuses a named button, whose name the renderer also puts on the form', function () {
    FormConfig::make([
        Button::make('delete'),
    ])->jsonSerialize();
})->throws(InvalidArgumentException::class, '"delete"');

it('refuses a reserved remove flag', function () {
    FormConfig::make([
        File::make('avatar')->removable(flag: 'reset'),
    ])->jsonSerialize();
})->throws(InvalidArgumentException::class, 'remove flag "reset" of field "avatar"');

it('accepts reserved words inside a dotted name or a repeater row', function () {
    $schema = FormConfig::make([
        Text::make('data.street'),
        Text::make('database'),
        Repeater::make('lines')->fields([
            Text::make('errors'),
        ]),
    ])->jsonSerialize();

    expect($schema['fields'])->toHaveCount(3);
});
