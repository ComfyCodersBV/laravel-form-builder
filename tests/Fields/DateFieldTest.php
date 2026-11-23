<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use TranquilTools\FormBuilder\Fields\Date;
use TranquilTools\FormBuilder\Fields\DateTime;
use TranquilTools\FormBuilder\FormConfig;

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

it('sends no week start and no week numbers by default', function () {
    $schema = Date::make('at')->toSchema();

    expect($schema)->not->toHaveKey('weekStartsOn')
        ->and($schema)->not->toHaveKey('weekNumbers');
});

it('shows week numbers on a field', function () {
    expect(Date::make('at')->weekNumbers()->toSchema()['weekNumbers'])->toBeTrue()
        ->and(Date::make('at')->weekNumbers()->weekNumbers(false)->toSchema())->not->toHaveKey('weekNumbers');
});

it('takes the week start and week numbers from the config', function () {
    config()->set('form-builder.dates.week_starts_on', 1);
    config()->set('form-builder.dates.week_numbers', true);

    $schema = Date::make('at')->toSchema();

    expect($schema['weekStartsOn'])->toBe(1)
        ->and($schema['weekNumbers'])->toBeTrue()
        ->and(DateTime::make('at')->range()->toSchema()['weekNumbers'])->toBeTrue();
});

it('lets the field override the configured week start and week numbers', function () {
    config()->set('form-builder.dates.week_starts_on', 1);
    config()->set('form-builder.dates.week_numbers', true);

    $schema = Date::make('at')->weekStartsOn(0)->weekNumbers(false)->toSchema();

    expect($schema['weekStartsOn'])->toBe(0)
        ->and($schema)->not->toHaveKey('weekNumbers');
});

it('clamps a configured week start to sunday or monday', function () {
    config()->set('form-builder.dates.week_starts_on', 3);

    expect(Date::make('at')->toSchema()['weekStartsOn'])->toBe(0);
});

it('sends the url to fetch marked dates from', function () {
    expect(Date::make('at')->markedDatesUrl('/leave/marked')->toSchema()['markedDatesUrl'])->toBe('/leave/marked')
        ->and(Date::make('at')->markedDatesUrl('/leave/marked')->markedDatesUrl(null)->toSchema())->not->toHaveKey('markedDatesUrl')
        ->and(Date::make('at')->toSchema())->not->toHaveKey('markedDates');
});

it('normalizes marked dates to calendar dates', function () {
    $schema = Date::make('at')
        ->markedDates([
            '2026-12-25 10:00',
            CarbonImmutable::parse('2026-12-31'),
            ['from' => '2026-07-20', 'to' => CarbonImmutable::parse('2026-08-02'), 'variant' => 'muted', 'label' => 'Requested'],
        ])
        ->markedDates([['from' => '2027-01-04', 'to' => null, 'label' => '']])
        ->toSchema();

    expect($schema['markedDates'])->toBe([
        ['from' => '2026-12-25'],
        ['from' => '2026-12-31'],
        ['from' => '2026-07-20', 'to' => '2026-08-02', 'variant' => 'muted', 'label' => 'Requested'],
        ['from' => '2027-01-04'],
    ]);
});

it('refuses a marked date that is not a date', function (mixed $date) {
    Date::make('at')->markedDates([$date]);
})->throws(InvalidArgumentException::class)->with([
    'text' => ['soon'],
    'range without from' => [['to' => '2026-08-02']],
]);

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

it('describes the disabled weekdays and dates', function () {
    $schema = Date::make('on')
        ->disabledWeekdays([6, 0, 0, 9])
        ->disabledDates([
            '2026-12-25',
            CarbonImmutable::parse('2026-12-31'),
            ['from' => '2026-07-20', 'to' => '2026-08-02'],
        ])
        ->toSchema();

    expect($schema['disabledWeekdays'])->toBe([0, 6])
        ->and($schema['disabledDates'])->toBe([
            '2026-12-25',
            '2026-12-31',
            ['from' => '2026-07-20', 'to' => '2026-08-02'],
        ])
        ->and(Date::make('on')->toSchema())->not->toHaveKeys(['disabledWeekdays', 'disabledDates'])
        ->and(Date::make('on')->getRules())->toBe([]);
});

