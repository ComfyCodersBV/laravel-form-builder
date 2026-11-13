<?php

declare(strict_types=1);

use TranquilTools\FormBuilder\Tests\Fixtures\RequestBackedForm;
use TranquilTools\FormBuilder\Tests\Fixtures\RequestUsingForm;

it('returns the rules and attributes of one built form', function () {
    RequestBackedForm::$builds = 0;
    $request = new RequestUsingForm;

    expect($request->rules())->toBe(['title' => ['required', 'string']])
        ->and($request->attributes())->toBe(['title' => 'Title'])
        ->and(RequestBackedForm::$builds)->toBe(1);
});
