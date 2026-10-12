<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Rules\MoneyBounds;

class Money extends BaseField
{
    private const ZERO_DECIMAL_CURRENCIES = [
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'ISK',
        'JPY',
        'KMF',
        'KRW',
        'PYG',
        'RWF',
        'UGX',
        'UYI',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ];

    private const THREE_DECIMAL_CURRENCIES = [
        'BHD',
        'IQD',
        'JOD',
        'KWD',
        'LYD',
        'OMR',
        'TND',
    ];

    protected string $type = 'money';

    protected ?string $currency = null;

    protected ?string $locale = null;

    protected ?int $decimals = null;

    protected ?int $min = null;

    protected ?int $max = null;

    public function currency(string $currency): static
    {
        $this->currency = strtoupper($currency);

        return $this;
    }

    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function decimals(int $decimals): static
    {
        $this->decimals = max(0, min(4, $decimals));

        return $this;
    }

    public function minValue(int|float $amount): static
    {
        $this->min = $this->toMinorUnits($amount);

        return $this;
    }

    public function maxValue(int|float $amount): static
    {
        $this->max = $this->toMinorUnits($amount);

        return $this;
    }

    public function unsigned(): static
    {
        return $this->minValue(0);
    }

    public function getCurrency(): string
    {
        if (! is_null($this->currency)) {
            return $this->currency;
        }

        $currency = config('form-builder.money.currency');

        return is_string($currency) && $currency !== '' ? strtoupper($currency) : 'EUR';
    }

    public function getDecimals(): int
    {
        if (! is_null($this->decimals)) {
            return $this->decimals;
        }

        if (in_array($this->getCurrency(), self::ZERO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }

        if (in_array($this->getCurrency(), self::THREE_DECIMAL_CURRENCIES, true)) {
            return 3;
        }

        return 2;
    }

    public function toMinorUnits(int|float|string $amount): int
    {
        return (int) round((float) $amount * (10 ** $this->getDecimals()));
    }

    public function toMajorUnits(?int $minorUnits): ?float
    {
        if (is_null($minorUnits)) {
            return null;
        }

        return $minorUnits / (10 ** $this->getDecimals());
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        $presence = in_array('required', $rules, true) ? [] : ['nullable'];

        $rules = array_values(array_unique([
            ...$presence,
            ...$rules,
            'integer',
        ], SORT_REGULAR));

        $bounds = $this->boundsRule();

        return is_null($bounds) ? $rules : [...$rules, $bounds];
    }

    public function boundsRule(): ?MoneyBounds
    {
        if (is_null($this->min) && is_null($this->max)) {
            return null;
        }

        return new MoneyBounds(
            $this->min,
            $this->max,
            $this->getCurrency(),
            $this->getDecimals(),
            $this->locale,
        );
    }

    public function normalizeValue(mixed $value): mixed
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        return $value;
    }

    public function toSchema(): array
    {
        return array_merge(parent::toSchema(), array_filter([
            'currency' => $this->getCurrency(),
            'decimals' => $this->getDecimals(),
            'locale' => $this->locale,
            'min' => $this->min,
            'max' => $this->max,
        ], fn (mixed $value): bool => ! is_null($value)));
    }
}
