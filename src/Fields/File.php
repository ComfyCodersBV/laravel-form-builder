<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasMultiple;

class File extends BaseField
{
    use HasMultiple;

    protected string $type = 'file';

    public function accept(array|string ...$mimetypes): static
    {
        $mimetypes = array_merge(...array_map(fn ($m) => (array) $m, $mimetypes));

        $this->attributes['accept'] = $mimetypes;

        $this->rules[] = 'mimetypes:'.implode(',', $mimetypes);

        return $this;
    }
}
