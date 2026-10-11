<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\Carbon;
use Throwable;

class Date extends BaseField
{
    public const BROWSER_TIMEZONE = 'browser';

    protected string $type = 'date';

    protected ?string $timezone = null;

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
     * 0 = Sunday (default), 1 = Monday
     */
    public function weekStartsOn(int $dayOfWeek): static
    {
        $this->attributes['weekStartsOn'] = $dayOfWeek === 1 ? 1 : 0;

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
        $timezone = $this->getTimezone();

        if (is_null($timezone)) {
            return parent::toSchema();
        }

        return array_merge(parent::toSchema(), [
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

    private function applicationTimezone(): string
    {
        $timezone = config('app.timezone');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'UTC';
    }
}
