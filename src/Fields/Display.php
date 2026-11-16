<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Stringable;

class Display extends BaseField
{
    protected string $type = 'display';

    public function content(string|int|float|Stringable|null $content): static
    {
        if (is_null($content)) {
            unset($this->attributes['content']);

            return $this;
        }

        $this->attributes['content'] = (string) $content;

        return $this;
    }

    public function expression(string $expression): static
    {
        $this->attributes['expression'] = $expression;

        return $this;
    }

    public function submitsValue(): bool
    {
        return false;
    }

    public function getRules(): array
    {
        return [];
    }

    public function marksRequired(): bool
    {
        return false;
    }
}
