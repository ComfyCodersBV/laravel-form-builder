<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Wysiwyg;

it('renders the wysiwyg type with the default editor from config', function () {
    $schema = Wysiwyg::make('body')->toSchema();

    expect($schema['type'])->toBe('wysiwyg')
        ->and($schema['editor'])->toBe('quill');
});

it('loads editor options from config by default', function () {
    $options = Wysiwyg::make('body')->toSchema()['options'];

    expect($options)->toBeArray()
        ->and($options['theme'])->toBe('snow');
});

it('overrides the editor', function () {
    expect(Wysiwyg::make('body')->editor('trix')->toSchema()['editor'])->toBe('trix');
});

it('overrides the options', function () {
    $schema = Wysiwyg::make('body')->options(['theme' => 'bubble'])->toSchema();

    expect($schema['options'])->toBe(['theme' => 'bubble']);
});

it('sends the features of a profile instead of the configured editor options', function () {
    config()->set('form-builder.wysiwyg.profiles.email', ['bold', 'italic', 'link']);

    $schema = Wysiwyg::make('body')->profile('email')->toSchema();

    expect($schema['profile'])->toBe('email')
        ->and($schema['features'])->toBe(['bold', 'italic', 'link'])
        ->and($schema['options'])->toBe([]);
});

it('lets explicit options win over a profile in either order', function () {
    config()->set('form-builder.wysiwyg.profiles.email', ['bold']);

    $before = Wysiwyg::make('body')->options(['theme' => 'bubble'])->profile('email')->toSchema();
    $after = Wysiwyg::make('body')->profile('email')->options(['theme' => 'bubble'])->toSchema();

    expect($before['options'])->toBe(['theme' => 'bubble'])
        ->and($before['features'])->toBe(['bold'])
        ->and($after['options'])->toBe(['theme' => 'bubble'])
        ->and($after['features'])->toBe(['bold']);
});

it('ships an email profile, also for a published config without profiles', function () {
    expect(Wysiwyg::make('body')->profile('email')->toSchema()['features'])
        ->toContain('bold', 'link', 'list', 'blockquote');

    config()->set('form-builder.wysiwyg', [
        'default-editor' => 'quill',
        'editors' => [],
    ]);

    expect(Wysiwyg::make('body')->profile('email')->toSchema()['features'])
        ->toBe(Wysiwyg::DEFAULT_PROFILES['email']);
});

it('refuses a profile that is not configured', function () {
    Wysiwyg::make('body')->profile('unknown');
})->throws(InvalidArgumentException::class);
