# Changelog

All notable changes to `laravel-form-builder` will be documented in this file.

## 1.5.1 - 2026-10-12

* Fix a regression from 1.5.0: `AbstractForm` was marked `@phpstan-consistent-constructor`, so PHPStan reported
  `parameter.notOptional` on every form in an application whose constructor takes required arguments, although
  `make()`, `rules()` and `attributes()` forward those arguments as before. The marker is gone and the package ignores
  `new.static` on that one line instead, so applications need no ignore for their forms. Behaviour is unchanged.
* PHPStan in CI now also analyses `tests/Fixtures`, which holds a form with required constructor arguments, so this
  cannot come back unnoticed.

## 1.5.0 - 2026-10-12

* `Date` takes `->disabledWeekdays([0, 6])` (0 = Sunday … 6 = Saturday) and `->disabledDates([...])` with `Y-m-d`
  strings, date objects and `['from' => ..., 'to' => ...]` ranges (either end may be left out). The schema carries
  them as `disabledWeekdays` and `disabledDates`, and the field adds the new `Rules\AvailableDate` rule, so the server
  rejects a disabled date as well ("The :attribute cannot be on that date.", English and Dutch). It reads the calendar
  date at the start of the value, also with a time or an offset; for a range it checks the start and the end. Use
  `new AvailableDate($weekdays, $dates)` on its own in any validator.
* `Section` takes `->columns(int)` (1 to 4) to place its fields side by side, and every field takes `->columnSpan(int)`
  and `->columnSpanFull()`. A nested section without columns inherits those of the section around it.
* Radio options keep a `target`; `->link($href, $label, ?string $target)` takes one for a separate radio, and
  `->openLinksInNewTab()` gives every link of the field `_blank` (`Radio::NEW_TAB`) unless it has its own target. An
  unsafe link drops its target with it.
* Translatable fields name their locale inputs for validation messages: `title.en` becomes "Title (English)", or
  "Title (Engels)" in Dutch. `FormConfig::getAttributes()` and `AbstractForm::attributes()` return them,
  `FormConfig::validate()` passes them on (attributes passed to `validate()` win), and the `make:form-request` stub
  now returns them from `attributes()`. Locale names come from the new `translatable.locale_names` config key, the
  package translations (English and Dutch names for about twenty languages), the `intl` extension, or the upper-cased
  code. Every field has a `validationAttributes()` hook, empty by default.
* `Money` bounds validate with the new `Rules\MoneyBounds` rule instead of `min:`/`max:` in minor units, so the message
  names the amount in major units with its currency in the field locale: "The price must not be greater than
  €1,000.00." / "Prijs mag niet groter zijn dan € 1.000,00.". Formatting uses `intl` when installed and a built-in
  fallback otherwise. `getRules()` holds the rule object in place of the two strings.
* Fix the PHPStan errors: `AbstractForm` declares a consistent constructor for `new static`, and
  `FormMakeCommand::handle()` returns `?bool` like the command it extends. An existing class without `--force` still
  stops before asking for a request, now with exit code 0 like Laravel's own generators. CI now runs PHPStan.
* `SCHEMA_VERSION` is now `1.6`. The new keys need laravel-vue-form-builder 1.8.0 or newer; an older renderer ignores
  them and keeps working, while the server-side rules and messages work with any renderer.

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
