<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\File;
use TranquilTools\FormBuilder\Fields\Files;
use TranquilTools\FormBuilder\FormConfig;

it('renders the file type', function () {
    expect(File::make('avatar')->toSchema()['type'])->toBe('file');
});

it('sets the accept attribute and a mimetypes rule', function () {
    $schema = File::make('doc')->accept('application/pdf', 'image/png')->toSchema();

    expect($schema['accept'])->toBe(['application/pdf', 'image/png'])
        ->and($schema['rules'])->toBe(['mimetypes:application/pdf,image/png']);
});

it('accepts mimetypes passed as an array', function () {
    $schema = File::make('doc')->accept(['image/jpeg', 'image/png'])->toSchema();

    expect($schema['accept'])->toBe(['image/jpeg', 'image/png'])
        ->and($schema['rules'])->toBe(['mimetypes:image/jpeg,image/png']);
});

it('flags multiple selection', function () {
    expect(File::make('photos')->multiple()->toSchema()['multiple'])->toBeTrue();
});

it('Files is a multiple file field', function () {
    $schema = Files::make('attachments')->toSchema();

    expect($schema['type'])->toBe('file')
        ->and($schema['multiple'])->toBeTrue();
});

it('describes the current file', function () {
    $schema = File::make('avatar')->current('https://cdn.test/avatars/jane.jpg?v=2')->toSchema();

    expect($schema['currentUrl'])->toBe('https://cdn.test/avatars/jane.jpg?v=2')
        ->and($schema['currentName'])->toBe('jane.jpg')
        ->and(File::make('avatar')->current('/files/a.pdf', 'Contract.pdf')->toSchema()['currentName'])->toBe('Contract.pdf')
        ->and(File::make('avatar')->current(null)->toSchema())->not->toHaveKey('currentUrl');
});

it('turns the image preview off', function () {
    expect(File::make('avatar')->preview(false)->toSchema()['preview'])->toBeFalse();
});

it('sends and validates a remove flag', function () {
    $field = File::make('avatar')->removable();

    expect($field->toSchema()['removeFlag'])->toBe('remove_avatar')
        ->and($field->nestedRules())->toBe(['remove_avatar' => ['nullable', 'boolean']])
        ->and(File::make('avatar')->removable(true, 'drop_avatar')->getRemoveFlag())->toBe('drop_avatar')
        ->and(File::make('avatar')->toSchema())->not->toHaveKey('removeFlag')
        ->and(File::make('avatar')->nestedRules())->toBe([]);
});

it('reads the remove flag from the request', function () {
    $field = File::make('avatar')->removable();

    expect($field->removalRequested(Request::create('/', 'POST', ['remove_avatar' => '1'])))->toBeTrue()
        ->and($field->removalRequested(Request::create('/', 'POST', ['remove_avatar' => '0'])))->toBeFalse()
        ->and(File::make('avatar')->removalRequested(Request::create('/', 'POST', ['remove_avatar' => '1'])))->toBeFalse();
});

it('validates the remove flag through the form', function () {
    $validated = FormConfig::make([File::make('avatar')->removable()])
        ->validate(Request::create('/', 'POST', ['remove_avatar' => true]));

    expect($validated)->toBe(['remove_avatar' => true]);
});

it('describes a list of current files', function () {
    $schema = Files::make('attachments')
        ->current([
            ['key' => 0, 'name' => 'offerte.pdf', 'size' => 81234, 'url' => 'https://cdn.test/files/offerte.pdf'],
            ['key' => 'logo', 'url' => 'https://cdn.test/files/logo.png?v=3'],
        ])
        ->toSchema();

    expect($schema['currentFiles'])->toBe([
        ['key' => '0', 'name' => 'offerte.pdf', 'size' => 81234, 'url' => 'https://cdn.test/files/offerte.pdf'],
        ['key' => 'logo', 'name' => 'logo.png', 'url' => 'https://cdn.test/files/logo.png?v=3'],
    ])
        ->and($schema)->not->toHaveKey('currentUrl')
        ->and(Files::make('attachments')->current([])->toSchema())->not->toHaveKey('currentFiles');
});

it('keeps the single current file on a Files field given a url', function () {
    $schema = Files::make('attachments')->current('/files/a.pdf', 'Contract.pdf')->toSchema();

    expect($schema['currentUrl'])->toBe('/files/a.pdf')
        ->and($schema['currentName'])->toBe('Contract.pdf')
        ->and($schema)->not->toHaveKey('currentFiles');
});

it('validates removal keys against the current files', function () {
    $field = Files::make('attachments')
        ->current([
            ['key' => 'a', 'name' => 'a.pdf'],
            ['key' => 'b', 'name' => 'b.pdf'],
        ])
        ->removable();

    $form = FormConfig::make([$field]);

    expect($field->toSchema()['removeFlag'])->toBe('remove_attachments')
        ->and(array_keys($field->nestedRules()))->toBe(['remove_attachments', 'remove_attachments.*'])
        ->and($field->nestedRules()['remove_attachments'])->toBe(['nullable', 'array'])
        ->and($form->validate(Request::create('/', 'POST', ['remove_attachments' => ['b']])))->toBe(['remove_attachments' => ['b']])
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['remove_attachments' => ['c']])))->toThrow(ValidationException::class)
        ->and(fn () => $form->validate(Request::create('/', 'POST', ['remove_attachments' => '1'])))->toThrow(ValidationException::class);
});

it('reads the removed keys from the request', function () {
    $field = Files::make('attachments')
        ->current([
            ['key' => 'a', 'name' => 'a.pdf'],
            ['key' => 'b', 'name' => 'b.pdf'],
        ])
        ->removable();

    $request = Request::create('/', 'POST', ['remove_attachments' => ['b', 'unknown']]);

    expect($field->removedKeys($request))->toBe(['b'])
        ->and($field->removalRequested($request))->toBeTrue()
        ->and($field->removedKeys(Request::create('/', 'POST')))->toBe([])
        ->and($field->removalRequested(Request::create('/', 'POST')))->toBeFalse();
});

it('reads no removed keys for a single current file', function () {
    $field = File::make('avatar')->current('/files/a.jpg')->removable();

    $request = Request::create('/', 'POST', ['remove_avatar' => '1']);

    expect($field->removedKeys($request))->toBe([])
        ->and($field->removalRequested($request))->toBeTrue()
        ->and($field->nestedRules())->toBe(['remove_avatar' => ['nullable', 'boolean']]);
});
