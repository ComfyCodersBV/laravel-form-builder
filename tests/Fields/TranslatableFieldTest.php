<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Money;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\Fields\Textarea;
use TranquilTools\FormBuilder\Fields\Translatable;
use TranquilTools\FormBuilder\FormConfig;

it('describes its locales and the field per locale', function () {
    $schema = Translatable::make('body')
        ->locales(['en', 'nl'])
        ->localeLabels(['nl' => 'Nederlands'])
        ->field(Textarea::class)
        ->toSchema();

    expect($schema['type'])->toBe('translatable')
        ->and($schema['locales'])->toBe([
            ['value' => 'en', 'label' => 'EN'],
            ['value' => 'nl', 'label' => 'Nederlands'],
        ])
        ->and($schema['defaultLocale'])->toBe('en')
        ->and($schema['field']['type'])->toBe('textarea')
        ->and($schema['field'])->not->toHaveKey('name');
});

it('falls back to the configured locales and then the app locale', function () {
    expect(Translatable::make('body')->getLocales())->toBe(['en']);

    config()->set('form-builder.translatable.locales', ['nl', 'de']);

    expect(Translatable::make('body')->getLocales())->toBe(['nl', 'de'])
        ->and(Translatable::make('body')->getDefaultLocale())->toBe('nl')
        ->and(Translatable::make('body')->defaultLocale('de')->getDefaultLocale())->toBe('de');
});

it('requires the default locale only', function () {
    $field = Translatable::make('title')->locales(['en', 'nl'])->field(Text::make()->maxLength(80))->required();

    expect($field->getRules())->toBe(['required', 'array'])
        ->and($field->nestedRules())->toBe([
            'title.en' => ['required', 'max:80'],
            'title.nl' => ['nullable', 'max:80'],
        ]);
});

it('requires the locales it is given', function () {
    $field = Translatable::make('title')->locales(['en', 'nl', 'de'])->requiredLocales(['nl', 'de']);

    expect($field->getRequiredLocales())->toBe(['nl', 'de'])
        ->and($field->toSchema()['requiredLocales'])->toBe(['nl', 'de']);
});

it('requires every locale when the inner field is required', function () {
    $field = Translatable::make('title')->locales(['en', 'nl'])->field(Text::make()->required());

    expect($field->getRequiredLocales())->toBe(['en', 'nl'])
        ->and($field->getRules())->toBe(['nullable', 'array']);
});

it('validates per locale', function () {
    $form = FormConfig::make([
        Translatable::make('title')->locales(['en', 'nl'])->required(),
    ]);

    try {
        $form->validate(Request::create('/', 'POST', ['title' => ['en' => '', 'nl' => 'Hallo']]));
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toBe(['title.en']);

        return;
    }

    $this->fail('The missing default locale passed validation.');
});

it('keeps only its own locales and normalizes the inner values', function () {
    $form = FormConfig::make([
        Translatable::make('price')->locales(['en', 'nl'])->field(Money::class),
    ]);

    $validated = $form->validate(Request::create('/', 'POST', [
        'price' => ['en' => '100', 'fr' => '5'],
    ]));

    expect($validated['price'])->toBe(['en' => 100, 'nl' => null]);
});

it('switches to stacked inputs', function () {
    expect(Translatable::make('body')->stacked()->toSchema()['display'])->toBe('stacked');
});

it('refuses a class that is no field', function () {
    Translatable::make('body')->field(stdClass::class);
})->throws(InvalidArgumentException::class);
