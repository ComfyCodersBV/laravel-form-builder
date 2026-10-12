<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use TranquilTools\FormBuilder\AbstractForm;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;
use TranquilTools\FormBuilder\Tests\Fixtures\FormWithRequiredArguments;

class SampleProductForm extends AbstractForm
{
    public function fields(): array
    {
        return [
            Text::make('title')->rules(['required', 'string']),
        ];
    }

    public function configure(FormConfig $form): void
    {
        $form->class('space-y-4');
    }
}

it('builds a FormConfig from a form class', function () {
    expect(SampleProductForm::make())->toBeInstanceOf(FormConfig::class);
});

it('applies configure() when building', function () {
    expect(SampleProductForm::make()->getClass())->toBe('space-y-4');
});

it('derives the form id from the configurator class name', function () {
    expect(SampleProductForm::make()->getId())->toBe('sample_product_form');
});

it('caches the built config instance', function () {
    $form = new SampleProductForm;

    expect($form->build())->toBe($form->build());
});

it('exposes rules statically', function () {
    expect(SampleProductForm::rules())->toBe([
        'title' => ['required', 'string'],
    ]);
});

it('validates a request against the form rules', function () {
    $request = Request::create('/', 'POST', ['title' => 'Hello']);

    expect((new SampleProductForm)->validate($request))->toBe(['title' => 'Hello']);
});

it('throws when the request fails validation', function () {
    $request = Request::create('/', 'POST', []);

    (new SampleProductForm)->validate($request);
})->throws(ValidationException::class);

it('passes constructor arguments through make() and rules()', function () {
    expect(FormWithRequiredArguments::make('title', true)->getRules())->toBe([
        'title' => ['required', 'string'],
    ]);

    expect(FormWithRequiredArguments::rules('summary', false))->toBe([
        'summary' => ['nullable', 'string'],
    ]);
});
