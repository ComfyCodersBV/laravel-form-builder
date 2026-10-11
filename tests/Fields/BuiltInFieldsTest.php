<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File as Filesystem;
use TranquilTools\FormBuilder\Fields\BaseField;

it('loads, builds and serializes every built-in field', function () {
    $classes = collect(Filesystem::files(__DIR__.'/../../src/Fields'))
        ->map(fn (SplFileInfo $file): string => 'TranquilTools\\FormBuilder\\Fields\\'.$file->getBasename('.php'))
        ->filter(fn (string $class): bool => is_subclass_of($class, BaseField::class) && ! (new ReflectionClass($class))->isAbstract())
        ->values();

    expect($classes)->not->toBeEmpty();

    $classes->each(function (string $class): void {
        $schema = $class::make('field')->toSchema();

        expect($schema)->toHaveKey('type');
    });
});