it('rejects a disabled date on the server', function (mixed $value, bool $passes) {
    $field = Date::make('on')
        ->disabledWeekdays([0, 6])
        ->disabledDates([
            '2026-10-14',
            ['from' => '2026-12-21', 'to' => '2027-01-03'],
            ['from' => '2027-06-01'],
        ]);

    expect(Validator::make(['on' => $value], ['on' => $field->getRules()])->passes())->toBe($passes);
})->with([
    'a weekday' => ['2026-10-12', true],
    'a saturday' => ['2026-10-17', false],
    'a sunday' => ['2026-10-18', false],
    'a disabled date' => ['2026-10-14', false],
    'inside a range' => ['2026-12-30', false],
    'the last day of a range' => ['2027-01-04', true],
    'an open-ended range' => ['2027-06-02', false],
    'a date with a time' => ['2026-10-17 10:00', false],
    'an ISO value with an offset' => ['2026-10-13T23:30:00+02:00', true],
    'a range end on a disabled day' => [['start' => '2026-10-12', 'end' => '2026-10-17'], false],
    'a range on open days' => [['start' => '2026-10-12', 'end' => '2026-10-13'], true],
    'an empty value' => [null, true],
]);

it('says why a disabled date is rejected', function () {
    try {
        FormConfig::make([Date::make('arrival_on')->label('Arrival')->disabledWeekdays([0])])
            ->validate(Request::create('/', 'POST', ['arrival_on' => '2026-10-18']));
    } catch (ValidationException $e) {
        expect($e->errors()['arrival_on'][0])->toBe('The arrival on cannot be on that date.');

        return;
    }

    $this->fail('The sunday should have been rejected.');
});

it('refuses a disabled date it cannot read', function (mixed $date) {
    Date::make('on')->disabledDates([$date]);
})->with([
    'not a date' => ['next friday'],
    'an empty range' => [['until' => '2026-01-01']],
])->throws(InvalidArgumentException::class);

it('leaves min and max dates to the browser unless asked', function () {
    $field = Date::make('on')->minDate('2026-10-01')->maxDate('2026-10-31');

    expect($field->getRules())->toBe([])
        ->and($field->boundsRule())->toBeNull()
        ->and(Validator::make(['on' => '2026-11-05'], ['on' => $field->getRules()])->passes())->toBeTrue();
});

it('validates min and max dates on the server', function (mixed $value, bool $passes) {
    $field = Date::make('on')->minDate('2026-10-01')->maxDate('2026-10-31')->validateBounds();

    expect(Validator::make(['on' => $value], ['on' => $field->getRules()])->passes())->toBe($passes);
})->with([
    'the first day' => ['2026-10-01', true],
    'the last day' => ['2026-10-31', true],
    'the day before' => ['2026-09-30', false],
    'the day after' => ['2026-11-01', false],
    'a late time on the last day' => ['2026-10-31 23:59', true],
    'the calendar date of an offset value' => ['2026-10-31T23:30:00-05:00', true],
    'a range inside' => [['start' => '2026-10-02', 'end' => '2026-10-30'], true],
    'a range ending too late' => [['start' => '2026-10-02', 'end' => '2026-11-02'], false],
    'a half range' => [['start' => '2026-09-01', 'end' => null], false],
    'an empty value' => [null, true],
]);

it('validates the bounds of every date field when configured', function () {
    config()->set('form-builder.dates.validate_bounds', true);

    expect(Date::make('on')->minDate('2026-10-01')->boundsRule())->not->toBeNull()
        ->and(Date::make('on')->minDate('2026-10-01')->validateBounds(false)->boundsRule())->toBeNull()
        ->and(Date::make('on')->boundsRule())->toBeNull()
        ->and(Date::make('on')->minDate('today')->boundsRule())->toBeNull();
});

it('compares the moment when a field with a time has a bound with a time', function (string $value, bool $passes) {
    config()->set('app.timezone', 'UTC');

    $field = DateTime::make('starts_at')
        ->timezone('Europe/Amsterdam')
        ->minDate('2026-10-12 09:00')
        ->validateBounds();

    expect(Validator::make(['starts_at' => $value], ['starts_at' => $field->getRules()])->passes())->toBe($passes);
})->with([
    'the bound itself' => ['2026-10-12 09:00', true],
    'a minute early' => ['2026-10-12 08:59', false],
    'eleven o\'clock in Amsterdam' => ['2026-10-12T11:00:00+02:00', true],
    'ten to eleven in Amsterdam' => ['2026-10-12T10:50:00+02:00', false],
]);

it('compares calendar dates when a field with a time has a bound without one', function () {
    $field = DateTime::make('starts_at')->minDate('2026-10-12')->validateBounds();

    expect(Validator::make(['starts_at' => '2026-10-12T00:15:00+02:00'], ['starts_at' => $field->getRules()])->passes())->toBeTrue()
        ->and(Validator::make(['starts_at' => '2026-10-11T23:45:00+02:00'], ['starts_at' => $field->getRules()])->passes())->toBeFalse();
});

