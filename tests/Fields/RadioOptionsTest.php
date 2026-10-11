<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Radio;

it('keeps a description and a link per option', function () {
    $schema = Radio::make('unit')->options([
        ['value' => 1, 'label' => 'Hall', 'description' => 'Ground floor', 'href' => '/units/1/move', 'linkLabel' => 'Move'],
        ['value' => 2, 'label' => 'Attic'],
    ])->toSchema();

    expect($schema['options'])->toBe([
        ['value' => 1, 'label' => 'Hall', 'description' => 'Ground floor', 'href' => '/units/1/move', 'linkLabel' => 'Move'],
        ['value' => 2, 'label' => 'Attic'],
    ]);
});

it('accepts keyed options with details', function () {
    $schema = Radio::make('unit')->options([
        'hall' => ['label' => 'Hall', 'href' => 'https://example.com/hall', 'linkLabel' => 'View'],
        'attic' => 'Attic',
    ])->toSchema();

    expect($schema['options'])->toBe([
        ['value' => 'hall', 'label' => 'Hall', 'href' => 'https://example.com/hall', 'linkLabel' => 'View'],
        ['value' => 'attic', 'label' => 'Attic'],
    ]);
});

it('leaves plain keyed options alone', function () {
    expect(Radio::make('unit')->options(['a' => 'A'])->toSchema()['options'])->toBe(['a' => 'A']);
});

it('drops a link with an unsafe scheme', function () {
    $schema = Radio::make('unit')->options([
        ['value' => 1, 'label' => 'Hall', 'href' => 'javascript:alert(1)', 'linkLabel' => 'Move'],
        ['value' => 2, 'label' => 'Attic', 'href' => ' JavaScript:alert(1)', 'linkLabel' => 'Move'],
    ])->toSchema();

    expect($schema['options'])->toBe([
        ['value' => 1, 'label' => 'Hall'],
        ['value' => 2, 'label' => 'Attic'],
    ]);
});

it('carries the description and link of individual radios into the group', function () {
    $normalized = Radio::normalize([
        Radio::make('plan')->value('a')->label('A')->description('Monthly')->link('/plans/a', 'Details')->toSchema(),
        Radio::make('plan')->value('b')->label('B')->link('data:text/html,x', 'Details')->toSchema(),
    ]);

    expect($normalized[0]['options'])->toBe([
        ['value' => 'a', 'label' => 'A', 'description' => 'Monthly', 'href' => '/plans/a', 'linkLabel' => 'Details'],
        ['value' => 'b', 'label' => 'B'],
    ]);
});

it('recognises safe links', function (string $href, bool $safe) {
    expect(Radio::isSafeHref($href))->toBe($safe);
})->with([
    ['/units/1', true],
    ['units/1', true],
    ['?year=2026', true],
    ['https://example.com', true],
    ['mailto:info@example.com', true],
    ['javascript:alert(1)', false],
    ['vbscript:x', false],
    ['data:text/html,x', false],
]);
