<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TranquilTools\FormBuilder\Commands\FormMakeCommand;
use TranquilTools\FormBuilder\Commands\FormRequestMakeCommand;
use TranquilTools\FormBuilder\Rules\RecaptchaRule;

class FormBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-form-builder')
            ->hasConfigFile('form-builder')
            ->hasTranslations()
            ->hasCommands([
                FormMakeCommand::class,
                FormRequestMakeCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->mergeLegacyConfiguration();
    }

    public function packageBooted(): void
    {
        Lang::addNamespace('vue-form-builder', __DIR__.'/../resources/lang');

        Validator::extend('recaptcha', function ($attribute, $value, $parameters) {
            $action = $parameters[0] ?? null;
            $minScore = isset($parameters[1]) ? (float) $parameters[1] : null;

            return is_null((new RecaptchaRule($action, $minScore))->failureMessage($value));
        }, trans('form-builder::recaptcha.validation-failed'));
    }

    /**
     * Honours a `config/vue-form-builder.php` published before the PHP core was
     * split out of the Vue renderer.
     *
     * Values from the legacy file win, because a published file is a deliberate
     * choice by the application, while `form-builder.php` may be nothing more
     * than this package's untouched defaults.
     */
    private function mergeLegacyConfiguration(): void
    {
        $legacy = config('vue-form-builder');

        if (! is_array($legacy) || $legacy === []) {
            return;
        }

        config()->set('form-builder', array_replace_recursive(
            config('form-builder', []),
            $legacy,
        ));

        if (! $this->app->hasDebugModeEnabled()) {
            return;
        }

        $this->app->make('log')->warning(
            'config/vue-form-builder.php is deprecated: laravel-form-builder reads config/form-builder.php. '
            .'Rename the published file and its keys; the old name keeps working for now.'
        );
    }
}
