<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use TranquilTools\FormBuilder\Fields\Time;

function timeIsValid(Time $field, mixed $value): bool
{
    return Validator::make(
        ['at' => $value],
        ['at' => $field->getRules()],
    )->passes();
}

it('renders the time type with a quarter-hour step by default', function () {
    $schema = Time::make('starts_at')->toSchema();

    expect($schema['type'])->toBe('time')
        ->and($schema['step'])->toBe(15)
        ->and($schema['clearable'])->toBeTrue()
        ->and($schema['clearLabel'])->toBe('Clear field');
});

it('stores the step, min and max options', function () {
    $schema = Time::make('starts_at')->step(30)->min('08:00')->max('18:00')->toSchema();

    expect($schema['step'])->toBe(30)
        ->and($schema['min'])->toBe('08:00')
        ->and($schema['max'])->toBe('18:00')
        ->and($schema['rules'])->toBe([
            'nullable',
            'date_format:H:i',
            'after_or_equal:08:00',
            'before_or_equal:18:00',
        ]);
});

it('keeps the step between one minute and half a day', function () {
    expect(Time::make('at')->step(0)->toSchema()['step'])->toBe(1)
        ->and(Time::make('at')->step(1000)->toSchema()['step'])->toBe(720);
});

it('is nullable and clearable unless required', function () {
    $schema = Time::make('at')->required()->toSchema();

    expect($schema['rules'])->toBe([
        'date_format:H:i',
        'required',
    ])
        ->and($schema['clearable'])->toBeFalse();
});

it('lets the clearable default be overridden', function () {
    expect(Time::make('at')->clearable(false)->toSchema()['clearable'])->toBeFalse();
});

it('validates the HH:MM format', function (mixed $value, bool $passes) {
    expect(timeIsValid(Time::make('at'), $value))->toBe($passes);
})->with([
    'a quarter past nine' => ['09:15', true],
    'last minute of the day' => ['23:59', true],
    'empty' => [null, true],
    'without leading zero' => ['9:15', false],
    'hour out of range' => ['24:00', false],
    'minute out of range' => ['12:60', false],
    'with seconds' => ['12:00:00', false],
    'text' => ['abc', false],
]);

it('rejects an empty value when required', function () {
    expect(timeIsValid(Time::make('at')->required(), null))->toBeFalse();
});

it('validates against min and max', function () {
    $field = Time::make('at')->min('08:00')->max('18:00');

    expect(timeIsValid($field, '08:00'))->toBeTrue()
        ->and(timeIsValid($field, '18:00'))->toBeTrue()
        ->and(timeIsValid($field, '07:45'))->toBeFalse()
        ->and(timeIsValid($field, '18:15'))->toBeFalse();
});
