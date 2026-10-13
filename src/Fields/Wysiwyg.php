<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use InvalidArgumentException;

class Wysiwyg extends BaseField
{
    public const DEFAULT_PROFILES = [
        'email' => [
            'bold',
            'italic',
            'underline',
            'link',
            'list',
            'blockquote',
        ],
    ];

    protected string $type = 'wysiwyg';

    protected bool $hasExplicitOptions = false;

    protected ?string $profile = null;

    /** @var list<string> */
    protected array $features = [];

    public function __construct()
    {
        $this->editor(config('form-builder.wysiwyg.default-editor'));

        $this->loadOptionsFromConfig($this->attributes['editor'], config('form-builder.wysiwyg.editors'));
    }

    public function editor(string $editor = 'quill'): static
    {
        $this->attributes['editor'] = $editor;

        return $this;
    }

    public function options(array $options): static
    {
        $this->attributes['options'] = $options;
        $this->hasExplicitOptions = true;

        return $this;
    }

    public function profile(string $profile): static
    {
        $features = config("form-builder.wysiwyg.profiles.{$profile}") ?? self::DEFAULT_PROFILES[$profile] ?? null;

        if (! is_array($features)) {
            throw new InvalidArgumentException(sprintf(
                'The WYSIWYG profile [%s] is not defined in form-builder.wysiwyg.profiles.',
                $profile,
            ));
        }

        $this->profile = $profile;
        $this->features = array_values(array_unique(array_filter($features, 'is_string')));

        return $this;
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();

        if (is_null($this->profile)) {
            return $schema;
        }

        return array_merge($schema, [
            'profile' => $this->profile,
            'features' => $this->features,
            'options' => $this->hasExplicitOptions ? $schema['options'] : [],
        ]);
    }

    private function loadOptionsFromConfig(string $editor, array $config): void
    {
        if (! array_key_exists($editor, $config)) {
            return;
        }

        $this->attributes['options'] = $config[$editor]['options'] ?? [];
    }
}
