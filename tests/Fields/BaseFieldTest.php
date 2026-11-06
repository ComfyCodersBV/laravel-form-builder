<?php

declare(strict_types=1);

use Illuminate\Support\HtmlString;
use TranquilTools\FormBuilder\Fields\Button;
use TranquilTools\FormBuilder\Fields\Hidden;
use TranquilTools\FormBuilder\Fields\Text;

it('builds a base schema with name, label and type', function () {
    $schema = Text::make('title')->label('Title')->toSchema();

    expect($schema['name'])->toBe('title')
        ->and($schema['label'])->toBe('Title')
        ->and($schema['type'])->toBe('text')
        ->and($schema['condition'])->toBeNull()
        ->and($schema['default'])->toBeNull()
        ->and($schema['rules'])->toBe([]);
});

it('exposes its name', function () {
    expect(Text::make('email')->getName())->toBe('email')
        ->and(Text::make()->getName())->toBeNull();
});

it('parses pipe separated rules into an array', function () {
    expect(Text::make('x')->rules('required|string|max:10')->getRules())
        ->toBe(['required', 'string', 'max:10']);
});

it('accepts array and string rules together', function () {
    expect(Text::make('x')->rules('required', ['max:5'])->getRules())
        ->toBe(['required', 'max:5']);
});

it('removes duplicate rules', function () {
    expect(Text::make('x')->rules('required', 'required')->getRules())
        ->toBe(['required']);
});

it('adds a required rule via required()', function () {
    expect(Text::make('x')->required()->getRules())->toBe(['required'])
        ->and(Text::make('x')->required(false)->getRules())->toBe([]);
});

it('coerces a null default into an empty string', function () {
    expect(Text::make('x')->default(null)->toSchema()['default'])->toBe('')
        ->and(Text::make('x')->default('hello')->toSchema()['default'])->toBe('hello');
});

it('sets the className attribute via class()', function () {
    expect(Text::make('x')->class('w-full')->toSchema()['className'])->toBe('w-full');
});

it('sets the wrapper class via wrapperClass(), the same key class() sets', function () {
    expect(Text::make('x')->wrapperClass('col-span-2')->toSchema()['className'])->toBe('col-span-2')
        ->and(Text::make('x')->class('a')->wrapperClass('b')->toSchema()['className'])->toBe('b');
});

it('sets the class of the element itself via inputClass()', function () {
    $schema = Text::make('x')->class('col-span-2')->inputClass('font-mono')->toSchema();

    expect($schema['inputClassName'])->toBe('font-mono')
        ->and($schema['className'])->toBe('col-span-2');
});

it('leaves both class keys out until they are set', function () {
    expect(Text::make('x')->toSchema())->not->toHaveKeys(['className', 'inputClassName']);
});

it('merges arbitrary attributes', function () {
    $schema = Text::make('x')->attributes(['data-test' => 'a', 'data-other' => 'b'])->toSchema();

    expect($schema['data-test'])->toBe('a')
        ->and($schema['data-other'])->toBe('b');
});

it('marks the field disabled and readonly', function () {
    $schema = Text::make('x')->disabled()->readonly()->toSchema();

    expect($schema['disabled'])->toBe('disabled')
        ->and($schema['readonly'])->toBe('readonly');

    expect(Text::make('x')->disabled(false)->toSchema())->not->toHaveKey('disabled');
});

it('stores a placeholder', function () {
    expect(Text::make('x')->placeholder('Type here')->toSchema()['placeholder'])->toBe('Type here');
});

it('escapes plain string help but keeps HtmlString raw', function () {
    expect(Text::make('x')->help('<b>hi</b>')->toSchema()['help'])->toBe('&lt;b&gt;hi&lt;/b&gt;')
        ->and(Text::make('x')->help(new HtmlString('<b>hi</b>'))->toSchema()['help'])->toBe('<b>hi</b>');
});

it('stores a display condition via if()', function () {
    expect(Text::make('x')->if('other == 1')->toSchema()['condition'])->toBe('other == 1');
});

it('renders the concrete field type', function () {
    expect(Hidden::make('token')->toSchema()['type'])->toBe('hidden');
});

it('refuses Vue event listeners and bindings as attributes', function (string $key) {
    Text::make('x')->attributes([$key => 'preview()']);
})->with([
    '@click',
    'v-on:click',
    'v-bind:title',
    ':title',
])->throws(InvalidArgumentException::class, 'fieldOverrides');

it('names the refused attribute and shows the Vue way', function () {
    expect(fn () => Button::make('preview')->attributes(['@click' => 'preview()']))
        ->toThrow(function (InvalidArgumentException $e) {
            expect($e->getMessage())
                ->toContain('"@click"')
                ->toContain('preview')
                ->toContain('onClick');
        });
});
