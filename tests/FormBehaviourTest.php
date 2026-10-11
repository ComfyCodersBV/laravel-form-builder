<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

it('hides a label visually', function () {
    expect(Text::make('q')->label('Search')->hideLabel()->toSchema()['hideLabel'])->toBeTrue()
        ->and(Text::make('q')->hideLabel()->hideLabel(false)->toSchema())->not->toHaveKey('hideLabel');
});

it('asks the renderer to reset after a successful submit', function () {
    expect(FormConfig::make()->jsonSerialize()['resetOnSuccess'])->toBeFalse()
        ->and(FormConfig::make()->resetOnSuccess()->jsonSerialize()['resetOnSuccess'])->toBeTrue();
});

it('asks the renderer to submit on every change', function () {
    expect(FormConfig::make()->submitOnChange()->jsonSerialize()['submitOnChange'])->toBeTrue();
});

it('fills a GET form from the query string', function () {
    app()->instance('request', Request::create('/bookings', 'GET', ['year' => '2025', 'page' => '3']));

    $schema = FormConfig::make([
        Select::make('year'),
        Section::make()->fields([Text::make('q')]),
    ])->method('get')->fill(['year' => '2026', 'q' => 'hall'])->jsonSerialize();

    expect($schema['defaults'])->toBe(['year' => '2025', 'q' => 'hall']);
});

it('leaves the defaults of a POST form alone', function () {
    app()->instance('request', Request::create('/bookings', 'GET', ['year' => '2025']));

    $schema = FormConfig::make([Select::make('year')])->fill(['year' => '2026'])->jsonSerialize();

    expect($schema['defaults'])->toBe(['year' => '2026']);
});

it('can turn filling from the query on or off', function () {
    app()->instance('request', Request::create('/bookings', 'GET', ['year' => '2025']));

    expect(FormConfig::make([Select::make('year')])->method('get')->fillFromQuery(false)->jsonSerialize()['defaults'])
        ->toEqual((object) [])
        ->and(FormConfig::make([Select::make('year')])->fillFromQuery()->jsonSerialize()['defaults'])
        ->toBe(['year' => '2025']);
});
