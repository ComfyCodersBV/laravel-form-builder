<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Wysiwyg extends BaseField
{
    protected string $type = 'wysiwyg';

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

        return $this;
    }

    private function loadOptionsFromConfig(string $editor, array $config): void
    {
        if (! array_key_exists($editor, $config)) {
            return;
        }

        $this->options($config[$editor]['options'] ?? []);
    }
}
