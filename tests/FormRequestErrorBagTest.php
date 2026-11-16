<?php

declare(strict_types=1);

use App\Http\Requests\NewsletterFormRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use TranquilTools\FormBuilder\Tests\Fixtures\ErrorBagForm;
use TranquilTools\FormBuilder\Tests\Fixtures\ErrorBagRequest;
use TranquilTools\FormBuilder\Tests\Fixtures\RequestUsingForm;

afterEach(function () {
    File::delete(app_path('Http/Requests/NewsletterFormRequest.php'));
});

it('gives the error bag of a form class statically', function () {
    expect(ErrorBagForm::errorBag())->toBe('newsletter');
});

it('puts the errors of a request using a form in the bag of that form', function () {
    Route::middleware('web')->post('/newsletter', fn (ErrorBagRequest $request) => 'ok');

    $this->post('/newsletter', ['email' => 'nope'])
        ->assertSessionHasErrorsIn('newsletter', ['email'])
        ->assertSessionDoesntHaveErrors(['email']);
});

it('keeps the errors of a request using a form without a bag in the default bag', function () {
    Route::middleware('web')->post('/title', fn (RequestUsingForm $request) => 'ok');

    $this->post('/title', [])->assertSessionHasErrors(['title']);
});

it('generates a form request that uses the error bag of its form', function () {
    $this->artisan('make:form-request', [
        'name' => 'NewsletterFormRequest',
        '--form' => 'App\\Forms\\ErrorBagForm',
    ])->assertSuccessful();

    class_alias(ErrorBagForm::class, 'App\\Forms\\ErrorBagForm');

    require_once app_path('Http/Requests/NewsletterFormRequest.php');

    Route::middleware('web')->post('/generated', fn (NewsletterFormRequest $request) => 'ok');

    $this->post('/generated', ['email' => 'nope'])
        ->assertSessionHasErrorsIn('newsletter', ['email'])
        ->assertSessionDoesntHaveErrors(['email']);
});
