<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\HtmlString;

class Button extends BaseField
{
    protected string $type = 'button';

    protected ?string $cancelLabel = null;

    protected ?string $confirmTitle = null;

    protected ?string $confirmMessage = null;

    protected ?string $deleteUrl = null;

    protected ?string $variant = null;

    protected ?string $labelHtml = null;

    protected ?string $ariaLabel = null;

    public function label(string|HtmlString $label): static
    {
        if ($label instanceof HtmlString) {
            $this->labelHtml = $label->toHtml();
            $text = trim(strip_tags($this->labelHtml));
            $this->label = $text === '' ? null : $text;

            return $this;
        }

        $this->labelHtml = null;

        return parent::label($label);
    }

    public function ariaLabel(string $label): static
    {
        $this->ariaLabel = $label;

        return $this;
    }

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
            'labelHtml' => $this->labelHtml,
            'ariaLabel' => $this->ariaLabel,
        ]));
    }
}
