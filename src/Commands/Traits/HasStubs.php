<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Commands\Traits;

trait HasStubs
{
    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : dirname(__FILE__, 4).$stub;
    }
}
