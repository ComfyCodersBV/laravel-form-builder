<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder;

use Illuminate\Http\Request;

/**
 * @phpstan-consistent-constructor
 */
abstract class AbstractForm
{
    protected ?FormConfig $config = null;

    public function fields(): array
    {
        return [];
    }

    public static function make(...$arguments): FormConfig
    {
        $form = new static(...$arguments);

        return $form->build();
    }

    public function build(): FormConfig
    {
        if ($this->config) {
            return $this->config;
        }

        return $this->config = tap(
            FormConfig::make($this->fields()),
            function (FormConfig $form) {
                $form->setConfigurator($this);
                $this->configure($form);
            }
        );
    }

    public function configure(FormConfig $form)
    {
        //
    }

    public static function rules(...$arguments): array
    {
        return self::make(...$arguments)->getRules();
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(...$arguments): array
    {
        return self::make(...$arguments)->getAttributes();
    }

    public function validate(?Request $request = null, ...$params): array
    {
        $request = $request ?? request();

        return $this->build()->validate($request, ...$params);
    }
}
