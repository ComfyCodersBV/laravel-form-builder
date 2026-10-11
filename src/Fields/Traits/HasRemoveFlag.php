<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields\Traits;

use Illuminate\Http\Request;

trait HasRemoveFlag
{
    protected ?string $removeFlag = null;

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
