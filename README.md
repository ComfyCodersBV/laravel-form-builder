# A FormBuilder core package for Laravel

Define Laravel form configurations in PHP: schema, fields, validation and config. This package knows nothing about any
frontend — it produces a framework-agnostic JSON schema that a renderer consumes.

## You probably want the Vue package instead

Unless you are writing a renderer, install
**[tranquil-tools/laravel-vue-form-builder](https://gitlab.comfycoders.nl/tranquil-tools/laravel-vue-form-builder)**. It
requires this package, so you get both:

```bash
composer require tranquil-tools/laravel-vue-form-builder
```

**Full documentation lives at
[docs.comfycoders.nl](https://docs.comfycoders.nl/laravel-vue-form-builder)** — field types, validation, configuration
and Vue usage are documented there. This package ships no `docs/` directory on purpose: one documentation site, one
version stream, no duplication.

## What lives here

|                           |                                                                                            |
|---------------------------|--------------------------------------------------------------------------------------------|
| `src/`                    | `AbstractForm`, `FormConfig`, every field type (also `Step` for multi-step forms), validation rules, the `make:form` commands |
| `config/form-builder.php` | theme, required indicator, date display format and timezone, WYSIWYG defaults, reCAPTCHA and key-value settings |
| `resources/lang/`         | translations used by PHP-side field labels                                                 |
| `stubs/`                  | templates for the generator commands                                                       |

The boundary rule: **what produces schema belongs here, what reads schema belongs in a renderer.**

## The schema contract

`FormConfig::SCHEMA_VERSION` is emitted as `schemaVersion` in every payload. A renderer compares it against the version
it supports and refuses to render rather than silently dropping a field type it does not recognise. Bump the major for
changes that would misrender in an older renderer, the minor for additions that can be ignored safely.

## Event listeners come from Vue

The schema is JSON, so a form class cannot carry a Vue listener. `->attributes()` throws an `InvalidArgumentException`
for keys starting with `@`, `:`, `v-on:` or `v-bind:`, instead of letting the renderer break the page. Attach handlers
in the renderer, in Vue through `fieldOverrides`:

```vue
<Form :schema="form" :field-overrides="{ preview: { onClick: () => (previewOpen = true) } }" />
```

A `Button` with `->emit('preview')` sends `emit`, so the renderer reports the click as a named event instead (in Vue:
`@button` on `<Form>`) and the form class shows the wiring.

## Conditions that validate

`->if('action', 'rebook')` (one value or a list) sends `when: {field, values}` for the renderer, plus an equivalent
`condition` expression for older renderers, and puts `exclude_unless:action,rebook` in front of the field's rules in
`getRules()`, `validate()` and `UsesForm`. On a `Section` or `Step` the rule covers every field inside it, and inside a
`Repeater` the other field is a sub-field of the same row (`exclude_unless:barrels.*.kind,full`). A field that depends
on a hidden field is hidden too: the chain goes to `when.also` and into the rules. `->if('expression')` keeps working
and adds no rule.

## Reserved field names

The renderer keeps every value on Inertia's `useForm()` object, next to its own properties and methods. Serializing a
form throws an `InvalidArgumentException` for a field (or remove flag) named after one of them, such as `data`,
`errors`, `reset`, `query` or `transform`, instead of letting the field show and send that property. The list is
`FormConfig::RESERVED_FIELD_NAMES`. Dotted names and repeater sub-fields are not affected.

## Fields without a value

`BaseField::submitsValue()` is `false` for a field that only shows something, such as `Display`. `FormConfig` leaves
such a field out of `getRules()`, `validate()`, the defaults and the reserved-name check, in sections and repeaters
too. A renderer leaves it out of the payload.

## Date display format

`'dates' => ['display_format' => 'DD-MM-YYYY']` in `config/form-builder.php` sends that `format` for every `Date` and
`DateTime` field without its own `->format()`. `->format()` on a field wins. `null` (the default) sends no format, so
the renderer shows the date the way the browser does.

## Classes on a field

`->wrapperClass()` replaces the classes of the wrapper around a field's label, control, help and error. `->class()` is
its older name and keeps working. `->inputClass()` adds classes to the control itself (input, select trigger,
checkbox, button). In the schema they are `className` and `inputClassName`.

## Form requests

`TranquilTools\FormBuilder\Concerns\UsesForm` builds the form once and answers both `rules()` and `attributes()` of a
`FormRequest` from it:

```php
class BookingRequest extends FormRequest
{
    use UsesForm;

    protected function form(): FormConfig
    {
        return BookingForm::make();
    }
}
```

## Error bags

`$form->errorBag('newsletter')` sends `errorBag` in the schema and makes `validate()` put its errors in that bag, so
two forms with the same field names on one page keep their errors apart. Without a bag the errors stay in the default
bag.

## Namespace

The namespace is `TranquilTools\FormBuilder\` and did **not** change when this package was extracted from the Vue
renderer, so `use TranquilTools\FormBuilder\Fields\Text;` keeps working. Exactly one installed package may claim that
namespace: this one.

## Credits

Built by [ComfyCoders BV](https://comfycoders.nl). Licensed under MIT — see [LICENSE.md](LICENSE.md).
