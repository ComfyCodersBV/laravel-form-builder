<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Wysiwyg;
use TranquilTools\FormBuilder\FormBuilderServiceProvider;

it('reads a published vue-form-builder config file', function () {
    config()->set('vue-form-builder.wysiwyg.default-editor', 'tiptap');

    (new FormBuilderServiceProvider(app()))->register();

    expect(config('form-builder.wysiwyg.default-editor'))->toBe('tiptap')
        ->and(Wysiwyg::make('body')->toSchema()['editor'])->toBe('tiptap');
});

it('keeps its own defaults for keys the legacy file does not set', function () {
    config()->set('vue-form-builder.wysiwyg.default-editor', 'tiptap');

    (new FormBuilderServiceProvider(app()))->register();

    expect(config('form-builder.key_value.masked_key_pattern'))->not->toBeNull();
});

it('leaves configuration alone when no legacy file is published', function () {
    $before = config('form-builder');

    (new FormBuilderServiceProvider(app()))->register();

    expect(config('form-builder'))->toBe($before);
});

it('still resolves translations under the legacy namespace', function () {
    expect(trans('vue-form-builder::fields.increment'))
        ->toBe(trans('form-builder::fields.increment'))
        ->not->toBe('vue-form-builder::fields.increment');
});
