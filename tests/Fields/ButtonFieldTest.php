<?php

declare(strict_types=1);

use Illuminate\Support\HtmlString;
use TranquilTools\FormBuilder\Fields\Button;
use TranquilTools\FormBuilder\Fields\DeleteButton;
use TranquilTools\FormBuilder\Fields\Submit;

it('renders a plain button with a variant', function () {
    $schema = Button::make()->label('Do it')->variant('secondary')->toSchema();

    expect($schema['type'])->toBe('button')
        ->and($schema['label'])->toBe('Do it')
        ->and($schema['variant'])->toBe('secondary');
});

it('omits confirm keys when no confirmation is configured', function () {
    $schema = Button::make()->label('Go')->toSchema();

    expect($schema)->not->toHaveKey('confirmTitle')
        ->and($schema)->not->toHaveKey('cancelLabel')
        ->and($schema)->not->toHaveKey('deleteUrl');
});

it('adds a default cancel label once a confirmation is set', function () {
    $schema = Button::make()
        ->label('Delete')
        ->confirmTitle('Sure?')
        ->confirmMessage('No undo')
        ->deleteUrl('/items/1')
        ->toSchema();

    expect($schema['confirmTitle'])->toBe('Sure?')
        ->and($schema['confirmMessage'])->toBe('No undo')
        ->and($schema['deleteUrl'])->toBe('/items/1')
        ->and($schema['cancelLabel'])->toBe('Cancel');
});

it('keeps an explicit cancel label', function () {
    $schema = Button::make()
        ->confirmTitle('Sure?')
        ->cancelLabel('Nope')
        ->toSchema();

    expect($schema['cancelLabel'])->toBe('Nope');
});

it('defaults a submit button to the save label', function () {
    $schema = Submit::make()->toSchema();

    expect($schema['type'])->toBe('submit')
        ->and($schema['label'])->toBe('Save');
});

it('keeps a custom submit label', function () {
    expect(Submit::make()->label('Publish')->toSchema()['label'])->toBe('Publish');
});

it('fills in delete button defaults', function () {
    $schema = DeleteButton::make()->toSchema();

    expect($schema['type'])->toBe('delete')
        ->and($schema['label'])->toBe('Delete')
        ->and($schema['cancelLabel'])->toBe('Cancel')
        ->and($schema['confirmTitle'])->toBe('Are you sure?')
        ->and($schema['confirmMessage'])->toBe('This action cannot be undone. This will permanently delete this record.');
});

it('keeps an HtmlString label raw and derives a plain label from its text', function () {
    $schema = Submit::make()
        ->label(new HtmlString('<svg class="icon"></svg> <b>Save</b>'))
        ->toSchema();

    expect($schema['labelHtml'])->toBe('<svg class="icon"></svg> <b>Save</b>')
        ->and($schema['label'])->toBe('Save');
});

it('falls back to the default label and an aria label for an icon-only button', function () {
    $schema = Submit::make()
        ->label(new HtmlString('<svg class="icon"></svg>'))
        ->ariaLabel('Add')
        ->toSchema();

    expect($schema['labelHtml'])->toBe('<svg class="icon"></svg>')
        ->and($schema['label'])->toBe(trans('form-builder::buttons.save'))
        ->and($schema['ariaLabel'])->toBe('Add');
});

it('adds no html keys for a plain string label', function () {
    $schema = Button::make()->label('Plain <b>')->toSchema();

    expect($schema)->not->toHaveKey('labelHtml')
        ->and($schema)->not->toHaveKey('ariaLabel')
        ->and($schema['label'])->toBe('Plain <b>');
});

it('drops the html label again when a plain label replaces it', function () {
    $schema = Button::make()
        ->label(new HtmlString('<i></i>'))
        ->label('Text again')
        ->toSchema();

    expect($schema)->not->toHaveKey('labelHtml')
        ->and($schema['label'])->toBe('Text again');
});
