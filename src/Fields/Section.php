<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\HtmlString;

class Section extends BaseField
{
    public const MAX_COLUMNS = 4;

    protected string $type = 'section';

    /** @var list<BaseField> */
    protected array $children = [];

    public function title(string $title): static
    {
        $this->attributes['title'] = $title;

        return $this;
    }

    public function description(string|HtmlString $description): static
    {
        $this->attributes['description'] = $description instanceof HtmlString
            ? $description->toHtml()
            : e($description);

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->attributes['columns'] = max(1, min(self::MAX_COLUMNS, $columns));

        return $this;
    }

    /**
     * @param  list<BaseField>  $fields
     */
    public function fields(array $fields): static
    {
        $this->children = [
            ...$this->children,
            ...$fields,
        ];

        return $this;
    }

    /**
     * @return list<BaseField>
     */
    public function getFields(): array
    {
        return $this->children;
    }

    public function getRules(): array
    {
        return [];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function nestedRules(): array
    {
        $rules = [];

        foreach ($this->children as $field) {
            $name = $field->getName();

            if ($name !== null && $name !== '' && ! $field instanceof self) {
                $rules[$name] = $field->getRules();
            }

            if (method_exists($field, 'nestedRules')) {
                $rules = array_merge($rules, $field->nestedRules());
            }
        }

        return $rules;
    }

    public function toSchema(): array
    {
        return array_merge(parent::toSchema(), [
            'fields' => Radio::normalize(array_map(
                fn (BaseField $field): array => $field->toSchema(),
                $this->children,
            )),
        ]);
    }
}
