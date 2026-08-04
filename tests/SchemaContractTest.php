<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('emits the schema version every renderer checks against', function () {
    $schema = FormConfig::make()
        ->fields([Text::make('title')])
        ->jsonSerialize();

    expect($schema['schemaVersion'])->toBe(FormConfig::SCHEMA_VERSION)
        ->and($schema['schemaVersion'])->toMatch('/^\d+\.\d+$/');
});
