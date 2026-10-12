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

it('keeps a link target per option', function () {
    $schema = Radio::make('unit')->options([
        ['value' => 1, 'label' => 'Hall', 'href' => '/units/1', 'linkLabel' => 'Open', 'target' => '_blank'],
        ['value' => 2, 'label' => 'Attic', 'href' => 'javascript:alert(1)', 'linkLabel' => 'Evil', 'target' => '_blank'],
    ])->toSchema();

    expect($schema['options'])->toBe([
        ['value' => 1, 'label' => 'Hall', 'href' => '/units/1', 'linkLabel' => 'Open', 'target' => '_blank'],
        ['value' => 2, 'label' => 'Attic'],
    ]);
});

it('opens every option link in a new tab on request', function () {
    $schema = Radio::make('unit')
        ->options([
            ['value' => 1, 'label' => 'Hall', 'href' => '/units/1', 'linkLabel' => 'Open'],
            ['value' => 2, 'label' => 'Attic', 'href' => '/units/2', 'linkLabel' => 'Open', 'target' => '_self'],
            ['value' => 3, 'label' => 'Cellar'],
        ])
        ->openLinksInNewTab()
        ->toSchema();

    expect(array_column($schema['options'], 'target', 'value'))->toBe([1 => '_blank', 2 => '_self'])
        ->and($schema)->not->toHaveKey('linksInNewTab');
});

it('carries the link target of separate radios into the group', function () {
    $schemas = Radio::normalize([
        Radio::make('plan')->value('a')->label('A')->link('https://example.com/a', 'Terms', Radio::NEW_TAB)->toSchema(),
        Radio::make('plan')->value('b')->label('B')->link('/plans/b', 'Terms')->openLinksInNewTab()->toSchema(),
        Radio::make('plan')->value('c')->label('C')->link('/plans/c', 'Terms')->toSchema(),
    ]);

    expect(array_column($schemas[0]['options'], 'target', 'value'))->toBe(['a' => '_blank', 'b' => '_blank']);
});
