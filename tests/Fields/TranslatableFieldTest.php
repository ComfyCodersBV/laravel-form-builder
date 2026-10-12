<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Money;
use TranquilTools\FormBuilder\Fields\Section;
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

it('names every locale input after its label and locale', function () {
    $form = FormConfig::make([
        Translatable::make('title')->label('Title')->locales(['nl', 'en', 'xx']),
        Translatable::make('meta_description')->locales(['nl']),
        Text::make('slug')->label('Slug'),
    ]);

    expect($form->getAttributes())->toBe([
        'title.nl' => 'Title (Dutch)',
        'title.en' => 'Title (English)',
        'title.xx' => 'Title (XX)',
        'meta_description.nl' => 'meta description (Dutch)',
    ]);

    app()->setLocale('nl');

    expect($form->getAttributes()['title.nl'])->toBe('Title (Nederlands)');
});

it('takes locale names from the config', function () {
    config()->set('form-builder.translatable.locale_names', ['nl' => 'NL-BE']);

    expect(Translatable::make('title')->label('Title')->locales(['nl', 'en'])->validationAttributes())->toBe([
        'title.nl' => 'Title (NL-BE)',
        'title.en' => 'Title (English)',
    ]);
});

it('uses the attribute names in its validation messages', function () {
    $form = FormConfig::make([
        Translatable::make('title')->label('Title')->locales(['nl', 'en'])->requiredLocales(['nl', 'en']),
    ]);

    try {
        $form->validate(Request::create('/', 'POST', ['title' => ['nl' => 'Hallo']]), [], ['title.nl' => 'Own name']);
    } catch (ValidationException $e) {
        expect($e->errors()['title.en'][0])->toBe('The Title (English) field is required.');

        return;
    }

    $this->fail('The English title should have been required.');
});

it('collects the attribute names inside sections and from a form class', function () {
    $form = new class extends AbstractForm
    {
        public function fields(): array
        {
            return [
                Section::make()->fields([
                    Translatable::make('title')->label('Title')->locales(['en']),
                ]),
            ];
        }
    };

    expect($form::attributes())->toBe(['title.en' => 'Title (English)']);
});

it('prefers its own locale labels over the config, the translations and intl', function () {
    config()->set('form-builder.translatable.locale_names', ['nl' => 'NL-BE', 'de' => 'Deutsch']);

    $field = Translatable::make('title')
        ->label('Title')
        ->locales(['nl', 'en', 'de', 'fr'])
        ->localeLabels([
            'nl' => 'Nederlands',
            'en' => 'Brits',
        ]);

    expect($field->validationAttributes())->toBe([
        'title.nl' => 'Title (Nederlands)',
        'title.en' => 'Title (Brits)',
        'title.de' => 'Title (Deutsch)',
        'title.fr' => 'Title (French)',
    ]);
});
