<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder;

use TranquilTools\FormBuilder\Fields\BaseField;
use TranquilTools\FormBuilder\Fields\Section;

final class ConditionChain
{
    /**
     * @param  array<int, BaseField>  $fields
     */
    public static function apply(array $fields): void
    {
        $holders = [];
        $conditional = [];
        $counts = [];

        self::collect($fields, [], $holders, $conditional, $counts);

        $holders = array_filter(
            $holders,
            fn (string $name): bool => $counts[$name] === 1,
            ARRAY_FILTER_USE_KEY,
        );

        foreach ($conditional as $field) {
            $visibility = $field->getVisibleWhen();

            if (is_null($visibility)) {
                continue;
            }

            $field->inheritVisibility(self::hidingConditions($visibility['field'], $holders, []));
        }
    }

    /**
     * @param  array<int, BaseField>  $fields
     * @param  list<BaseField>  $sections
     * @param  array<string, list<BaseField>>  $holders
     * @param  list<BaseField>  $conditional
     * @param  array<string, int>  $counts
     */
    private static function collect(array $fields, array $sections, array &$holders, array &$conditional, array &$counts): void
    {
        foreach ($fields as $field) {
            $own = is_null($field->getVisibleWhen()) ? [] : [$field];
            $conditional = [
                ...$conditional,
                ...$own,
            ];

            if ($field instanceof Section) {
                self::collect($field->getFields(), [...$sections, ...$own], $holders, $conditional, $counts);

                continue;
            }

            $name = $field->getName();

            if (is_null($name) || $name === '') {
                continue;
            }

            $counts[$name] = ($counts[$name] ?? 0) + 1;
            $holders[$name] = [
                ...($holders[$name] ?? []),
                ...$sections,
                ...$own,
            ];
        }
    }

    /**
     * @param  array<string, list<BaseField>>  $holders
     * @param  list<string>  $visiting
     * @return list<array{field: string, values: list<string>}>
     */
    private static function hidingConditions(string $name, array $holders, array $visiting): array
    {
        if (in_array($name, $visiting, true)) {
            return [];
        }

        $conditions = [];

        foreach ($holders[$name] ?? [] as $holder) {
            $visibility = $holder->getVisibleWhen();

            if (is_null($visibility)) {
                continue;
            }

            $conditions = [
                ...$conditions,
                $visibility,
                ...self::hidingConditions($visibility['field'], $holders, [...$visiting, $name]),
            ];
        }

        $unique = [];

        foreach ($conditions as $condition) {
            $unique[json_encode($condition, JSON_THROW_ON_ERROR)] = $condition;
        }

        return array_values($unique);
    }
}
