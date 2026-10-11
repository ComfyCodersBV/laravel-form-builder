<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Date;
use TranquilTools\FormBuilder\Fields\DateTime;

it('renders the date type', function () {
    expect(Date::make('published_at')->toSchema()['type'])->toBe('date');
});

it('toggles range mode', function () {
    expect(Date::make('period')->range()->toSchema()['range'])->toBeTrue()
        ->and(Date::make('period')->range()->range(false)->toSchema())->not->toHaveKey('range');
});

it('enables time selection', function () {
    expect(Date::make('at')->enableTime()->toSchema()['enableTime'])->toBeTrue();
});

it('stores min and max dates', function () {
    $schema = Date::make('at')->minDate('2026-01-01')->maxDate('2026-12-31')->toSchema();

    expect($schema['minDate'])->toBe('2026-01-01')
        ->and($schema['maxDate'])->toBe('2026-12-31');
});

it('clamps the week start to sunday or monday', function () {
    expect(Date::make('at')->weekStartsOn(1)->toSchema()['weekStartsOn'])->toBe(1)
        ->and(Date::make('at')->weekStartsOn(5)->toSchema()['weekStartsOn'])->toBe(0);
});

it('stores format and locale', function () {
    $schema = Date::make('at')->format('d-m-Y')->locale('nl')->toSchema();

    expect($schema['format'])->toBe('d-m-Y')
        ->and($schema['locale'])->toBe('nl');
});

it('DateTime enables time and uses the date type', function () {
    $schema = DateTime::make('at')->toSchema();

    expect($schema['type'])->toBe('date')
        ->and($schema['enableTime'])->toBeTrue();
});

it('sends no timezone unless one is configured', function () {
    expect(DateTime::make('at')->toSchema())->not->toHaveKey('timezone')
        ->and(DateTime::make('at')->toSchema())->not->toHaveKey('serverTimezone');
});

it('declares the timezone of a date field with a time and the application timezone', function () {
    config()->set('app.timezone', 'UTC');

    $schema = DateTime::make('starts_at')->timezone('Europe/Amsterdam')->toSchema();

    expect($schema['timezone'])->toBe('Europe/Amsterdam')
        ->and($schema['serverTimezone'])->toBe('UTC');
});

it('falls back to the configured timezone', function () {
    config()->set('form-builder.dates.timezone', Date::BROWSER_TIMEZONE);

    expect(DateTime::make('starts_at')->toSchema()['timezone'])->toBe('browser')
        ->and(DateTime::make('starts_at')->timezone('Europe/London')->toSchema()['timezone'])->toBe('Europe/London');
});

it('ignores the timezone on a date without a time', function () {
    $field = Date::make('birthday')->timezone('Europe/Amsterdam');

    expect($field->toSchema())->not->toHaveKey('timezone')
        ->and($field->normalizeValue('2026-10-11'))->toBe('2026-10-11');
});

it('converts a value with an offset to the application timezone', function () {
    config()->set('app.timezone', 'UTC');

    $field = DateTime::make('starts_at')->timezone('Europe/Amsterdam');

    expect($field->normalizeValue('2026-10-11T14:30:00+02:00'))->toBe('2026-10-11 12:30:00')
        ->and($field->normalizeValue('2026-10-11 14:30'))->toBe('2026-10-11 14:30:00')
        ->and($field->normalizeValue(['start' => '2026-01-05T09:00:00+01:00', 'end' => null]))
        ->toBe(['start' => '2026-01-05 08:00:00', 'end' => null])
        ->and($field->normalizeValue('not a date'))->toBe('not a date');
});

it('leaves values untouched without a timezone', function () {
    expect(DateTime::make('starts_at')->normalizeValue('2026-10-11T14:30:00+02:00'))->toBe('2026-10-11T14:30:00+02:00');
});
