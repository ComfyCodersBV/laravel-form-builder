<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields\Traits;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        if (! is_null($this->removableKeys())) {
            return $this->removedKeys($request) !== [];
        }

        return ($request ?? request())->boolean($this->removeFlag);
    }

    /**
     * @return list<string>
     */
    public function removedKeys(?Request $request = null): array
    {
        $keys = $this->removableKeys();

        if (is_null($this->removeFlag) || is_null($keys)) {
            return [];
        }

        $requested = ($request ?? request())->input($this->removeFlag);

        if (! is_array($requested)) {
            return [];
        }

        $requested = array_map(fn (mixed $key): string => is_scalar($key) ? (string) $key : '', $requested);

        return array_values(array_filter($keys, fn (string $key): bool => in_array($key, $requested, true)));
    }

    /**
     * @return list<string>|null
     */
    protected function removableKeys(): ?array
    {
        return null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function nestedRules(): array
    {
        if (is_null($this->removeFlag)) {
            return [];
        }

        $keys = $this->removableKeys();

        if (is_null($keys)) {
            return [
                $this->removeFlag => [
                    'nullable',
                    'boolean',
                ],
            ];
        }

        return [
            $this->removeFlag => [
                'nullable',
                'array',
            ],
            "{$this->removeFlag}.*" => [
                'string',
                Rule::in($keys),
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
