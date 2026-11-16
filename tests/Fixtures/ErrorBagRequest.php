<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;
use TranquilTools\FormBuilder\Concerns\UsesForm;
use TranquilTools\FormBuilder\FormConfig;

class ErrorBagRequest extends FormRequest
{
    use UsesForm;

    protected function form(): FormConfig
    {
        return ErrorBagForm::make();
    }
}
