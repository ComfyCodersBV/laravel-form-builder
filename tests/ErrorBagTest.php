<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

function errorBagForm(?string $bag = null): FormConfig
{
    return FormConfig::make([
        Text::make('email')->rules(['required', 'email']),
    ])->errorBag($bag);
}

it('sends the error bag only when it is set', function () {
    expect(errorBagForm()->jsonSerialize())->not->toHaveKey('errorBag')
        ->and(errorBagForm('newsletter')->jsonSerialize()['errorBag'])->toBe('newsletter')
        ->and(errorBagForm('')->jsonSerialize())->not->toHaveKey('errorBag')
        ->and(errorBagForm('newsletter')->errorBag(null)->getErrorBag())->toBeNull();
});

it('puts validation errors in the error bag of the form', function () {
    try {
        errorBagForm('newsletter')->validate(Request::create('/', 'POST', []));
    } catch (ValidationException $e) {
        expect($e->errorBag)->toBe('newsletter')
            ->and($e->errors())->toHaveKey('email');

        return;
    }

    test()->fail('The form did not fail validation.');
});

it('keeps validation errors in the default bag without an error bag', function () {
    try {
        errorBagForm()->validate(Request::create('/', 'POST', []));
    } catch (ValidationException $e) {
        expect($e->errorBag)->toBe('default');

        return;
    }

    test()->fail('The form did not fail validation.');
});

it('flashes the errors of two forms with the same field to their own bag', function () {
    Route::middleware('web')->post('/newsletter', fn (Request $request) => errorBagForm('newsletter')->validate($request));
    Route::middleware('web')->post('/contact', fn (Request $request) => errorBagForm()->validate($request));

    $this->post('/newsletter', ['email' => 'nope'])
        ->assertSessionHasErrorsIn('newsletter', ['email'])
        ->assertSessionDoesntHaveErrors(['email']);

    $this->post('/contact', ['email' => 'nope'])
        ->assertSessionHasErrors(['email']);
});

it('returns the validated values with an error bag', function () {
    expect(errorBagForm('newsletter')->validate(Request::create('/', 'POST', ['email' => 'jane@example.com'])))
        ->toBe(['email' => 'jane@example.com']);
});
