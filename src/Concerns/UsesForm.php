<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Concerns;

use Illuminate\Contracts\Validation\Validator;
use TranquilTools\FormBuilder\FormConfig;

trait UsesForm
{
    private ?FormConfig $builtForm = null;

    abstract protected function form(): FormConfig;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->builtForm()->getRules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->builtForm()->getAttributes();
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->errorBag = $this->builtForm()->getErrorBag() ?? $this->errorBag;

        parent::failedValidation($validator);
    }

    protected function builtForm(): FormConfig
    {
        return $this->builtForm ??= $this->form();
    }
}
