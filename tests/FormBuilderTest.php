<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\FormBuilder;

it('returns an empty string when recaptcha is disabled', function () {
    config()->set('form-builder.recaptcha.enabled', false);
    config()->set('form-builder.recaptcha.site_key', 'site-key');

    expect((new FormBuilder)->recaptchaJs()->toHtml())->toBe('');
});

it('returns an empty string when no site key is set', function () {
    config()->set('form-builder.recaptcha.enabled', true);
    config()->set('form-builder.recaptcha.site_key', '');

    expect((new FormBuilder)->recaptchaJs()->toHtml())->toBe('');
});

it('renders the recaptcha script tag with the site key', function () {
    config()->set('form-builder.recaptcha.enabled', true);
    config()->set('form-builder.recaptcha.site_key', 'site-key-123');

    expect((new FormBuilder)->recaptchaJs()->toHtml())
        ->toBe('<script src="https://www.google.com/recaptcha/api.js?render=site-key-123"></script>');
});
