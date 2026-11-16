<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\Fields\Checkbox;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Repeater;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Step;
use TranquilTools\FormBuilder\Fields\Tags;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;
use TranquilTools\FormBuilder\Tests\Fixtures\ConditionalRequest;

function validateWith(FormConfig $config, array $input): array
{
    return $config->validate(Request::create('/', 'POST', $input));
}

function actionForm(array $fields): FormConfig
{
    return FormConfig::make([
        Radio::make('action')
            ->options([
                'rebook' => 'Rebook',
                'return' => 'Return',
                'split' => 'Split',
            ])
            ->rules(['required']),
        ...$fields,
    ]);
}

it('sends the condition to the renderer as a field and values, with an expression for older renderers', function () {
    $schema = Text::make('destination')->if('action', ['rebook', 'split'])->toSchema();

    expect($schema['when'])->toBe([
        'field' => 'action',
        'values' => ['rebook', 'split'],
    ])
        ->and($schema['condition'])->toBe('!Array.isArray(form["action"]) && ["rebook","split"].includes(String(form["action"] === \'\' ? null : (form["action"] ?? null)))');
});

it('leaves a field out of validation and the validated data while its condition does not hold', function () {
    $config = actionForm([
        Text::make('destination')->if('action', 'rebook')->rules(['required', 'string']),
    ]);

    expect(validateWith($config, ['action' => 'return', 'destination' => 'stale']))->toBe(['action' => 'return']);
});

it('validates the field while its condition holds', function () {
    $config = actionForm([
        Text::make('destination')->if('action', 'rebook')->rules(['required', 'string']),
    ]);

    expect(fn () => validateWith($config, ['action' => 'rebook']))->toThrow(ValidationException::class)
        ->and(validateWith($config, ['action' => 'rebook', 'destination' => 'Lyon']))->toBe([
            'action' => 'rebook',
            'destination' => 'Lyon',
        ]);
});

it('holds for any of several values', function () {
    $config = actionForm([
        Text::make('destination')->if('action', ['rebook', 'split'])->rules(['required']),
    ]);

    expect(fn () => validateWith($config, ['action' => 'split']))->toThrow(ValidationException::class)
        ->and(validateWith($config, ['action' => 'return']))->toBe(['action' => 'return']);
});

it('matches a value that contains a comma', function () {
    $config = FormConfig::make([
        Text::make('city')->rules(['required']),
        Text::make('district')->if('city', 'Paris, France')->rules(['required']),
    ]);

    expect(fn () => validateWith($config, ['city' => 'Paris, France']))->toThrow(ValidationException::class)
        ->and(validateWith($config, ['city' => 'Paris']))->toBe(['city' => 'Paris']);
});

it('matches a checkbox by true and false, whether it sends 1 and 0 or booleans', function (mixed $sent, bool $validated) {
    $config = FormConfig::make([
        Checkbox::make('extra'),
        Text::make('extra_number')->if('extra', true)->rules(['required']),
    ]);

    $attempt = fn () => validateWith($config, ['extra' => $sent]);

    if ($validated) {
        expect($attempt)->toThrow(ValidationException::class);

        return;
    }

    expect($attempt())->toBe(['extra' => $sent]);
})->with([
    'checked as 1' => ['1', true],
    'checked as true' => [true, true],
    'unchecked as 0' => ['0', false],
    'unchecked as false' => [false, false],
]);

it('keeps validating a field whose condition is a JavaScript expression', function () {
    $config = actionForm([
        Text::make('destination')->if("action === 'rebook'")->rules(['required']),
    ]);

    expect($config->getRules()['destination'])->toBe(['required'])
        ->and(fn () => validateWith($config, ['action' => 'return']))->toThrow(ValidationException::class);
});

it('reads the condition of a repeater sub-field from the same row', function () {
    $config = FormConfig::make([
        Repeater::make('barrels')->fields([
            Text::make('kind')->rules(['required']),
            Text::make('weight')->if('kind', 'full')->rules(['required', 'numeric']),
        ]),
    ]);

    expect($config->getRules()['barrels.*.weight'])->toBe(['exclude_unless:barrels.*.kind,full', 'required', 'numeric']);

    try {
        validateWith($config, ['barrels' => [
            ['kind' => 'empty'],
            ['kind' => 'full'],
        ]]);
        $this->fail('The full barrel without a weight passed.');
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toBe(['barrels.1.weight']);
    }

    expect(validateWith($config, ['barrels' => [
        ['kind' => 'empty', 'weight' => 'stale'],
        ['kind' => 'full', 'weight' => '12'],
    ]]))->toBe(['barrels' => [
        ['kind' => 'empty'],
        ['kind' => 'full', 'weight' => '12'],
    ]]);
});

it('scopes the condition to the row of a nested repeater', function () {
    $config = FormConfig::make([
        Repeater::make('lots')->fields([
            Repeater::make('barrels')->fields([
                Text::make('kind'),
                Text::make('weight')->if('kind', 'full')->rules(['required']),
            ]),
        ]),
    ]);

    expect($config->getRules()['lots.*.barrels.*.weight'])->toBe(['exclude_unless:lots.*.barrels.*.kind,full', 'required']);

    try {
        validateWith($config, ['lots' => [
            ['barrels' => [['kind' => 'empty']]],
            ['barrels' => [['kind' => 'empty'], ['kind' => 'full']]],
        ]]);
        $this->fail('The full barrel without a weight passed.');
    } catch (ValidationException $exception) {
        expect(array_keys($exception->errors()))->toBe(['lots.1.barrels.1.weight']);
    }
});

it('leaves every field of a hidden section or step out, nested rules included', function (string $container) {
    $config = actionForm([
        $container::make()->if('action', 'rebook')->fields([
            Text::make('destination')->rules(['required']),
            Tags::make('labels')->rules(['required']),
        ]),
    ]);

    expect(validateWith($config, ['action' => 'return', 'labels' => [1, 2]]))->toBe(['action' => 'return'])
        ->and(fn () => validateWith($config, ['action' => 'rebook']))->toThrow(ValidationException::class);
})->with([
    'section' => [Section::class],
    'step' => [Step::class],
]);

it('combines the condition of a section with the condition of its field', function () {
    $config = actionForm([
        Checkbox::make('express'),
        Section::make()->if('action', 'rebook')->fields([
            Text::make('courier')->if('express', true)->rules(['required']),
        ]),
    ]);

    expect(validateWith($config, ['action' => 'rebook', 'express' => '0']))->toBe(['action' => 'rebook', 'express' => '0'])
        ->and(validateWith($config, ['action' => 'return', 'express' => '1']))->toBe(['action' => 'return', 'express' => '1'])
        ->and(fn () => validateWith($config, ['action' => 'rebook', 'express' => '1']))->toThrow(ValidationException::class);
});

it('leaves hidden fields out of a form request that uses the form', function () {
    $rules = (new ConditionalRequest)->rules();

    $validator = Validator::make([
        'action' => 'return',
        'destination' => 'stale',
        'barrels' => [
            ['kind' => 'empty', 'weight' => 'stale'],
        ],
    ], $rules);

    expect($validator->validated())->toBe([
        'action' => 'return',
        'barrels' => [
            ['kind' => 'empty'],
        ],
    ])
        ->and(Validator::make(['action' => 'rebook', 'barrels' => [['kind' => 'full']]], $rules)->errors()->keys())
        ->toBe(['destination', 'barrels.0.weight']);
});

it('refuses a condition on a field without a name', function () {
    Text::make('destination')->if('', 'rebook');
})->throws(InvalidArgumentException::class);
