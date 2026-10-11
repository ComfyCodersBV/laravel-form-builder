<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasMultiple;
use TranquilTools\FormBuilder\Fields\Traits\HasRemoveFlag;

class File extends BaseField
{
    use HasMultiple;
    use HasRemoveFlag;

    protected string $type = 'file';

    public function accept(array|string ...$mimetypes): static
    {
        $mimetypes = array_merge(...array_map(fn ($m) => (array) $m, $mimetypes));

        $this->attributes['accept'] = $mimetypes;

        $this->rules[] = 'mimetypes:'.implode(',', $mimetypes);

        return $this;
    }

    public function current(?string $url, ?string $name = null): static
    {
        if (is_null($url) || $url === '') {
            unset($this->attributes['currentUrl'], $this->attributes['currentName']);

            return $this;
        }

        $this->attributes['currentUrl'] = $url;
        $this->attributes['currentName'] = $name ?? basename((string) parse_url($url, PHP_URL_PATH));

        return $this;
    }

    public function preview(bool $preview = true): static
    {
        $this->attributes['preview'] = $preview;

        return $this;
    }
}
