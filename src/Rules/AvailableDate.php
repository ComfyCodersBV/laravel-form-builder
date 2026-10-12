<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use JsonSerializable;

class AvailableDate implements JsonSerializable, ValidationRule
{
    /** @var list<int> */
    protected array $weekdays = [];

    /** @var list<string|array{from?: string, to?: string}> */
    protected array $dates = [];

    /**
     * @param  array<int, int>  $weekdays
     * @param  array<int, string|DateTimeInterface|array<string, string|DateTimeInterface|null>>  $dates
     */
    public function __construct(array $weekdays = [], array $dates = [])
    {
        $this->weekdays = self::normalizeWeekdays($weekdays);
        $this->dates = self::normalizeDates($dates);
    }

    /**
     * @param  array<int, int>  $weekdays
     * @return list<int>
     */
    public static function normalizeWeekdays(array $weekdays): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(intval(...), $weekdays),
            fn (int $weekday): bool => $weekday >= 0 && $weekday <= 6,
        )));

        sort($normalized);

        return $normalized;
    }

    /**
     * @param  array<int, string|DateTimeInterface|array<string, string|DateTimeInterface|null>>  $dates
     * @return list<string|array{from?: string, to?: string}>
     */
    public static function normalizeDates(array $dates): array
    {
        $normalized = [];

        foreach ($dates as $date) {
            if (! is_array($date)) {
                $normalized[] = self::toDateString($date);

                continue;
            }

            $range = array_filter([
                'from' => isset($date['from']) ? self::toDateString($date['from']) : null,
                'to' => isset($date['to']) ? self::toDateString($date['to']) : null,
            ]);

            if ($range === []) {
                throw new InvalidArgumentException('A disabled date range needs a [from] or a [to] date.');
            }

            $normalized[] = $range;
        }

        return $normalized;
    }

    /**
     * @return list<int>
     */
    public function getWeekdays(): array
    {
        return $this->weekdays;
    }

    /**
     * @return list<string|array{from?: string, to?: string}>
     */
    public function getDates(): array
    {
        return $this->dates;
    }

    public function isEmpty(): bool
    {
        return $this->weekdays === [] && $this->dates === [];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = is_array($value) ? array_values($value) : [$value];

        foreach ($values as $item) {
            $date = self::calendarDateOf($item);

            if (! is_null($date) && $this->isDisabled($date)) {
                $fail('form-builder::validation.date-unavailable')->translate();

                return;
            }
        }
    }

    public function isDisabled(string $date): bool
    {
        $timestamp = strtotime($date.' 12:00:00 UTC');

        if ($timestamp === false) {
            return false;
        }

        if (in_array((int) gmdate('w', $timestamp), $this->weekdays, true)) {
            return true;
        }

        foreach ($this->dates as $disabled) {
            if ($this->matches($date, $disabled)) {
                return true;
            }
        }

        return false;
    }

    public function jsonSerialize(): string
    {
        return 'available_date';
    }

    /**
     * @param  string|array{from?: string, to?: string}  $disabled
     */
    private function matches(string $date, string|array $disabled): bool
    {
        if (is_string($disabled)) {
            return $date === $disabled;
        }

        $afterStart = ! isset($disabled['from']) || $date >= $disabled['from'];
        $beforeEnd = ! isset($disabled['to']) || $date <= $disabled['to'];

        return $afterStart && $beforeEnd;
    }

    private static function calendarDateOf(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        return $matches[0];
    }

    private static function toDateString(mixed $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        $calendarDate = self::calendarDateOf($date);

        if (is_null($calendarDate)) {
            throw new InvalidArgumentException('A disabled date must be a Y-m-d string or a date object.');
        }

        return $calendarDate;
    }
}