it('says which bound a date crossed', function (string $locale, string $value, string $message) {
    app()->setLocale($locale);

    try {
        FormConfig::make([Date::make('arrival_on')->minDate('2026-10-01')->maxDate('2026-10-31')->validateBounds()])
            ->validate(Request::create('/', 'POST', ['arrival_on' => $value]));
    } catch (ValidationException $e) {
        expect($e->errors()['arrival_on'][0])->toBe($message);

        return;
    }

    $this->fail('The date should have been rejected.');
})->with([
    ['en', '2026-09-30', 'The arrival on must be on or after 2026-10-01.'],
    ['en', '2026-11-01', 'The arrival on must be on or before 2026-10-31.'],
    ['nl', '2026-09-30', 'Arrival on moet op of na 2026-10-01 liggen.'],
]);

it('sends the touch picker options only when set', function () {
    $schema = DateTime::make('starts_at')
        ->touchPicker()
        ->minuteStep(15)
        ->hours(7, 23)
        ->toSchema();

    expect($schema['touch'])->toBeTrue()
        ->and($schema['minuteStep'])->toBe(15)
        ->and($schema['hourFrom'])->toBe(7)
        ->and($schema['hourTo'])->toBe(23)
        ->and(DateTime::make('starts_at')->toSchema())->not->toHaveKeys(['touch', 'minuteStep', 'hourFrom', 'hourTo'])
        ->and(DateTime::make('starts_at')->touchPicker()->touchPicker(false)->toSchema())->not->toHaveKey('touch');
});

it('keeps the touch picker hours and minute step within a day', function () {
    $schema = DateTime::make('starts_at')
        ->minuteStep(90)
        ->hours(20, 30)
        ->toSchema();

    expect($schema['minuteStep'])->toBe(60)
        ->and($schema['hourFrom'])->toBe(20)
        ->and($schema['hourTo'])->toBe(23);
});

it('makes a touch picker clearable unless the field is required', function () {
    expect(DateTime::make('expires_at')->touchPicker()->rules(['nullable'])->toSchema()['clearable'])->toBeTrue()
        ->and(DateTime::make('starts_at')->touchPicker()->rules(['required'])->toSchema()['clearable'])->toBeFalse()
        ->and(DateTime::make('starts_at')->touchPicker()->rules(['required'])->clearable()->toSchema()['clearable'])->toBeTrue()
        ->and(DateTime::make('starts_at')->toSchema())->not->toHaveKey('clearable');
});

it('shows dates in the configured display format when the field sets none', function () {
    config(['form-builder.dates.display_format' => 'DD-MM-YYYY']);

    expect(Date::make('at')->toSchema()['format'])->toBe('DD-MM-YYYY')
        ->and(DateTime::make('at')->toSchema()['format'])->toBe('DD-MM-YYYY')
        ->and(Date::make('period')->range()->toSchema()['format'])->toBe('DD-MM-YYYY');
});

it('lets the field format win over the configured display format', function () {
    config(['form-builder.dates.display_format' => 'DD-MM-YYYY']);

    expect(Date::make('at')->format('YYYY/MM/DD')->toSchema()['format'])->toBe('YYYY/MM/DD');
});

it('sends no format without a configured display format', function () {
    config(['form-builder.dates.display_format' => null]);

    expect(Date::make('at')->toSchema())->not->toHaveKey('format');
});

it('takes the marked dates url from the configured route', function () {
    Route::get('calendar/marked', fn () => [])->name('calendar.marked');
    Route::getRoutes()->refreshNameLookups();
    config()->set('form-builder.dates.marked_dates_route', 'calendar.marked');

    expect(Date::make('at')->toSchema()['markedDatesUrl'])->toBe(url('calendar/marked'))
        ->and(DateTime::make('at')->toSchema()['markedDatesUrl'])->toBe(url('calendar/marked'));
});

it('lets the field override or turn off the configured marked dates route', function () {
    Route::get('calendar/marked', fn () => [])->name('calendar.marked');
    Route::getRoutes()->refreshNameLookups();
    config()->set('form-builder.dates.marked_dates_route', 'calendar.marked');

    expect(Date::make('at')->markedDatesUrl('/other')->toSchema()['markedDatesUrl'])->toBe('/other')
        ->and(Date::make('at')->markedDatesUrl(null)->toSchema())->not->toHaveKey('markedDatesUrl');
});

it('throws on a configured marked dates route that does not exist', function () {
    config()->set('form-builder.dates.marked_dates_route', 'missing.route');

    Date::make('at')->toSchema();
})->throws(InvalidArgumentException::class);
