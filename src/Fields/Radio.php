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

    private const OPTION_KEYS = [
        'help',
        'description',
        'disabled',
        'href',
        'linkLabel',
        'target',
    ];

    public const NEW_TAB = '_blank';

    private const SAFE_SCHEMES = [
        'http',
        'https',
        'mailto',
        'tel',
    ];

    protected string $type = 'radio';

    public function description(string $description): static
    {
        $this->attributes['description'] = $description;

        return $this;
    }

    public function link(string $href, string $label, ?string $target = null): static
    {
        $this->attributes['href'] = $href;
        $this->attributes['linkLabel'] = $label;

        if (! is_null($target)) {
            $this->attributes['linkTarget'] = $target;
        }

        return $this;
    }

    public function openLinksInNewTab(bool $newTab = true): static
    {
        $this->attributes['linksInNewTab'] = $newTab;

        return $this;
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();

        $newTab = (bool) ($schema['linksInNewTab'] ?? false);
        unset($schema['linksInNewTab']);

        if (isset($schema['href']) && ! self::isSafeHref($schema['href'])) {
            unset($schema['href'], $schema['linkLabel'], $schema['linkTarget']);
        }

        if ($newTab && isset($schema['href'])) {
            $schema['linkTarget'] ??= self::NEW_TAB;
        }

        $options = $schema['options'] ?? null;

        if (is_array($options) && array_filter($options, is_array(...)) !== []) {
            $schema['options'] = self::normalizeOptions($options);
        }

        if ($newTab && is_array($schema['options'] ?? null)) {
            $schema['options'] = array_map(self::openInNewTab(...), $schema['options']);
        }

        return $schema;
    }

    private static function openInNewTab(mixed $option): mixed
    {
        if (! is_array($option) || ! isset($option['href'])) {
            return $option;
        }

        $option['target'] ??= self::NEW_TAB;

        return $option;
    }

    public static function isSafeHref(mixed $href): bool
    {
        if (! is_string($href) || trim($href) === '') {
            return false;
        }

        $href = trim($href);

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) !== 1) {
            return true;
        }

        $scheme = strtolower(strstr($href, ':', true) ?: '');

        return in_array($scheme, self::SAFE_SCHEMES, true);
    }

    /**
     * @param  array<string, mixed>  $option
     * @return array<string, mixed>
     */
    public static function safeLink(array $option): array
    {
        if (! array_key_exists('href', $option) || self::isSafeHref($option['href'])) {
            return $option;
        }

        unset($option['href'], $option['linkLabel'], $option['target']);

        return $option;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        if (! array_is_list($options)) {
            return array_map(
                fn (mixed $value, mixed $label): array => is_array($label)
                    ? self::normalizeOption(['value' => $value, ...$label])
                    : ['value' => $value, 'label' => (string) $label],
                array_keys($options),
                $options,
            );
        }

        $normalized = [];

        foreach ($options as $option) {
            if (is_array($option) && array_key_exists('value', $option)) {
                $normalized[] = self::normalizeOption($option);

                continue;
            }

            if (is_string($option) || is_numeric($option)) {
                $normalized[] = ['value' => $option, 'label' => (string) $option];
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $option
     * @return array<string, mixed>
     */
    private static function normalizeOption(array $option): array
    {
        $normalized = [
            'value' => $option['value'],
            'label' => (string) ($option['label'] ?? $option['value']),
        ];

        foreach (self::OPTION_KEYS as $key) {
            if (array_key_exists($key, $option) && ! is_null($option[$key])) {
                $normalized[$key] = $option[$key];
            }
        }

        return self::safeLink($normalized);
    }

    public static function normalize(array $schemas): array
    {
        $groups = [];
        $skipIndex = [];

        $normalizeOptions = fn ($options): array => self::normalizeOptions($options);

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
                $g['options'][] = self::safeLink(array_filter([
                    'value' => $schema['value'],
                    'label' => $schema['label'] ?? (string) $schema['value'],
                    'help' => $schema['help'] ?? null,
                    'description' => $schema['description'] ?? null,
                    'disabled' => isset($schema['disabled']),
                    'href' => $schema['href'] ?? null,
                    'linkLabel' => $schema['linkLabel'] ?? null,
                    'target' => $schema['linkTarget'] ?? null,
                ], fn ($v) => $v !== null && $v !== false));

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
