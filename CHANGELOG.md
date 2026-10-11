# Changelog

All notable changes to `laravel-form-builder` will be documented in this file.

## 1.4.0 - 2026-10-11

* Add a `Money` field (type `money`) for an amount stored as whole minor units (cents). `->currency('EUR')` (default
  from the new `money.currency` config key), `->locale()`, `->decimals()` (follows the currency: `JPY` 0, `KWD` 3,
  others 2), and `->minValue()`, `->maxValue()` and `->unsigned()` in major units that validate in minor units. It
  validates `integer`, is `nullable` unless required, and normalises to an `int` or `null`. `toMinorUnits()` and
  `toMajorUnits()` convert.
* Add a `Translatable` field (type `translatable`) holding one value per locale: `->locales([...])` (default from the
  new `translatable.locales` config key, else the app locale), `->localeLabels()`, `->defaultLocale()`,
  `->field(Textarea::class)` or an instance, `->stacked()`. `->required()` requires the default locale,
  `->requiredLocales([...])` exactly those, a required inner field every locale. Each locale is validated on its own
  key (`body.nl`) with the inner field's rules; normalising keeps exactly the configured locales.
* `Password` takes `->stored(bool)` to say a value is stored, and `->removable(bool, ?string $flag)` for a "remove the
  stored value" flag with `removalRequested()`, the same as `File`. Both now share the `HasRemoveFlag` trait.
* Add `->hideLabel()` on every field: the label is hidden visually but stays for screen readers.
* `FormConfig` takes `->resetOnSuccess()` (clear the inputs after a successful submit) and `->submitOnChange()`
  (submit on every change, for filter bars).
* A `GET` form fills its defaults from the query string of the request (query values win over `fill()`), so a filter
  form shows the filter the page was loaded with. `->fillFromQuery(bool)` turns this off, or on for other methods.
* Radio options keep a `description`, an `href` and a `linkLabel` (from a list of arrays, or keyed options with an
  array), and separate radios take `->description()` and `->link($href, $label)`. A link with a scheme other than
  `http`, `https`, `mailto` or `tel` is dropped.
* `SCHEMA_VERSION` is now `1.5`. The new types need laravel-vue-form-builder 1.7.0 or newer; an older renderer shows
  its "Unknown field type" notice for `money` and `translatable` and ignores the other additions.

## 1.3.0 - 2026-10-11

* `Date` and `DateTime` take `->timezone(?string)`: an IANA name, or `Date::BROWSER_TIMEZONE` for the user's own. A
  default for every field lives in the new `dates.timezone` config key (`null`, so nothing changes until set). A date
  field with a time then sends its timezone and the application timezone in the schema, and the renderer submits ISO
  8601 with an offset. `FormConfig::validate()` converts those values to the application timezone as `Y-m-d H:i:s`;
  `FormConfig::normalizeValues()` and `BaseField::normalizeValue()` do the same for a FormRequest.
* `File` takes `->current(?string $url, ?string $name = null)` to show the stored file, `->preview(bool)` for its
  thumbnail and `->removable(bool $removable = true, ?string $flag = null)` for a remove checkbox. The flag defaults to
  `remove_{name}`, gets a `nullable|boolean` rule and is read back with `->removalRequested($request)`.
* Add a `Section` layout element (type `section`) with `->title()`, `->description()` and `->fields([...])`. It holds no
  value; the rules of its fields are collected as if they were top-level fields.
* The schema carries the form `locale`, `app()->getLocale()` unless set with `->locale()`, so a renderer can pick its
  built-in texts.
* `SCHEMA_VERSION` is now `1.4`. Sections need laravel-vue-form-builder 1.6.0 or newer; an older renderer shows its
  "Unknown field type" notice in place of a section and the fields inside it.

## 1.2.0 - 2026-10-02

* Add a `Time` field (`TranquilTools\FormBuilder\Fields\Time`, type `time`) for a time of day as `HH:MM` (24h). It
  validates with `date_format:H:i` and is `nullable` unless `->required()`.
* `->step(int $minutes)` sets the interval of the suggested times (default 15, kept between 1 and 720). `->min('08:00')`
  and `->max('18:00')` limit the range and add `after_or_equal` / `before_or_equal` rules. The field is clearable
  unless required; `->clearable(bool)` overrides that.
* `SCHEMA_VERSION` is now `1.3`. The new type needs laravel-vue-form-builder 1.5.0 or newer to render; an older
  renderer shows its "Unknown field type" notice for it and keeps rendering every other field.

## 1.1.0 - 2026-10-01

* `Button`, `Submit` and `DeleteButton` accept an `HtmlString` as label, for an icon or markup on the button. The schema
  carries it as `labelHtml`; `label` keeps the plain text of it (or the default label when the HTML has no text), so a
  confirm dialog or a renderer without HTML support still has something to show.
* Add `->ariaLabel(string)` on buttons, for icon-only buttons that need an accessible name.
* `SCHEMA_VERSION` is now `1.2`. Both keys are additions a renderer may safely ignore.
* Code style: Pint now formats the whole package with the Laravel preset.

## 1.0.1 - 2026-08-14

* Add a `theme` config block — `wrapper`, `label`, `help` and `error` — carried in the schema, so any renderer inherits
  the same classes. A key left `null` or empty keeps the renderer's own default rather than blanking it.
* Add `->theme([...])` on every field, overriding the form-wide theme for that field only. Repeated calls merge.
* `SCHEMA_VERSION` is now `1.1`. This is an addition a renderer may safely ignore, so the major is unchanged and older
  renderers keep working.

## 1.0.0 - 2026-08-04

First release. This package is the PHP half of `laravel-vue-form-builder`, extracted so a renderer for another frontend
can reuse it. It carries the full git history of that code.

Released together with laravel-vue-form-builder 1.2.0, laravel-vue-table-builder 1.2.0 and laravel-vue-crud-builder
1.2.0.

**Nothing changes for applications.** The namespace `TranquilTools\FormBuilder\` is unchanged, and
`composer require tranquil-tools/laravel-vue-form-builder` pulls this package in transitively.

* Contains `src/`, `config/`, `resources/lang/` and `stubs/`. The Vue renderer keeps `resources/js` and the
  documentation.
* The config file is `config/form-builder.php`, renamed from `vue-form-builder.php`. A published
  `config/vue-form-builder.php` is still read and merged, with a deprecation warning when debug mode is on, so no
  application breaks on upgrade. Translations follow the same pattern: the namespace is `form-builder::`, with
  `vue-form-builder::` kept as an alias.
* Adds `FormConfig::SCHEMA_VERSION`, emitted as `schemaVersion` in every payload. A renderer that does not support the
  version refuses to render instead of silently dropping unknown field types.
