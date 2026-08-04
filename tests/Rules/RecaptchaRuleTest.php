<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TranquilTools\FormBuilder\Rules\RecaptchaRule;

function runRecaptcha(RecaptchaRule $rule, mixed $value): ?string
{
    $message = null;

    $rule->validate('g-recaptcha-response', $value, function (string $failMessage) use (&$message) {
        $message = $failMessage;
    });

    return $message;
}

beforeEach(function () {
    config()->set('form-builder.recaptcha.enabled', true);
    config()->set('form-builder.recaptcha.secret_key', 'test-secret');
});

it('passes silently when recaptcha is disabled', function () {
    config()->set('form-builder.recaptcha.enabled', false);
    Http::fake();

    expect(runRecaptcha(new RecaptchaRule, ''))->toBeNull();
    Http::assertNothingSent();
});

it('fails when no secret key is configured', function () {
    config()->set('form-builder.recaptcha.secret_key', '');

    expect(runRecaptcha(new RecaptchaRule, 'token'))
        ->toBe(trans('form-builder::recaptcha.not-configured'));
});

it('fails on an empty token', function () {
    expect(runRecaptcha(new RecaptchaRule, ''))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});

it('fails when the verification request is not successful', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    expect(runRecaptcha(new RecaptchaRule, 'token'))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});

it('fails when google reports success false', function () {
    Http::fake(['*' => Http::response(['success' => false], 200)]);

    expect(runRecaptcha(new RecaptchaRule, 'token'))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});

it('fails when the action does not match', function () {
    Http::fake(['*' => Http::response(['success' => true, 'action' => 'other', 'score' => 0.9], 200)]);

    expect(runRecaptcha(new RecaptchaRule('submit', 0.5), 'token'))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});

it('fails when the score is below the threshold', function () {
    Http::fake(['*' => Http::response(['success' => true, 'action' => 'submit', 'score' => 0.1], 200)]);

    expect(runRecaptcha(new RecaptchaRule('submit', 0.5), 'token'))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});

it('passes when success, action and score are valid', function () {
    Http::fake(['*' => Http::response(['success' => true, 'action' => 'submit', 'score' => 0.9], 200)]);

    expect(runRecaptcha(new RecaptchaRule('submit', 0.5), 'token'))->toBeNull();
});

it('fails when the http client throws', function () {
    Http::fake(function () {
        throw new RuntimeException('network down');
    });

    expect(runRecaptcha(new RecaptchaRule, 'token'))
        ->toBe(trans('form-builder::recaptcha.validation-failed'));
});
