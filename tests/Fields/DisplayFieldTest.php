<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use TranquilTools\FormBuilder\Fields\Display;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('is never validated or returned, also inside a section or a repeater', function () {
    $form = FormConfig::make([
        Text::make('prefix'),
        Display::make('preview')->rules('required'),
        Section::make()->fields([
            Display::make('section_preview'),
        ]),
        Repeater::make('rows')->fields([
            Text::make('code'),
            Display::make('row_preview'),
        ]),
    ]);

    $validated = $form->validate(Request::create('/', 'POST', [
        'prefix' => 'AB',
        'preview' => 'sent anyway',
        'section_preview' => 'sent anyway',
        'rows' => [['code' => 'x']],
    ]));

    expect($form->getRules())->not->toHaveKeys(['preview', 'section_preview', 'rows.*.row_preview'])
        ->and($validated)->toBe([
            'prefix' => 'AB',
            'rows' => [['code' => 'x']],
        ]);
});

it('is not marked required', function () {
    expect(Display::make('preview')->required()->toSchema())->not->toHaveKey('markRequired');
});
