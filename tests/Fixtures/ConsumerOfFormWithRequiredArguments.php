<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Tests\Fixtures;

use TranquilTools\FormBuilder\FormConfig;

class ConsumerOfFormWithRequiredArguments
{
    public function config(): FormConfig
    {
        return FormWithRequiredArguments::make('title', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return FormWithRequiredArguments::rules('title', true);
    }
}
