<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\FormConfig;

it('sends tiles for a radio with options', function () {
    $schema = FormConfig::make()
        ->fields([
            Radio::make('unit')
                ->options([
                    ['value' => 1, 'label' => 'Hall', 'description' => 'Up to 120 people'],
                ])
                ->tiles(),
        ])
        ->jsonSerialize();

    expect($schema['fields'][0]['tiles'])->toBeTrue()
        ->and($schema['fields'][0]['options'][0]['description'])->toBe('Up to 120 people');
});

it('keeps tiles on the group built from separate radios', function () {
    $normalized = Radio::normalize([
        Radio::make('plan')->value('a')->label('A')->tiles()->toSchema(),
        Radio::make('plan')->value('b')->label('B')->toSchema(),
    ]);

    expect($normalized[0]['tiles'])->toBeTrue();
});

it('keeps tiles on a radios schema', function () {
    $normalized = Radio::normalize([
        ['type' => 'radios', 'name' => 'plan', 'tiles' => true, 'options' => ['a' => 'A']],
    ]);

    expect($normalized[0]['tiles'])->toBeTrue();
});

it('sends no tiles key for a group without tiles', function () {
    $normalized = Radio::normalize([
        Radio::make('plan')->value('a')->label('A')->toSchema(),
    ]);

    expect($normalized[0])->not->toHaveKey('tiles');
});
