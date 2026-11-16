<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('validates the characters a charset allows', function () {
    $form = FormConfig::make([
        Text::make('number')->charset('0-9'),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['number' => '0042'])))->toBe(['number' => '0042'])
        ->and($form->validate(Request::create('/', 'POST', ['number' => null])))->toBe(['number' => null])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['number' => '42a'])))
        ->toThrow(ValidationException::class)
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['number' => ['4']])))
        ->toThrow(ValidationException::class);
});

it('keeps one charset rule when the charset is set twice', function () {
    $rules = Text::make('code')->charset('0-9')->charset('A-Z')->getRules();

    expect($rules)->toHaveCount(1);
});

it('accepts a slash in the charset', function () {
    $form = FormConfig::make([
        Text::make('caliber')->charset('0-9/'),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['caliber' => '22/24'])))->toBe(['caliber' => '22/24']);
});

it('refuses a charset that is no character class', function () {
    Text::make('code')->charset('z-a');
})->throws(InvalidArgumentException::class);

it('upper-cases the value before it validates', function () {
    $form = FormConfig::make([
        Text::make('container')->uppercase()->charset('A-Z0-9'),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['container' => 'abcu1234567'])))
        ->toBe(['container' => 'ABCU1234567']);
});

it('upper-cases a text field inside a repeater row', function () {
    $form = FormConfig::make([
        Repeater::make('rows')->fields([
            Text::make('code')->uppercase(),
        ]),
    ]);

    expect($form->validate(Request::create('/', 'POST', ['rows' => [['code' => 'ab']]]))['rows'][0]['code'])->toBe('AB');
});
