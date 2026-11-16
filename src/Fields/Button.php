<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

use Illuminate\Support\HtmlString;
use InvalidArgumentException;

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

    protected ?string $labelExpression = null;

    protected ?string $disabledIf = null;

    protected ?string $emit = null;

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

    public function labelExpression(string $expression): static
    {
        $this->labelExpression = $expression;

        return $this;
    }

    public function disabledIf(string $expression): static
    {
        $this->disabledIf = $expression;

        return $this;
    }

    public function emit(string $event): static
    {
        if (trim($event) === '') {
            throw new InvalidArgumentException(sprintf(
                'The button "%s" emits an event without a name. Pass a name, for example ->emit(\'make-rows\').',
                $this->getName() ?? 'button',
            ));
        }

        $this->emit = $event;

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
        if (! is_null($this->emit) && ! is_null($this->deleteUrl)) {
            throw new InvalidArgumentException(sprintf(
                'The button "%s" has a delete URL and emits "%s". A button with a delete URL sends the DELETE request itself and never emits. Drop one of the two.',
                $this->getName() ?? 'button',
                $this->emit,
            ));
        }

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
            'labelExpression' => $this->labelExpression,
            'disabledIf' => $this->disabledIf,
            'emit' => $this->emit,
        ]));
    }
}
