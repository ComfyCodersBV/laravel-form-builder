<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonSerializable;
use TranquilTools\FormBuilder\Fields\BaseField;
use TranquilTools\FormBuilder\Fields\Radio;
use TranquilTools\FormBuilder\Fields\Section;

/**
 * @phpstan-consistent-constructor
 */
class FormConfig implements JsonSerializable
{
    /**
     * The contract between this package and whichever renderer consumes its schema.
     *
     * Bump the major when a renderer that does not know about the change would
     * misrender a form — a new field type, a renamed key, changed semantics for
     * an existing one. Bump the minor for additions a renderer may ignore safely.
     * Renderers compare their supported version against this and refuse to render
     * silently wrong output.
     */
    public const SCHEMA_VERSION = '1.7';

    protected ?AbstractForm $configurator = null;

    protected array|Model $data = [];

    /** @var BaseField[] */
    protected array $fields = [];

    protected array $options = [];

    protected array|string $class = [];

    protected string $id = '';

    protected string $action = '';

    protected string $method = 'POST';

    protected ?string $locale = null;

    protected bool $resetOnSuccess = false;

    protected bool $submitOnChange = false;

    protected ?bool $fillFromQuery = null;

    protected ?int $columns = null;

    protected ?bool $labelsAsAttributes = null;

    public function __construct(array $fields = [])
    {
        $this->fields($fields);
    }

    public static function make(array $fields = []): static
    {
        return new static($fields);
    }

    public function setConfigurator(AbstractForm $configurator): static
    {
        $this->configurator = $configurator;

        return $this;
    }

    public function id(string $id = ''): static
    {
        $this->id = $id;

        return $this;
    }

    public function action(string $action = 'POST'): static
    {
        $this->action = $action;

        return $this;
    }

    public function class(array|string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function fill($data): static
    {
        $this->data = $data;

        return $this;
    }

    public function fields(array $fields = []): static
    {
        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    public function method(string $method = 'POST'): static
    {
        $this->method = $method;

        return $this;
    }

    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function resetOnSuccess(bool $reset = true): static
    {
        $this->resetOnSuccess = $reset;

        return $this;
    }

    public function submitOnChange(bool $submit = true): static
    {
        $this->submitOnChange = $submit;

        return $this;
    }

    public function fillFromQuery(bool $fill = true): static
    {
        $this->fillFromQuery = $fill;

        return $this;
    }

    public function columns(?int $columns): static
    {
        $this->columns = is_null($columns) ? null : max(1, min(Section::MAX_COLUMNS, $columns));

        return $this;
    }

    public function labelsAsAttributes(bool $labels = true): static
    {
        $this->labelsAsAttributes = $labels;

        return $this;
    }

    public function getColumns(): ?int
    {
        return $this->columns;
    }

    public function usesLabelsAsAttributes(): bool
    {
        return $this->labelsAsAttributes ?? (bool) config('form-builder.validation.label_attributes', false);
    }

    public function getLocale(): string
    {
        return $this->locale ?? app()->getLocale();
    }

    public function getId(): string
    {
        if ($this->id !== '') {
            return $this->id;
        }

        return Str::snake(class_basename($this->configurator ?? ''));
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getClass(): array|string
    {
        return $this->class;
    }

    public function getData(): Model|array
    {
        return $this->data;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOption(string $option): array|bool|string|null
    {
        return $this->options[$option] ?? false;
    }

    public function jsonSerialize(): array
    {
        $schemas = array_map(fn ($field) => $field->toSchema(), $this->getFields());
        $schemas = Radio::normalize($schemas);

        $defaults = $this->defaults();
        if ($defaults === []) {
            $defaults = (object) [];
        }

        $payload = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'id' => $this->getId(),
            'action' => $this->getAction(),
            'defaults' => $defaults,
            'fields' => $schemas,
            'formClass' => $this->getClass(),
            'method' => $this->getMethod(),
            'theme' => $this->getTheme(),
            'locale' => $this->getLocale(),
            'resetOnSuccess' => $this->resetOnSuccess,
            'submitOnChange' => $this->submitOnChange,
        ];

        if (is_null($this->columns)) {
            return $payload;
        }

        return [
            ...$payload,
            'columns' => $this->columns,
        ];
    }

    /**
     * @return Model|array<string, mixed>
     */
    protected function defaults(): Model|array
    {
        $data = $this->getData();

        if (! $this->fillsFromQuery()) {
            return $data;
        }

        $query = request()->query();

        $values = [];

        foreach ($this->valueFields($this->getFields()) as $field) {
            $name = $field->getName();

            if (! is_null($name) && array_key_exists($name, $query)) {
                $values[$name] = $query[$name];
            }
        }

        if ($values === []) {
            return $data;
        }

        return [
            ...($data instanceof Model ? $data->toArray() : $data),
            ...$values,
        ];
    }

    protected function fillsFromQuery(): bool
    {
        return $this->fillFromQuery ?? strtoupper($this->getMethod()) === 'GET';
    }

    /**
     * The form-wide theme, with unset keys dropped so a renderer keeps its own
     * default for those rather than being handed a null to interpret.
     *
     * @return array<string, string>
     */
    protected function getTheme(): array
    {
        $theme = config('form-builder.theme', []);

        if (! is_array($theme)) {
            return [];
        }

        return array_filter($theme, fn ($classes) => is_string($classes) && $classes !== '');
    }

    public function getRules(): array
    {
        return array_reduce(
            $this->getFields(),
            function (array $carry, BaseField $field): array {
                $name = $field->getName();
                if ($name !== null && $name !== '' && ! $field instanceof Section) {
                    $carry[$name] = $field->getRules();
                }

                if (method_exists($field, 'nestedRules')) {
                    foreach ($field->nestedRules() as $ruleName => $rules) {
                        $carry[$ruleName] = $rules;
                    }
                }

                return $carry;
            },
            []
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        $attributes = [];

        foreach ($this->valueFields($this->getFields()) as $field) {
            $attributes = [
                ...$attributes,
                ...$this->labelAttribute($field),
                ...$field->validationAttributes(),
            ];
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    protected function labelAttribute(BaseField $field): array
    {
        if (! $this->usesLabelsAsAttributes()) {
            return [];
        }

        $name = $field->getName();
        $label = $field->validationLabel();

        if (is_null($name) || $name === '' || is_null($label) || $label === '') {
            return [];
        }

        return [
            $name => $label,
        ];
    }

    public function validate(?Request $request = null, ...$params): array
    {
        $request = $request ?? request();

        $messages = $params['messages'] ?? $params[0] ?? [];
        $attributes = $params['attributes'] ?? $params[1] ?? [];

        return $this->normalizeValues($request->validate($this->getRules(), $messages, [
            ...$this->getAttributes(),
            ...$attributes,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeValues(array $data): array
    {
        foreach ($this->valueFields($this->getFields()) as $field) {
            $name = $field->getName();

            if (! is_null($name) && array_key_exists($name, $data)) {
                $data[$name] = $field->normalizeValue($data[$name]);
            }
        }

        return $data;
    }

    /**
     * @param  array<int, BaseField>  $fields
     * @return list<BaseField>
     */
    protected function valueFields(array $fields): array
    {
        $flattened = [];

        foreach ($fields as $field) {
            if ($field instanceof Section) {
                $flattened = [
                    ...$flattened,
                    ...$this->valueFields($field->getFields()),
                ];

                continue;
            }

            $flattened[] = $field;
        }

        return $flattened;
    }
}
