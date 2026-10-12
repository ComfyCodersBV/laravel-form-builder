<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use JsonSerializable;
use Throwable;

class DateBounds implements JsonSerializable, ValidationRule
{
    protected ?string $min;

    protected ?string $max;

    public function __construct(
        ?string $min = null,
        ?string $max = null,
        protected bool $withTime = false,
        protected ?string $timezone = null,
    ) {
        $this->min = self::readableBound($min);
        $this->max = self::readableBound($max);
    }

    public function getMin(): ?string
    {
        return $this->min;
    }

    public function getMax(): ?string
    {
        return $this->max;
    }

    public function isEmpty(): bool
    {
        return is_null($this->min) && is_null($this->max);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = is_array($value) ? array_values($value) : [$value];

        foreach ($values as $item) {
            if (! is_null($this->min) && ($this->compare($item, $this->min) ?? 0) < 0) {
                $fail('form-builder::validation.date-min')->translate([
                    'date' => $this->min,
                ]);

                return;
            }

            if (! is_null($this->max) && ($this->compare($item, $this->max) ?? 0) > 0) {
                $fail('form-builder::validation.date-max')->translate([
                    'date' => $this->max,
                ]);

                return;
            }
        }
    }

    public function jsonSerialize(): string
    {
        return 'date_bounds';
    }

    private function compare(mixed $value, string $bound): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if ($this->withTime && self::hasTime($bound)) {
            return $this->compareMoments($value, $bound);
        }

        $date = self::calendarDateOf($value);

        if (is_null($date)) {
            return null;
        }

        return $date <=> self::calendarDateOf($bound);
    }

    private function compareMoments(string $value, string $bound): ?int
    {
        $timezone = $this->resolveTimezone();

        try {
            return Carbon::parse($value, $timezone)->getTimestamp() <=> Carbon::parse($bound, $timezone)->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveTimezone(): string
    {
        $timezone = $this->timezone ?? config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }

    private static function readableBound(?string $bound): ?string
    {
        if (is_null($bound) || is_null(self::calendarDateOf($bound))) {
            return null;
        }

        return $bound;
    }

    private static function hasTime(string $bound): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $bound) === 1;
    }

    private static function calendarDateOf(string $value): ?string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        return $matches[0];
    }
}
