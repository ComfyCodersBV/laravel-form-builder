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
| `config/form-builder.php` | theme, required indicator, WYSIWYG defaults, reCAPTCHA and key-value settings              |
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

## Namespace

The namespace is `TranquilTools\FormBuilder\` and did **not** change when this package was extracted from the Vue
renderer, so `use TranquilTools\FormBuilder\Fields\Text;` keeps working. Exactly one installed package may claim that
namespace: this one.

## Credits

Built by [ComfyCoders BV](https://comfycoders.nl). Licensed under MIT — see [LICENSE.md](LICENSE.md).
