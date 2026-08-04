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
| `src/`                    | `AbstractForm`, `FormConfig`, every field type, validation rules, the `make:form` commands |
| `config/form-builder.php` | WYSIWYG defaults, reCAPTCHA and key-value settings                                         |
| `resources/lang/`         | translations used by PHP-side field labels                                                 |
| `stubs/`                  | templates for the generator commands                                                       |

The boundary rule: **what produces schema belongs here, what reads schema belongs in a renderer.**

## The schema contract

`FormConfig::SCHEMA_VERSION` is emitted as `schemaVersion` in every payload. A renderer compares it against the version
it supports and refuses to render rather than silently dropping a field type it does not recognise. Bump the major for
changes that would misrender in an older renderer, the minor for additions that can be ignored safely.

## Namespace

The namespace is `TranquilTools\FormBuilder\` and did **not** change when this package was extracted from the Vue
renderer, so `use TranquilTools\FormBuilder\Fields\Text;` keeps working. Exactly one installed package may claim that
namespace: this one.

## Credits

Built by [ComfyCoders BV](https://comfycoders.nl). Licensed under MIT — see [LICENSE.md](LICENSE.md).
