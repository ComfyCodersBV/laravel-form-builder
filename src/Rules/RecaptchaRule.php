<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaRule implements ValidationRule
{
    public function __construct(
        protected ?string $action = null,
        protected ?float $minScore = null,
    ) {
        $this->action = $action ?? config('form-builder.recaptcha.default_action', 'submit');
        $this->minScore = $minScore ?? config('form-builder.recaptcha.default_score', 0.5);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('form-builder.recaptcha.enabled', true)) {
            return;
        }

        if (empty(config('form-builder.recaptcha.secret_key'))) {
            $fail(trans('form-builder::recaptcha.not-configured'));

            return;
        }

        if (empty($value)) {
            $fail(trans('form-builder::recaptcha.validation-failed'));

            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('form-builder.recaptcha.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (! $response->successful()) {
                $fail(trans('form-builder::recaptcha.validation-failed'));

                return;
            }

            $data = $response->json();

            if (! $data['success']) {
                $fail(trans('form-builder::recaptcha.validation-failed'));

                return;
            }

            if ($this->action && ($data['action'] ?? '') !== $this->action) {
                $fail(trans('form-builder::recaptcha.validation-failed'));

                return;
            }

            $score = $data['score'] ?? 0;
            if ($score < $this->minScore) {
                $fail(trans('form-builder::recaptcha.validation-failed'));

                return;
            }
        } catch (Exception $e) {
            $fail(trans('form-builder::recaptcha.validation-failed'));
        }
    }
}
