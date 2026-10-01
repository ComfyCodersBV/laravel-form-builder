<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use TranquilTools\FormBuilder\Commands\Traits\HasStubs;

use function Laravel\Prompts\select;

#[AsCommand(name: 'make:form')]
class FormMakeCommand extends GeneratorCommand
{
    use HasStubs;

    protected $name = 'make:form';

    protected $type = 'Form';

    protected $description = 'Create a new form class';

    public function handle(): int
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return self::FAILURE;
        }

        if (
            $this->option('request')
            || select('Do you want to create a FormRequest for this form?', [
                'no' => 'No',
                'yes' => 'Yes',
            ], default: 'yes') === 'yes'
        ) {
            $this->createRequest();
        }

        return self::SUCCESS;
    }

    protected function createRequest(): void
    {
        $form = Str::studly($this->argument('name'));

        $this->call('make:form-request', [
            'name' => $form.'Request',
            '--form' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/form.stub');
    }

    protected function buildClass($name): array|string
    {
        $formName = strtolower(class_basename($name));

        $replace = [
            '{{ name }}' => $formName,
            '{{name}}' => $formName,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Forms';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the class already exists'],
            ['request', 'r', InputOption::VALUE_NONE, 'Create a FormRequest for the form'],
        ];
    }
}
