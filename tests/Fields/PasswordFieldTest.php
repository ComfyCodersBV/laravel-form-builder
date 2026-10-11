<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use TranquilTools\FormBuilder\Fields\Password;
use TranquilTools\FormBuilder\FormConfig;

it('says a value is stored', function () {
    expect(Password::make('api_key')->stored()->toSchema()['stored'])->toBeTrue()
        ->and(Password::make('api_key')->stored(false)->toSchema()['stored'])->toBeFalse()
        ->and(Password::make('api_key')->toSchema())->not->toHaveKey('stored');
});

it('sends and validates a remove flag', function () {
    $field = Password::make('api_key')->stored()->removable();

    expect($field->toSchema()['removeFlag'])->toBe('remove_api_key')
        ->and($field->nestedRules())->toBe(['remove_api_key' => ['nullable', 'boolean']])
        ->and(Password::make('api_key')->toSchema())->not->toHaveKey('removeFlag');
});

it('reads the remove flag from the request', function () {
    $field = Password::make('api_key')->removable(true, 'forget_key');

    expect($field->removalRequested(Request::create('/', 'POST', ['forget_key' => '1'])))->toBeTrue()
        ->and($field->removalRequested(Request::create('/', 'POST')))->toBeFalse();
});

it('validates the remove flag through the form', function () {
    $validated = FormConfig::make([Password::make('api_key')->rules('nullable')->removable()])
        ->validate(Request::create('/', 'POST', ['remove_api_key' => true]));

    expect($validated)->toBe(['remove_api_key' => true]);
});
