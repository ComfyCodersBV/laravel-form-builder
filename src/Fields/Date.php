<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Throwable;
use TranquilTools\FormBuilder\Rules\AvailableDate;
use TranquilTools\FormBuilder\Rules\DateBounds;

class Date extends BaseField
{
    public const BROWSER_TIMEZONE = 'browser';

    protected string $type = 'date';

    protected ?string $timezone = null;

    /** @var list<int> */
    protected array $disabledWeekdays = [];

    /** @var list<string|array{from?: string, to?: string}> */
    protected array $disabledDates = [];

    protected ?bool $validateBounds = null;

    protected ?bool $weekNumbers = null;

    protected bool $markedDatesUrlSet = false;

    /** @var list<array{from: string, to?: string, variant?: string, label?: string}> */
    protected array $markedDates = [];

    public function range(bool $range = true): static
    {
        if ($range) {
            $this->attributes['range'] = true;
        } else {
            unset($this->attributes['range']);
        }

        return $this;
    }

    public function enableTime(bool $enable = true): static
    {
        if ($enable) {
            $this->attributes['enableTime'] = true;
        }

        return $this;
    }

    public function touchPicker(bool $touch = true): static
    {
        if ($touch) {
            $this->attributes['touch'] = true;
        } else {
            unset($this->attributes['touch']);
        }

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->attributes['clearable'] = $clearable;

        return $this;
    }

    public function minuteStep(int $minutes): static
    {
        $this->attributes['minuteStep'] = max(1, min($minutes, 60));

        return $this;
    }

    public function hours(int $from, int $to): static
    {
        $from = max(0, min($from, 23));

        $this->attributes['hourFrom'] = $from;
        $this->attributes['hourTo'] = max($from, min($to, 23));

        return $this;
    }

    public function minDate(string $date): static
    {
        $this->attributes['minDate'] = $date;

        return $this;
    }

    public function maxDate(string $date): static
    {
        $this->attributes['maxDate'] = $date;

        return $this;
    }

    /**
     * @param  array<int, int>  $weekdays
     */
    public function disabledWeekdays(array $weekdays): static
    {
        $this->disabledWeekdays = AvailableDate::normalizeWeekdays($weekdays);

        return $this;
    }

    /**
     * @param  array<int, string|DateTimeInterface|array<string, string|DateTimeInterface|null>>  $dates
     */
    public function disabledDates(array $dates): static
    {
        $this->disabledDates = [
            ...$this->disabledDates,
            ...AvailableDate::normalizeDates($dates),
        ];

        return $this;
    }

    /**
     * @param  array<int, string|DateTimeInterface|array{from: string|DateTimeInterface, to?: string|DateTimeInterface|null, variant?: string|null, label?: string|null}>  $dates
     */
    public function markedDates(array $dates): static
    {
        $this->markedDates = [
            ...$this->markedDates,
            ...array_map($this->normalizeMarkedDate(...), array_values($dates)),
        ];

        return $this;
    }

    public function markedDatesUrl(?string $url): static
    {
        $this->markedDatesUrlSet = true;

        if (is_null($url) || $url === '') {
            unset($this->attributes['markedDatesUrl']);

            return $this;
        }

        $this->attributes['markedDatesUrl'] = $url;

        return $this;
    }

    public function availabilityRule(): ?AvailableDate
    {
        $rule = new AvailableDate($this->disabledWeekdays, $this->disabledDates);

        return $rule->isEmpty() ? null : $rule;
    }

    public function validateBounds(bool $validate = true): static
    {
        $this->validateBounds = $validate;

        return $this;
    }

    public function boundsRule(): ?DateBounds
    {
        if (! ($this->validateBounds ?? (bool) config('form-builder.dates.validate_bounds', false))) {
            return null;
        }

        $rule = new DateBounds(
            $this->attributes['minDate'] ?? null,
            $this->attributes['maxDate'] ?? null,
            (bool) ($this->attributes['enableTime'] ?? false),
        );

        return $rule->isEmpty() ? null : $rule;
    }

    public function getRules(): array
    {
        $extra = array_values(array_filter([
            $this->availabilityRule(),
            $this->boundsRule(),
        ]));

        if ($extra === []) {
            return parent::getRules();
        }

        return [
            ...parent::getRules(),
            ...$extra,
        ];
    }

