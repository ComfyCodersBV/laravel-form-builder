<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Textarea extends BaseField
{
    protected string $type = 'textarea';

    public function rows(int $rows): static
    {
        $this->attributes['rows'] = max(1, $rows);

        return $this;
    }
}
