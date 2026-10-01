<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use TranquilTools\FormBuilder\Fields\Traits\HasInline;
use TranquilTools\FormBuilder\Fields\Traits\HasOptions;
use TranquilTools\FormBuilder\Fields\Traits\HasValue;

class Radio extends BaseField
{
    use HasInline;
    use HasOptions;
    use HasValue;

    protected string $type = 'radio';

    public static function normalize(array $schemas): array
    {
        $groups = [];
        $skipIndex = [];

        $normalizeOptions = function ($options): array {
            $out = [];
            if ($options === null) {
                return $out;
            }
            if (is_array($options)) {
                $isAssoc = array_keys($options) !== range(0, count($options) - 1);

                if ($isAssoc) {
                    foreach ($options as $value => $label) {
                        $out[] = ['value' => $value, 'label' => (string) $label];
                    }

                    return $out;
                }

                foreach ($options as $opt) {
                    if (is_array($opt) && array_key_exists('value', $opt)) {
                        $out[] = [
                            'value' => $opt['value'],
                            'label' => (string) ($opt['label'] ?? $opt['value']),
                        ];
                    } elseif (is_string($opt) || is_numeric($opt)) {
                        $out[] = ['value' => $opt, 'label' => (string) $opt];
                    }
                }

                return $out;
            }

            return $out;
        };

        foreach ($schemas as $i => $schema) {
            if (! is_array($schema)) {
                continue;
            }

            $type = $schema['type'] ?? null;
            $name = $schema['name'] ?? null;

            if ($type === 'radio' && ! empty($name) && array_key_exists('value', $schema)) {
                if (! isset($groups[$name])) {
                    $groups[$name] = [
                        'firstIndex' => $i,
                        'group' => [
                            'type' => 'radios',
                            'name' => $name,
                            'label' => null,
                            'inline' => (bool) ($schema['inline'] ?? false),
                            'options' => [],
                        ],
                    ];
                }

                $g = &$groups[$name]['group'];
                $groups[$name]['firstIndex'] = min($groups[$name]['firstIndex'], $i);
                $g['inline'] = $g['inline'] || (bool) ($schema['inline'] ?? false);
                $g['options'][] = array_filter([
                    'value' => $schema['value'],
                    'label' => $schema['label'] ?? (string) $schema['value'],
                    'help' => $schema['help'] ?? null,
                    'disabled' => isset($schema['disabled']),
                ], fn ($v) => $v !== null && $v !== false);

                $skipIndex[$i] = true;

                continue;
            }

            if ($type === 'radios' && ! empty($name)) {
                if (! isset($groups[$name])) {
                    $groups[$name] = [
                        'firstIndex' => $i,
                        'group' => [
                            'type' => 'radios',
                            'name' => $name,
                            'label' => $schema['label'] ?? null,
                            'inline' => (bool) ($schema['inline'] ?? false),
                            'options' => $normalizeOptions($schema['options'] ?? []),
                        ],
                    ];
                } else {
                    $g = &$groups[$name]['group'];
                    $groups[$name]['firstIndex'] = min($groups[$name]['firstIndex'], $i);
                    $g['label'] = $g['label'] ?? ($schema['label'] ?? null);
                    $g['inline'] = $g['inline'] || (bool) ($schema['inline'] ?? false);
                    $g['options'] = array_merge($g['options'], $normalizeOptions($schema['options'] ?? []));
                }
                $skipIndex[$i] = true;
            }
        }

        $output = [];
        $firstIndexMap = [];
        foreach ($groups as $name => $data) {
            $firstIndexMap[$data['firstIndex']] = $data['group'];
        }

        $count = count($schemas);
        for ($i = 0; $i < $count; $i++) {
            if (isset($firstIndexMap[$i])) {
                $output[] = $firstIndexMap[$i];
            }
            if (! isset($skipIndex[$i])) {
                $output[] = $schemas[$i];
            }
        }

        return $output;
    }
}
