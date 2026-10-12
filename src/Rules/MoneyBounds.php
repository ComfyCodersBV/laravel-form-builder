<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use JsonSerializable;
use NumberFormatter;

class MoneyBounds implements JsonSerializable, ValidationRule
{
    private const SYMBOLS = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'JPY' => '¥',
    ];

    public function __construct(
        protected ?int $min,
        protected ?int $max,
        protected string $currency = 'EUR',
        protected int $decimals = 2,
        protected ?string $locale = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) {
            return;
        }

        $amount = (int) $value;

        if (! is_null($this->min) && $amount < $this->min) {
            $fail('form-builder::validation.money-min')->translate([
                'amount' => $this->format($this->min),
            ]);

            return;
        }

        if (! is_null($this->max) && $amount > $this->max) {
            $fail('form-builder::validation.money-max')->translate([
                'amount' => $this->format($this->max),
            ]);
        }
    }

    public function format(int $minorUnits): string
    {
        $amount = $minorUnits / (10 ** $this->decimals);
        $locale = $this->locale ?? app()->getLocale();

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $this->decimals);
            $formatted = $formatter->formatCurrency($amount, $this->currency);

            if (is_string($formatted)) {
                return $formatted;
            }
        }

        return $this->fallbackFormat($amount, $locale);
    }

    public function jsonSerialize(): string
    {
        return 'money_bounds:'.($this->min ?? '').','.($this->max ?? '');
    }

    private function fallbackFormat(float $amount, string $locale): string
    {
        $symbol = self::SYMBOLS[$this->currency] ?? $this->currency;
        $commaDecimals = trans('form-builder::money.decimal-separator', [], $locale) === ',';

        $number = number_format(
            abs($amount),
            $this->decimals,
            $commaDecimals ? ',' : '.',
            $commaDecimals ? '.' : ',',
        );

        $sign = $amount < 0 ? '-' : '';

        if ($commaDecimals) {
            return "{$symbol} {$sign}{$number}";
        }

        return "{$sign}{$symbol}{$number}";
    }
}
