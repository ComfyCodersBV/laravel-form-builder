<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Commands;

use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use TranquilTools\FormBuilder\Commands\Traits\HasStubs;

#[AsCommand(name: 'make:form-request')]
class FormRequestMakeCommand extends RequestMakeCommand
{
    use HasStubs;

    protected $name = 'make:form-request';

    /**
     * Discards the `make:request` signature inherited from RequestMakeCommand.
     *
     * Laravel names a command from its signature when one is set, and only falls
     * back to `$name` when there is none. Since 13.24 commands are registered
     * lazily under the name in their #[AsCommand] attribute, so an inherited
     * signature makes the instance name itself `make:request` while the lazy map
     * points `make:form-request` at this class — and Symfony then reports the
     * command as registered under multiple names. Arguments and options come from
     * getArguments() and getOptions() instead.
     */
    protected $signature = null;

    protected $type = 'Form Request';

    protected $description = 'Create a new form request class for a FormBuilder Form';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/form-request.stub');
    }

    protected function buildClass($name): string
    {
        $namespaceForm = $this->option('form')
            ? $this->qualifyForm($this->option('form'))
            : $this->qualifyForm($this->guessFormName($name));

        $form = class_basename($namespaceForm);

        $formName = strtolower($form);

        $replace = [
            '{{ namespacedForm }}' => $namespaceForm,
            '{{namespacedForm}}' => $namespaceForm,
            '{{ form }}' => $form,
            '{{form}}' => $form,
            '{{ name }}' => $formName,
            '{{name}}' => $formName,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function qualifyForm(string $form): string
    {
        $form = ltrim($form, '\\/');

        $form = str_replace('/', '\\', $form);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($form, $rootNamespace)) {
            return $form;
        }

        return is_dir(app_path('Forms'))
            ? $rootNamespace.'Forms\\'.$form
            : $rootNamespace.$form;
    }

    protected function guessFormName($name): string
    {
        if (str_ends_with($name, 'FormRequest')) {
            $name = substr(class_basename($name), 0, -11);
        }

        $formName = $this->qualifyForm(Str::after($name, $this->rootNamespace()));

        if (class_exists($formName)) {
            return $formName;
        }

        if (class_exists($formName.'Form')) {
            return $formName.'Form';
        }

        if (is_dir(app_path('Forms/'))) {
            return $this->rootNamespace().'Forms\Form';
        }

        return $this->rootNamespace().'Form';
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['form', 'F', InputOption::VALUE_OPTIONAL, 'The name of the form'],
        ]);
    }
}
