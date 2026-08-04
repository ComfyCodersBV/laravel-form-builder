<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder;

use Illuminate\Support\HtmlString;

class FormBuilder
{
    public function recaptchaJs(): HtmlString
    {
        if (
            ! config('form-builder.recaptcha.enabled', true)
            || empty(config('form-builder.recaptcha.site_key'))
        ) {
            return new HtmlString('');
        }

        $siteKey = config('form-builder.recaptcha.site_key');

        return new HtmlString(
            sprintf('<script src="https://www.google.com/recaptcha/api.js?render=%s"></script>', $siteKey)
        );
    }
}
