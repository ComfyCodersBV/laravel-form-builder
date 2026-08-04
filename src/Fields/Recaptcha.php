<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Recaptcha extends BaseField
{
    protected string $type = 'recaptcha';

    protected ?string $action = null;

    protected ?string $siteKey = null;

    protected ?float $minScore = null;

    public static function make(?string $name = 'g-recaptcha-response'): static
    {
        $instance = parent::make($name);
        $instance->siteKey = config('form-builder.recaptcha.site_key');
        $instance->action = config('form-builder.recaptcha.default_action', 'submit');
        $instance->minScore = config('form-builder.recaptcha.default_score', 0.5);

        return $instance;
    }

    public function action(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function minScore(float $minScore): static
    {
        $this->minScore = $minScore;

        return $this;
    }

    public function siteKey(string $siteKey): static
    {
        $this->siteKey = $siteKey;

        return $this;
    }

    protected function getValidationRule(): string
    {
        return sprintf('recaptcha:%s,%s', $this->action, $this->minScore);
    }

    public function getRules(): array
    {
        return [$this->getValidationRule()];
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();

        $schema['action'] = $this->action;
        $schema['siteKey'] = $this->siteKey;

        return $schema;
    }
}
