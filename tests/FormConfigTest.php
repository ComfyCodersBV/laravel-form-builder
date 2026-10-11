<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\DateTime;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('collects rules keyed by field name', function () {
    $config = FormConfig::make([
        Text::make('title')->rules(['required', 'string']),
        Select::make('category_id')->rules(['nullable']),
    ]);

    expect($config->getRules())->toBe([
        'title' => ['required', 'string'],
        'category_id' => ['nullable'],
    ]);
});

it('skips fields without a name in the rules', function () {
    $config = FormConfig::make([
        Text::make('title')->rules(['required']),
        Text::make()->rules(['string']),
    ]);

    expect($config->getRules())->toBe([
        'title' => ['required'],
    ]);
});

it('defaults method to POST and reflects overrides', function () {
    expect(FormConfig::make()->getMethod())->toBe('POST')
        ->and(FormConfig::make()->method('PUT')->getMethod())->toBe('PUT');
});

it('stores action and class', function () {
    $config = FormConfig::make()
        ->action('/products')
        ->class('space-y-4');

    expect($config->getAction())->toBe('/products')
        ->and($config->getClass())->toBe('space-y-4');
});

it('serializes to a schema payload', function () {
    $payload = FormConfig::make([
        Text::make('title')->label('Title'),
    ])
        ->action('/products')
        ->method('PUT')
        ->jsonSerialize();

    expect($payload)->toHaveKeys(['id', 'action', 'defaults', 'fields', 'formClass', 'method'])
        ->and($payload['action'])->toBe('/products')
        ->and($payload['method'])->toBe('PUT')
        ->and($payload['fields'])->toHaveCount(1)
        ->and($payload['fields'][0]['name'])->toBe('title');
});

it('emits an empty object for defaults when no data is filled', function () {
    $payload = FormConfig::make([Text::make('title')])->jsonSerialize();

    expect($payload['defaults'])->toEqual((object) []);
});

it('exposes filled data as form defaults', function () {
    $payload = FormConfig::make([Text::make('title')])
        ->fill(['title' => 'Hello'])
        ->jsonSerialize();

    expect($payload['defaults'])->toBe(['title' => 'Hello']);
});

it('normalizes radio fields into a single group when serializing', function () {
    $payload = FormConfig::make([
        Radio::make('plan')->value('starter')->label('Starter'),
        Radio::make('plan')->value('pro')->label('Pro'),
    ])->jsonSerialize();

    expect($payload['fields'])->toHaveCount(1)
        ->and($payload['fields'][0]['type'])->toBe('radios')
        ->and($payload['fields'][0]['options'])->toHaveCount(2);
});

it('uses an explicit id over the derived one', function () {
    expect(FormConfig::make()->id('custom_form')->getId())->toBe('custom_form');
});

it('returns an empty id without a configurator or explicit id', function () {
    expect(FormConfig::make()->getId())->toBe('');
});

it('exposes filled data via getData', function () {
    $config = FormConfig::make()->fill(['title' => 'Hello']);

    expect($config->getData())->toBe(['title' => 'Hello']);
});

it('defaults getData to an empty array', function () {
    expect(FormConfig::make()->getData())->toBe([]);
});

it('returns false for an unknown option', function () {
    expect(FormConfig::make()->getOption('missing'))->toBeFalse();
});

it('validates a request against the collected rules', function () {
    $config = FormConfig::make([
        Text::make('title')->rules(['required', 'string']),
    ]);

    $request = Request::create('/', 'POST', ['title' => 'Hello']);

    expect($config->validate($request))->toBe(['title' => 'Hello']);
});

it('throws when validation fails', function () {
    $config = FormConfig::make([
        Text::make('title')->rules(['required']),
    ]);

    $config->validate(Request::create('/', 'POST', []));
})->throws(ValidationException::class);

it('emits the application locale unless one is set', function () {
    app()->setLocale('nl');

    expect(FormConfig::make()->jsonSerialize()['locale'])->toBe('nl')
        ->and(FormConfig::make()->locale('de')->jsonSerialize()['locale'])->toBe('de');
});

it('converts date values to the application timezone while validating', function () {
    config()->set('app.timezone', 'UTC');

    $config = FormConfig::make([
        Section::make()->fields([
            DateTime::make('starts_at')->timezone('Europe/Amsterdam')->rules(['required', 'date']),
        ]),
        Text::make('title'),
    ]);

    $validated = $config->validate(Request::create('/', 'POST', [
        'starts_at' => '2026-07-01T10:00:00+02:00',
    ]));

    expect($validated)->toBe(['starts_at' => '2026-07-01 08:00:00']);
});
