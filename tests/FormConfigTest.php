<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\DateTime;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\Fields\Translatable;
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

it('sends the form columns only when they are set', function () {
    expect(FormConfig::make()->jsonSerialize())->not->toHaveKey('columns')
        ->and(FormConfig::make()->columns(3)->jsonSerialize()['columns'])->toBe(3)
        ->and(FormConfig::make()->columns(9)->getColumns())->toBe(4)
        ->and(FormConfig::make()->columns(0)->getColumns())->toBe(1)
        ->and(FormConfig::make()->columns(2)->columns(null)->jsonSerialize())->not->toHaveKey('columns');
});

it('names fields by their key in validation messages by default', function () {
    $form = FormConfig::make([
        Text::make('arrival_on')->label('Arrival')->rules(['required']),
    ]);

    expect($form->getAttributes())->toBe([]);

    try {
        $form->validate(Request::create('/', 'POST', []));
    } catch (ValidationException $e) {
        expect($e->errors()['arrival_on'][0])->toBe('The arrival on field is required.');

        return;
    }

    $this->fail('The field should have been required.');
});

it('names fields by their label in validation messages when asked', function () {
    $form = FormConfig::make([
        Section::make()->fields([
            Text::make('arrival_on')->label('Arrival')->rules(['required']),
        ]),
        Text::make('notes'),
        Radio::make('plan')->value('starter')->label('Starter'),
        Radio::make('plan')->value('pro')->label('Pro'),
        Translatable::make('title')->label('Title')->locales(['en']),
    ])->labelsAsAttributes();

    expect($form->getAttributes())->toBe([
        'arrival_on' => 'Arrival',
        'title' => 'Title',
        'title.en' => 'Title (English)',
    ]);

    try {
        $form->validate(Request::create('/', 'POST', []));
    } catch (ValidationException $e) {
        expect($e->errors()['arrival_on'][0])->toBe('The Arrival field is required.');

        return;
    }

    $this->fail('The field should have been required.');
});

it('lets explicit attributes win over labels', function () {
    $form = FormConfig::make([
        Text::make('arrival_on')->label('Arrival')->rules(['required']),
    ])->labelsAsAttributes();

    try {
        $form->validate(Request::create('/', 'POST', []), [], ['arrival_on' => 'day of arrival']);
    } catch (ValidationException $e) {
        expect($e->errors()['arrival_on'][0])->toBe('The day of arrival field is required.');

        return;
    }

    $this->fail('The field should have been required.');
});

it('names fields by their label for every form when configured', function () {
    config()->set('form-builder.validation.label_attributes', true);

    $fields = [
        Text::make('arrival_on')->label('Arrival'),
    ];

    expect(FormConfig::make($fields)->getAttributes())->toBe(['arrival_on' => 'Arrival'])
        ->and(FormConfig::make($fields)->labelsAsAttributes(false)->getAttributes())->toBe([]);
});
