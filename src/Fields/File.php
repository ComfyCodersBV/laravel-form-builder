<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Http\Request;
use TranquilTools\FormBuilder\Fields\Traits\HasMultiple;

class File extends BaseField
{
    use HasMultiple;

    protected string $type = 'file';

    protected ?string $removeFlag = null;

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

    public function removable(bool $removable = true, ?string $flag = null): static
    {
        $this->removeFlag = $removable ? ($flag ?? 'remove_'.$this->getName()) : null;

        return $this;
    }

    public function getRemoveFlag(): ?string
    {
        return $this->removeFlag;
    }

    public function removalRequested(?Request $request = null): bool
    {
        if (is_null($this->removeFlag)) {
            return false;
        }

        return ($request ?? request())->boolean($this->removeFlag);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function nestedRules(): array
    {
        if (is_null($this->removeFlag)) {
            return [];
        }

        return [
            $this->removeFlag => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function toSchema(): array
    {
        if (is_null($this->removeFlag)) {
            return parent::toSchema();
        }

        return array_merge(parent::toSchema(), [
            'removeFlag' => $this->removeFlag,
        ]);
    }
}
