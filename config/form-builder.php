<?php

declare(strict_types=1);

// config for TranquilTools/FormBuilder
return [

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | Classes for the wrapper, label, help text and error message that every
    | field renders around its own control. These travel in the schema, so any
    | renderer inherits them.
    |
    | A key left null keeps the renderer's own default. The Vue renderer uses:
    |
    |   'wrapper' => 'space-y-1'
    |   'label'   => 'block text-sm font-medium text-neutral-800 dark:text-neutral-200'
    |   'help'    => 'mt-1 text-xs text-neutral-500 dark:text-neutral-400'
    |   'error'   => 'text-sm text-red-600'
    |
    | Override a single field instead with ->theme(['label' => '...']).
    |
    */
    'theme' => [
        'wrapper' => null,
        'label' => null,
        'help' => null,
        'error' => null,
    ],

    'wysiwyg' => [

        /*
        |--------------------------------------------------------------------------
        | Default WYSIWYG editor
        |--------------------------------------------------------------------------
        |
        | This is the default editor that will be used when implementing the
        | TranquilTools\FormBuilder\Fields\Wysiwyg => =>make('content')
        | component without specifying a different ->editor('...')
        |
        */
        'default-editor' => 'quill',

        /*
        |--------------------------------------------------------------------------
        | Editor configs
        |--------------------------------------------------------------------------
        |
        | Editor-specific configurations
        |
        */
        'editors' => [

            'quill' => [
                'options' => [
                    'theme' => 'snow',
                    'showSourceButton' => true,
                    'modules' => [
                        'toolbar' => [
                            [['font' => []], ['size' => []]],
                            ['bold', 'italic', 'underline', 'strike'],
                            [['color' => []], ['background' => []]],
                            [['script' => 'super'], ['script' => 'sub']],
                            [['header' => '1'], ['header' => '2'], 'blockquote', 'code-block'],
                            [['list' => 'ordered'], ['list' => 'bullet'], ['indent' => '-1'], ['indent' => '+1']],
                            ['direction', ['align' => []]],
                            ['link', 'image', 'video', 'formula'],
                            ['clean'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'key_value' => [

        /*
        |--------------------------------------------------------------------------
        | Masked value key pattern
        |--------------------------------------------------------------------------
        |
        | Rows in a KeyValue field whose key matches this pattern (case-insensitive,
        | no delimiters — used as both a PHP and a JS regex source) render their
        | value input as masked (type="password") with a reveal toggle.
        | Override per-field with ->maskedKeyPattern('...').
        |
        */
        'masked_key_pattern' => 'password|secret|token',
    ],

    /*
    |--------------------------------------------------------------------------
    | (Optional) reCAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Configure reCAPTCHA v3 settings
    |
    */

    'recaptcha' => [
        /*
         * The reCAPTCHA site key (public key)
         */
        'site_key' => env('RECAPTCHA_SITE_KEY', ''),

        /*
         * The reCAPTCHA secret key (private key)
         */
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

        /*
         * Default action name for reCAPTCHA verification
         */
        'default_action' => 'submit',

        /*
         * Default minimum score threshold (0.0 to 1.0)
         * 1.0 is very likely a good interaction, 0.0 is very likely a bot
         */
        'default_score' => 0.5,

        /*
         * Enable or disable reCAPTCHA verification
         */
        'enabled' => env('RECAPTCHA_ENABLED', true),
    ],
];
