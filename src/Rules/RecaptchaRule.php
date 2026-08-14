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
        $message = $this->failureMessage($value);

        if (is_null($message)) {
            return;
        }

        $fail($message);
    }

    /**
     * The reason this value is unacceptable, or null when it is fine.
     *
     * The decision lives here rather than in validate() so that the `recaptcha`
     * validator registered on the Validator facade can reuse it. That extension
     * has to answer with a boolean and has no $fail closure to hand over, and
     * faking one would mean lying about the callable ValidationRule expects.
     */
    public function failureMessage(mixed $value): ?string
    {
        if (! config('form-builder.recaptcha.enabled', true)) {
            return null;
        }

        if (empty(config('form-builder.recaptcha.secret_key'))) {
            return $this->translate('not-configured');
        }

        if (empty($value)) {
            return $this->translate('validation-failed');
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('form-builder.recaptcha.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);

            if (! $response->successful()) {
                return $this->translate('validation-failed');
            }

            $data = $response->json();

            if (! $data['success']) {
                return $this->translate('validation-failed');
            }

            if ($this->action && ($data['action'] ?? '') !== $this->action) {
                return $this->translate('validation-failed');
            }

            $score = $data['score'] ?? 0;

            if ($score < $this->minScore) {
                return $this->translate('validation-failed');
            }
        } catch (Exception $e) {
            return $this->translate('validation-failed');
        }

        return null;
    }

    private function translate(string $key): string
    {
        $message = trans("form-builder::recaptcha.{$key}");

        return is_string($message) ? $message : $key;
    }
}