    /**
     * 0 = Sunday (default), 1 = Monday
     */
    public function weekStartsOn(int $dayOfWeek): static
    {
        $this->attributes['weekStartsOn'] = $dayOfWeek === 1 ? 1 : 0;

        return $this;
    }

    public function weekNumbers(bool $show = true): static
    {
        $this->weekNumbers = $show;

        return $this;
    }

    public function format(string $format): static
    {
        $this->attributes['format'] = $format;

        return $this;
    }

    public function locale(string $locale): static
    {
        $this->attributes['locale'] = $locale;

        return $this;
    }

    public function timezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): ?string
    {
        if (! ($this->attributes['enableTime'] ?? false)) {
            return null;
        }

        $timezone = $this->timezone ?? config('form-builder.dates.timezone');

        if (! is_string($timezone) || $timezone === '') {
            return null;
        }

        return $timezone;
    }

    public function normalizeValue(mixed $value): mixed
    {
        if (is_null($this->getTimezone())) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->toApplicationTimezone($item), $value);
        }

        return $this->toApplicationTimezone($value);
    }

    public function toSchema(): array
    {
        if ($this->attributes['touch'] ?? false) {
            $this->attributes['clearable'] ??= ! in_array('required', $this->getRules(), true);
        }

        $schema = array_merge(parent::toSchema(), array_filter([
            'format' => $this->attributes['format'] ?? $this->defaultDisplayFormat(),
            'disabledWeekdays' => $this->disabledWeekdays,
            'disabledDates' => $this->disabledDates,
            'markedDates' => $this->markedDates,
            'weekNumbers' => $this->weekNumbers ?? (bool) config('form-builder.dates.week_numbers', false),
        ]));

        $markedDatesUrl = $this->defaultMarkedDatesUrl();

        if (! is_null($markedDatesUrl)) {
            $schema['markedDatesUrl'] = $markedDatesUrl;
        }

        $weekStart = $this->attributes['weekStartsOn'] ?? $this->defaultWeekStart();

        if (! is_null($weekStart)) {
            $schema['weekStartsOn'] = $weekStart;
        }

        $timezone = $this->getTimezone();

        if (is_null($timezone)) {
            return $schema;
        }

        return array_merge($schema, [
            'timezone' => $timezone,
            'serverTimezone' => $this->applicationTimezone(),
        ]);
    }

    private function toApplicationTimezone(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        try {
            return Carbon::parse($value, $this->applicationTimezone())
                ->setTimezone($this->applicationTimezone())
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return $value;
        }
    }

    private function defaultDisplayFormat(): ?string
    {
        $format = config('form-builder.dates.display_format');

        return is_string($format) && $format !== '' ? $format : null;
    }

    /**
     * @return array{from: string, to?: string, variant?: string, label?: string}
     */
    private function normalizeMarkedDate(mixed $date): array
    {
        if (! is_array($date)) {
            return ['from' => $this->markedDateString($date)];
        }

        if (! isset($date['from'])) {
            throw new InvalidArgumentException('A marked date range needs a [from] date.');
        }

        $marked = ['from' => $this->markedDateString($date['from'])];

        if (isset($date['to'])) {
            $marked['to'] = $this->markedDateString($date['to']);
        }

        foreach (['variant', 'label'] as $key) {
            if (is_string($date[$key] ?? null) && $date[$key] !== '') {
                $marked[$key] = $date[$key];
            }
        }

        return $marked;
    }

    private function markedDateString(mixed $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}/', $date, $matches) === 1) {
            return $matches[0];
        }

        throw new InvalidArgumentException('A marked date must be a Y-m-d string or a date object.');
    }

    private function defaultMarkedDatesUrl(): ?string
    {
        if ($this->markedDatesUrlSet) {
            return null;
        }

        $routeName = config('form-builder.dates.marked_dates_route');

        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        if (! Route::has($routeName)) {
            throw new InvalidArgumentException(sprintf('The marked dates route [%s] does not exist.', $routeName));
        }

        return route($routeName);
    }

    private function defaultWeekStart(): ?int
    {
        $weekStart = config('form-builder.dates.week_starts_on');

        if (is_null($weekStart)) {
            return null;
        }

        return (int) $weekStart === 1 ? 1 : 0;
    }

    private function applicationTimezone(): string
    {
        $timezone = config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }
}
