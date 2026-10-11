<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasRemoveFlag;

class Password extends Text
{
    use HasRemoveFlag;

    protected string $type = 'password';

    public function stored(bool $stored = true): static
    {
        $this->attributes['stored'] = $stored;

        return $this;
    }
}
