<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

function schemaWithTheme(array $config = []): array
{
    config()->set('form-builder.theme', $config);

    return FormConfig::make()
        ->fields([Text::make('title')])
        ->jsonSerialize();
}

it('carries the configured theme in the schema', function () {
    $schema = schemaWithTheme([
        'label' => 'font-bold',
        'error' => 'text-rose-700',
    ]);

    expect($schema['theme'])->toBe([
        'label' => 'font-bold',
        'error' => 'text-rose-700',
    ]);
});

it('drops unset theme keys so the renderer keeps its own default', function () {
    $schema = schemaWithTheme([
        'wrapper' => null,
        'label' => '',
        'error' => 'text-rose-700',
    ]);

    expect($schema['theme'])->toBe(['error' => 'text-rose-700']);
});

it('lets a field override the form-wide theme without discarding the rest', function () {
    config()->set('form-builder.theme', [
        'label' => 'font-bold',
        'error' => 'text-rose-700',
    ]);

    $schema = FormConfig::make()
        ->fields([Text::make('title')->theme(['label' => 'sr-only'])])
        ->jsonSerialize();

    expect($schema['theme'])->toBe([
        'label' => 'font-bold',
        'error' => 'text-rose-700',
    ])
        ->and($schema['fields'][0]['theme'])->toBe(['label' => 'sr-only']);
});

it('merges repeated theme calls on a field', function () {
    $field = Text::make('title')
        ->theme(['label' => 'sr-only'])
        ->theme(['error' => 'hidden']);

    expect($field->toSchema()['theme'])->toBe([
        'label' => 'sr-only',
        'error' => 'hidden',
    ]);
});
