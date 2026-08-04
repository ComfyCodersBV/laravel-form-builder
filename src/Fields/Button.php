<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Button extends BaseField
{
    protected string $type = 'button';

    protected ?string $cancelLabel = null;

    protected ?string $confirmTitle = null;

    protected ?string $confirmMessage = null;

    protected ?string $deleteUrl = null;

    protected ?string $variant = null;

    public function cancelLabel(string $label): static
    {
        $this->cancelLabel = $label;

        return $this;
    }

    public function confirmTitle(string $title): static
    {
        $this->confirmTitle = $title;

        return $this;
    }

    public function confirmMessage(string $message): static
    {
        $this->confirmMessage = $message;

        return $this;
    }

    public function deleteUrl(string $url): static
    {
        $this->deleteUrl = $url;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function toSchema(): array
    {
        $schema = parent::toSchema();

        if (! is_null($this->confirmTitle) || ! is_null($this->confirmMessage)) {
            $this->cancelLabel ??= trans('form-builder::buttons.cancel');
        }

        return array_merge($schema, array_filter([
            'cancelLabel' => $this->cancelLabel,
            'confirmTitle' => $this->confirmTitle,
            'confirmMessage' => $this->confirmMessage,
            'deleteUrl' => $this->deleteUrl,
            'variant' => $this->variant,
        ]));
    }
}
