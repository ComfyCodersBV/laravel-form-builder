<?php

declare(strict_types=1);

use Illuminate\Validation\Rule;
use TranquilTools\FormBuilder\Fields\Checkboxes;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\Fields\Translatable;
use TranquilTools\FormBuilder\FormConfig;

describe('required indicator', function () {
    it('marks a field whose rules contain required', function (array|string $rules) {
        expect(Text::make('title')->rules($rules)->toSchema())->toHaveKey('markRequired', true);
    })->with([
        'string' => ['required|string'],
        'array' => [['string', 'required']],
        'static requiredIf' => [[Rule::requiredIf(true)]],
    ]);

    it('does not mark optional or conditionally required fields', function (array|string $rules) {
        expect(Text::make('title')->rules($rules)->toSchema())->not->toHaveKey('markRequired');
    })->with([
        'nullable' => ['nullable|string'],
        'required_if' => ['required_if:type,company'],
        'required_with' => ['required_with:other'],
        'false requiredIf' => [[Rule::requiredIf(false)]],
        'callable requiredIf' => [[Rule::requiredIf(fn (): bool => true)]],
    ]);

    it('lets a field opt in or out', function () {
        expect(Text::make('a')->rules('required_if:b,1')->markRequired()->toSchema())->toHaveKey('markRequired', true)
            ->and(Text::make('a')->required()->markRequired(false)->toSchema())->not->toHaveKey('markRequired');
    });

    it('can be switched off app-wide, while a field can still opt in', function () {
        config()->set('form-builder.required_indicator', false);

        expect(Text::make('a')->required()->toSchema())->not->toHaveKey('markRequired')
            ->and(Text::make('a')->markRequired()->toSchema())->toHaveKey('markRequired', true);
    });

    it('marks radio and checkbox groups but not sections', function () {
        $section = Section::make('group')->fields([Text::make('inner')->required()]);

        expect(Radio::make('choice')->options(['a' => 'A'])->required()->toSchema())->toHaveKey('markRequired', true)
            ->and(Checkboxes::make('days')->options(['a' => 'A'])->required()->toSchema())->toHaveKey('markRequired', true)
            ->and($section->toSchema())->not->toHaveKey('markRequired')
            ->and($section->toSchema()['fields'][0])->toHaveKey('markRequired', true);
    });

    it('marks a translatable field when one of its locales is required', function () {
        expect(Translatable::make('name')->locales(['nl', 'en'])->required()->toSchema())->toHaveKey('markRequired', true)
            ->and(Translatable::make('name')->locales(['nl', 'en'])->toSchema())->not->toHaveKey('markRequired');
    });
});

describe('plain-text help', function () {
    it('sends the help as text next to an escaped fallback', function () {
        $schema = Text::make('a')->helpText('Use <b> & co')->toSchema();

        expect($schema['helpText'])->toBe('Use <b> & co')
            ->and($schema['help'])->toBe('Use &lt;b&gt; &amp; co');
    });

    it('takes html: false on help()', function () {
        expect(Text::make('a')->help('1 < 2', html: false)->toSchema()['helpText'])->toBe('1 < 2');
    });

    it('keeps rendering help as html by default and drops an earlier plain-text help', function () {
        $schema = Text::make('a')->helpText('old')->help('new')->toSchema();

        expect($schema['help'])->toBe('new')
            ->and($schema)->not->toHaveKey('helpText');
    });
});
