<?php

declare(strict_types=1);

use Illuminate\Http\Request;
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

    expect($field->getRules())->toBe(['nullable', 'integer', 'min:50', 'max:100000'])
        ->and($field->toSchema()['min'])->toBe(50)
        ->and($field->toSchema()['max'])->toBe(100000)
        ->and(Money::make('a')->unsigned()->getRules())->toContain('min:0');
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
