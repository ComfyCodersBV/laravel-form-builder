<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('serializes a title, a description and its fields', function () {
    $schema = Section::make()
        ->title('Contact')
        ->description('How we <reach> you')
        ->fields([
            Text::make('email'),
        ])
        ->toSchema();

    expect($schema['type'])->toBe('section')
        ->and($schema['title'])->toBe('Contact')
        ->and($schema['description'])->toBe('How we &lt;reach&gt; you')
        ->and($schema['fields'])->toHaveCount(1)
        ->and($schema['fields'][0]['name'])->toBe('email')
        ->and(Section::make()->description(new HtmlString('<b>Bold</b>'))->toSchema()['description'])->toBe('<b>Bold</b>');
});

it('groups radios inside a section', function () {
    $schema = Section::make()
        ->fields([
            Radio::make('plan')->value('starter'),
            Radio::make('plan')->value('pro'),
        ])
        ->toSchema();

    expect($schema['fields'])->toHaveCount(1)
        ->and($schema['fields'][0]['type'])->toBe('radios');
});

it('contributes the rules of its fields to the form without a rule of its own', function () {
    $rules = FormConfig::make([
        Text::make('name')->rules(['required']),
        Section::make('contact')->fields([
            Text::make('email')->rules(['required', 'email']),
            Section::make()->fields([
                Text::make('phone')->rules(['nullable']),
            ]),
            Repeater::make('links')->fields([
                Text::make('url')->rules(['url']),
            ]),
        ]),
    ])->getRules();

    expect($rules)->toBe([
        'name' => ['required'],
        'email' => ['required', 'email'],
        'phone' => ['nullable'],
        'links' => ['array'],
        'links.*.url' => ['url'],
    ]);
});

it('validates fields inside a section', function () {
    $validated = FormConfig::make([
        Section::make()->fields([
            Text::make('email')->rules(['required']),
        ]),
    ])->validate(Request::create('/', 'POST', ['email' => 'jane@example.com']));

    expect($validated)->toBe(['email' => 'jane@example.com']);
});
