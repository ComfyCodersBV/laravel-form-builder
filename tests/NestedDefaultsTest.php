<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use TranquilTools\FormBuilder\Fields\Section;
use TranquilTools\FormBuilder\Fields\Select;
use TranquilTools\FormBuilder\Fields\Step;
use TranquilTools\FormBuilder\Fields\Text;
use TranquilTools\FormBuilder\FormConfig;

function serverModel(array $attributes): Model
{
    $model = new class extends Model
    {
        protected $guarded = [];

        protected $hidden = [
            'secret',
        ];

        protected $casts = [
            'database_credentials' => 'array',
            'secret' => 'array',
        ];
    };

    return $model->fill($attributes);
}

it('fills dot-notated fields from an array cast on a model', function () {
    $model = serverModel([
        'name' => 'web-1',
        'database_credentials' => [
            'driver' => 'mysql',
            'host' => 'db.internal',
        ],
    ]);

    $defaults = FormConfig::make([
        Text::make('name'),
        Select::make('database_credentials.driver'),
        Text::make('database_credentials.host'),
    ])->fill($model)->jsonSerialize()['defaults'];

    expect($defaults)
        ->toMatchArray([
            'name' => 'web-1',
            'database_credentials.driver' => 'mysql',
            'database_credentials.host' => 'db.internal',
        ])
        ->and($defaults['database_credentials'])->toBe([
            'driver' => 'mysql',
            'host' => 'db.internal',
        ]);
});

it('fills dot-notated fields from nested array data', function () {
    $defaults = FormConfig::make([
        Select::make('database_credentials.driver'),
    ])->fill([
        'database_credentials' => [
            'driver' => 'pgsql',
        ],
    ])->jsonSerialize()['defaults'];

    expect($defaults['database_credentials.driver'])->toBe('pgsql');
});

it('fills dot-notated fields inside sections and steps', function () {
    $defaults = FormConfig::make([
        Section::make()->fields([
            Select::make('database_credentials.driver'),
        ]),
        Step::make()->fields([
            Text::make('database_credentials.host'),
        ]),
    ])->fill([
        'database_credentials' => [
            'driver' => 'mysql',
            'host' => 'db.internal',
        ],
    ])->jsonSerialize()['defaults'];

    expect($defaults)->toMatchArray([
        'database_credentials.driver' => 'mysql',
        'database_credentials.host' => 'db.internal',
    ]);
});

it('keeps a literal dotted key over the nested value', function () {
    $defaults = FormConfig::make([
        Select::make('database_credentials.driver'),
    ])->fill([
        'database_credentials.driver' => 'sqlite',
        'database_credentials' => [
            'driver' => 'mysql',
        ],
    ])->jsonSerialize()['defaults'];

    expect($defaults['database_credentials.driver'])->toBe('sqlite');
});

it('adds nothing for a nested value that is missing', function () {
    $defaults = FormConfig::make([
        Select::make('database_credentials.driver'),
        Text::make('database_credentials.port'),
    ])->fill([
        'database_credentials' => [
            'driver' => 'mysql',
        ],
    ])->jsonSerialize()['defaults'];

    expect($defaults)->toBe([
        'database_credentials' => [
            'driver' => 'mysql',
        ],
        'database_credentials.driver' => 'mysql',
    ]);
});

it('leaves a model without nested values to serialize as itself', function () {
    $model = serverModel([
        'name' => 'web-1',
    ]);

    $defaults = FormConfig::make([
        Text::make('name'),
        Select::make('database_credentials.driver'),
    ])->fill($model)->jsonSerialize()['defaults'];

    expect($defaults)->toBe($model);
});

it('never fills a dot-notated field from a hidden attribute', function () {
    $model = serverModel([
        'secret' => [
            'token' => 'abc',
        ],
    ]);

    $defaults = json_decode(json_encode(FormConfig::make([
        Text::make('secret.token'),
    ])->fill($model)), true)['defaults'];

    expect($defaults)->toBe([]);
});

it('lets the query string win over a nested value on a GET form', function () {
    app()->instance('request', Request::create('/servers', 'GET', ['region' => 'eu']));

    $defaults = FormConfig::make([
        Text::make('region'),
        Select::make('database_credentials.driver'),
    ])->method('get')->fill([
        'region' => 'us',
        'database_credentials' => [
            'driver' => 'mysql',
        ],
    ])->jsonSerialize()['defaults'];

    expect($defaults)->toMatchArray([
        'region' => 'eu',
        'database_credentials.driver' => 'mysql',
    ]);
});
