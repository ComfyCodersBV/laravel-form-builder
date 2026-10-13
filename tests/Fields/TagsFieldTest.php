<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Tags;
use TranquilTools\FormBuilder\FormConfig;

it('describes the tags field', function () {
    $schema = Tags::make('to')
        ->emails()
        ->suggestions('https://app.test/addresses')
        ->max(5)
        ->toSchema();

    expect($schema['type'])->toBe('tags')
        ->and($schema['emails'])->toBeTrue()
        ->and($schema['suggestionsUrl'])->toBe('https://app.test/addresses')
        ->and($schema['max'])->toBe(5);
});

it('leaves out what is not set', function () {
    expect(Tags::make('labels')->toSchema())
        ->not->toHaveKey('emails')
        ->not->toHaveKey('suggestionsUrl')
        ->not->toHaveKey('max');
});

it('splits a pasted string on commas, semicolons and newlines', function () {
    expect(Tags::make('to')->normalizeValue("jane@example.com, john@example.com;\n mary@example.com\r\n;;"))
        ->toBe([
            'jane@example.com',
            'john@example.com',
            'mary@example.com',
        ]);
});

it('trims entries and drops blanks and duplicates from an array', function () {
    expect(Tags::make('labels')->normalizeValue([' urgent ', '', 'urgent', 'later', null]))
        ->toBe([
            'urgent',
            'later',
        ])
        ->and(Tags::make('labels')->normalizeValue(null))->toBe([])
        ->and(Tags::make('labels')->normalizeValue(''))->toBe([]);
});

it('is a nullable array of strings unless required', function () {
    expect(Tags::make('labels')->getRules())->toBe(['nullable', 'array'])
        ->and(Tags::make('labels')->required()->getRules())->toBe(['required', 'array'])
        ->and(Tags::make('labels')->max(3)->getRules())->toBe(['nullable', 'array', 'max:3'])
        ->and(Tags::make('labels')->nestedRules())->toBe(['labels.*' => ['string']]);
});

it('validates every entry as an e-mail address in email mode', function () {
    expect(Tags::make('to')->emails()->nestedRules())->toBe(['to.*' => ['string', 'email:rfc']]);

    $form = FormConfig::make([Tags::make('to')->emails()]);

    expect($form->validate(Request::create('/', 'POST', ['to' => ['jane@example.com', 'john@example.com']])))
        ->toBe(['to' => ['jane@example.com', 'john@example.com']]);

    try {
        $form->validate(Request::create('/', 'POST', ['to' => ['jane@example.com', 'not an address']]));

        $this->fail('The invalid address passed validation.');
    } catch (ValidationException $e) {
        expect(array_keys($e->errors()))->toBe(['to.1']);
    }
});

it('names every entry after the label in validation messages', function () {
    expect(Tags::make('to')->label('To')->validationAttributes())->toBe(['to.*' => 'To'])
        ->and(Tags::make('to')->validationAttributes())->toBe([]);
});

it('accepts a comma separated string through the form', function () {
    $validated = FormConfig::make([Tags::make('to')->emails()->required()])
        ->validate(Request::create('/', 'POST', ['to' => 'jane@example.com; john@example.com']));

    expect($validated)->toBe(['to' => ['jane@example.com', 'john@example.com']]);
});

it('splits the string before validating a field inside a section', function () {
    $form = FormConfig::make([
        Section::make()->fields([
            Tags::make('cc')->emails(),
        ]),
    ]);

    expect(fn () => $form->validate(Request::create('/', 'POST', ['cc' => 'jane@example.com, nope'])))
        ->toThrow(ValidationException::class);
});

it('limits the number of entries', function () {
    $form = FormConfig::make([Tags::make('labels')->max(2)]);

    expect(fn () => $form->validate(Request::create('/', 'POST', ['labels' => ['a', 'b', 'c']])))
        ->toThrow(ValidationException::class)
        ->and($form->validate(Request::create('/', 'POST', ['labels' => ['a', 'b']])))->toBe(['labels' => ['a', 'b']]);
});

it('accepts any text without email mode', function () {
    $validated = FormConfig::make([Tags::make('labels')])
        ->validate(Request::create('/', 'POST', ['labels' => ['not an address', 'urgent']]));

    expect($validated)->toBe(['labels' => ['not an address', 'urgent']]);
});
