<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Money;
use TranquilTools\FormBuilder\FormConfig;

it('describes the currency, its decimals and the locale', function () {
    $schema = Money::make('amount_cents')->currency('usd')->locale('en-US')->toSchema();

    expect($schema['type'])->toBe('money')
        ->and($schema['currency'])->toBe('USD')
        ->and($schema['decimals'])->toBe(2)
        ->and($schema['locale'])->toBe('en-US');
});

it('falls back to the configured currency', function () {
    config()->set('form-builder.money.currency', 'gbp');

    expect(Money::make('amount_cents')->toSchema()['currency'])->toBe('GBP')
        ->and(Money::make('amount_cents')->toSchema())->not->toHaveKey('locale');
});

it('derives the decimals from the currency', function () {
    expect(Money::make('a')->currency('JPY')->getDecimals())->toBe(0)
        ->and(Money::make('a')->currency('KWD')->getDecimals())->toBe(3)
        ->and(Money::make('a')->currency('EUR')->decimals(4)->getDecimals())->toBe(4);
});

it('validates whole minor units and is nullable unless required', function () {
    expect(Money::make('amount_cents')->getRules())->toBe(['nullable', 'integer'])
        ->and(Money::make('amount_cents')->required()->getRules())->toBe(['required', 'integer']);
});

it('takes its bounds in major units and validates them in minor units', function () {
    $field = Money::make('amount_cents')->minValue(0.5)->maxValue(1000);

    $passes = fn (Money $money, mixed $value): bool => Validator::make(['a' => $value], ['a' => $money->getRules()])->passes();

    expect($field->toSchema()['min'])->toBe(50)
        ->and($field->toSchema()['max'])->toBe(100000)
        ->and($passes($field, 50))->toBeTrue()
        ->and($passes($field, '100000'))->toBeTrue()
        ->and($passes($field, 49))->toBeFalse()
        ->and($passes($field, 100001))->toBeFalse()
        ->and($passes(Money::make('a')->unsigned(), -1))->toBeFalse()
        ->and($passes(Money::make('a')->unsigned(), null))->toBeTrue();
});

it('names its bounds in major units with the currency', function (string $locale, int $value, string $expected) {
    app()->setLocale($locale);

    $field = Money::make('price_cents')->label('Price')->minValue(0.5)->maxValue(1000);

    try {
        FormConfig::make([$field])->validate(Request::create('/', 'POST', ['price_cents' => $value]));
    } catch (ValidationException $e) {
        expect(preg_replace('/\s/u', ' ', $e->errors()['price_cents'][0]))->toContain($expected)
            ->and($e->errors()['price_cents'][0])->toContain('€')
            ->and($e->errors()['price_cents'][0])->not->toContain('100000');

        return;
    }

    $this->fail('The amount should have been rejected.');
})->with([
    ['en', 100001, 'must not be greater than'],
    ['en', 100001, '1,000.00'],
    ['en', 49, '0.50'],
    ['nl', 100001, 'mag niet groter zijn dan'],
    ['nl', 100001, '1.000,00'],
    ['nl', 49, '0,50'],
]);

it('formats the bound in the currency of the field', function () {
    $rule = Money::make('a')->currency('JPY')->locale('en')->maxValue(5000)->boundsRule();

    expect($rule->format(5000))->toContain('5,000')
        ->and($rule->format(5000))->not->toContain('.00');
});

it('converts between major and minor units', function () {
    $field = Money::make('amount_cents');

    expect($field->toMinorUnits('12.34'))->toBe(1234)
        ->and($field->toMinorUnits(0.1 + 0.2))->toBe(30)
        ->and($field->toMajorUnits(1250))->toBe(12.5)
        ->and($field->toMajorUnits(null))->toBeNull();
});

it('hands back an integer or null after validation', function () {
    $form = FormConfig::make([Money::make('amount_cents'), Money::make('fee_cents')]);

    $validated = $form->validate(Request::create('/', 'POST', ['amount_cents' => '1250', 'fee_cents' => null]));

    expect($validated)->toBe(['amount_cents' => 1250, 'fee_cents' => null]);
});

it('rejects a decimal amount', function () {
    FormConfig::make([Money::make('amount_cents')])
        ->validate(Request::create('/', 'POST', ['amount_cents' => '12.50']));
})->throws(ValidationException::class);
