# Filament Action Overflow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)
[![Total Downloads](https://img.shields.io/packagist/dt/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)

> Turn a flat list of Filament actions into **a few primary buttons + a tidy "More" dropdown** — automatically.

You declare every action in one list. The package decides which ones sit out front and which get tucked under **More**, so crowded action rows stop wrapping and your UI stays clean.

```text
You write:                          You get:

Edit                                ┌──────┐ ┌─────────┐ ┌──────────┐
Archive            ──────────▶      │ Edit │ │ Archive │ │ ⋮ More ▾ │
Publish                             └──────┘ └─────────┘ └────┬─────┘
Delete                                                        ├─ Publish
Download                                                      ├─ Delete
                                                              └─ Download
```

Works **anywhere Filament accepts an action array** — page headers, table actions, record actions, bulk actions, widgets.

---

## Contents

- [Why this package](#why-this-package)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start (30 seconds)](#quick-start-30-seconds)
- [Three ways to use it](#three-ways-to-use-it)
- [How it decides what overflows](#how-it-decides-what-overflows) — the mental model
- [More or split?](#more-or-split)
- [Recipes](#recipes)
  - [Choose how many primary actions](#choose-how-many-primary-actions)
  - [Style the More trigger](#style-the-more-trigger)
  - [Translate the More label](#translate-the-more-label)
  - [Put More on the left](#put-more-on-the-left-and-rtl) (and RTL)
  - [A split button](#a-split-button)
  - [Which way the menu opens](#which-way-the-menu-opens)
  - [Sections inside the menu (dividers)](#sections-inside-the-menu-dividers)
  - [Hidden, invisible & unauthorized actions](#hidden-invisible--unauthorized-actions)
  - [Visibility that depends on the page or the record](#visibility-that-depends-on-the-page-or-the-record)
  - [Button vs. link appearance](#button-vs-link-appearance)
  - [Set defaults globally](#set-defaults-globally)
- [API reference](#api-reference)
- [Under the hood](#under-the-hood)
- [FAQ](#faq)
- [Testing](#testing) · [Changelog](#changelog) · [Credits](#credits) · [License](#license)

---

## Why this package

- **One line to adopt.** Append `->withOverflow()` to any `ActionGroup`.
- **Smart by default.** Nothing to overflow → no **More** button. One leftover action → shown inline, not buried. Two or more → grouped under **More**.
- **Section-aware.** Filament's `->dropdown(false)` divider groups flow into the menu as real, separated sections.
- **Visibility-aware.** `->hidden()`, `->visible(false)`, and (opt-in) `->authorize(...)` are all honored *before* anything is composed, so hidden actions never leak into **More**.
- **Direction-aware.** Position **More** at the start or end; under RTL it flips correctly with zero extra config.
- **Translated.** **More** is shown in the app's language, with 49 languages included.
- **Split button.** `->split()` joins the main action and its menu into one control.
- **Filament 4 & 5.** A single compatibility layer keeps one codebase working across both.

---

## Requirements

| Requirement | Version        |
| ----------- | -------------- |
| PHP         | `^8.2`         |
| Filament    | `^4.0` ‖ `^5.0` |

---

## Installation

```bash
composer require harvirsidhu/filament-action-overflow
```

That's it — the package auto-registers and works with zero configuration.

A [split button](#a-split-button) also uses a small stylesheet, published with your other Filament assets by `php artisan filament:assets`. Filament's own installation adds `php artisan filament:upgrade`, which runs it, to Composer's `post-autoload-dump`, so most apps already do this on every `composer install`. Without it, a split button's two halves show as two separate buttons.

Only publish the config if you want to change the defaults globally:

```bash
php artisan vendor:publish --tag="filament-action-overflow-config"
```

---

## Quick start (30 seconds)

Build an `ActionGroup` exactly as you normally would, then chain **`->withOverflow()`** and return the result:

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

public function getHeaderActions(): array
{
    return ActionGroup::make([
        Action::make('edit'),
        Action::make('archive'),
        Action::make('delete'),
    ])->withOverflow(); // keep 1 primary, overflow the rest
}
```

```text
[ Edit ]  [ ⋮ More ▾ ]
            ├─ Archive
            └─ Delete
```

`withOverflow(?int $primary = null)` is **terminal**: it returns the finished `array<Action|ActionGroup>`, ready to return from a page's `getHeaderActions()`, or to pass to a table's `recordActions()`, `headerActions()` or `toolbarActions()`. Without a count it keeps the configured one: `1` unless you [changed it](#set-defaults-globally).

> By default, the primary actions **and** the **More** trigger are rendered as buttons, so you get a row of matching buttons without sprinkling `->button()` everywhere.

---

## Three ways to use it

Two macros for the common cases, and the builder underneath them for everything else.

| | `->withOverflow()` macro | `->withSplit()` macro | `ActionOverflow` builder |
| --- | --- | --- | --- |
| **Best for** | The common case | A [split button](#a-split-button) | When you need control |
| **Syntax** | Chains onto an `ActionGroup` | Chains onto an `ActionGroup` | Standalone builder |
| **Customizes label / icon / color / position?** | No (only the primary count) | No | Yes, full fluent API |
| **Returns** | `array` (terminal) | `array` (terminal) | `array` via `->toActions()` (terminal) |

**Macros** — shortest path:

```php
ActionGroup::make([...])->withOverflow(2);
ActionGroup::make([...])->withSplit();
```

**Builder** — when you want to change the trigger or behavior:

```php
use Filament\Actions\Action;
use Harvirsidhu\FilamentActionOverflow\Facades\ActionOverflow;

return ActionOverflow::make([
    Action::make('edit'),
    Action::make('archive'),
    Action::make('delete'),
])
    ->primaryCount(2)
    ->label('Options')
    ->icon('heroicon-m-bars-3')
    ->color('gray')
    ->toActions();
```

All three produce the same kind of output. The macros are sugar over the builder: `withOverflow(2)` is `ActionOverflow::make($actions)->primaryCount(2)->toActions()`, and `withSplit()` is `ActionOverflow::make($actions)->split()->toActions()`. The builder's `make()` is on the facade above (also aliased as `ActionOverflow`) and on the class `Harvirsidhu\FilamentActionOverflow\ActionOverflow` itself.

---

## How it decides what overflows

The whole package is one deterministic pipeline. Understanding it means you can always predict the output.

**1. It filters first.** Hidden, invisible, and (if you opt in) unauthorized actions are removed *before* anything else — including those nested inside divider sections. Only *available* actions are ever counted or placed.

**2. It fills primary slots left-to-right.** Walking your list in order, it promotes actions to the front until `primaryCount` is reached (default `1`). Everything after that overflows. Order is preserved — the list order *is* the priority order.

**3. It applies the count rule to whatever is left:**

| Available overflow actions | Result |
| -------------------------- | ------ |
| **0** | No **More** button at all — just the primary actions. |
| **1** | That single action is shown **inline** (promoted to a button), never hidden behind a dropdown for no reason. |
| **2 or more** | Grouped under a single **More** dropdown. |

**4. It positions the More control** at the end (default) or start of the row — see [Put More on the left](#put-more-on-the-left-and-rtl).

That's the entire model. Everything below is just configuring these steps.

---

## More or split?

Two patterns from the same builder, for two different jobs:

| | **More** (default) | **Split** (`split()`) |
| --- | --- | --- |
| For | Too many actions: the extras are other things you can do. | One action and its variants: the same action done differently. |
| Looks like | `[ Edit ] [ ⋮ More ▾ ]` | `[ Call next │ ▾ ]` |
| The menu's button | Grey, standing apart, labelled **More**. | A chevron in the main button's colour, joined to it. |
| Main buttons | `primaryCount()` of them, any number. | Exactly one. |

**Rule of thumb:** if every item in the menu reads as *"the main button, but…"* (where it sends, how it prints, what it saves as), use split. If they are just more things to do, use More. Other buttons stand beside a split button, not inside it.

```php
// More: tuck the extras away
ActionGroup::make([$edit, $archive, $delete])->withOverflow();

// Split: one action and its variants
ActionGroup::make([$callNext, $toCounterOne, $toCounterTwo])->withSplit();
```

---

## Recipes

### Choose how many primary actions

`primaryCount` controls how many actions stay out front. The rest overflow.

```php
ActionGroup::make([...])->withOverflow(2);          // macro
ActionOverflow::make([...])->primaryCount(2)->toActions(); // builder
```

`primaryCount(0)` is valid and pushes **everything** into **More**. Negative values throw an `InvalidArgumentException`.

### Style the More trigger

Only available on the builder (the macros intentionally keep their surface tiny):

```php
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

ActionOverflow::make($actions)
    ->label(__('Manage'))                      // trigger text, used as given
    ->icon(Heroicon::EllipsisVertical)         // string | BackedEnum | Filament icon enum
    ->iconPosition(IconPosition::Before)       // 'before' | 'after' | IconPosition enum
    ->color('danger')                          // any Filament color name
    ->hiddenLabel()                            // icon-only trigger (where supported)
    ->toActions();
```

- **`icon()`** accepts a plain string (`'heroicon-m-bars-3'`), a backed enum, or a Filament icon enum like `Heroicon::EllipsisVertical`.
- **`hiddenLabel()`** draws an icon-only trigger. The label stays as its name for screen readers, so keep it meaningful.

### Translate the More label

**More** is shown in the app's language. The package includes it in 49 languages, under the locale codes Filament uses (`ms`, `zh_CN`, `pt_BR`…). To change a word, or add a language, publish the translations and edit them:

```bash
php artisan vendor:publish --tag="filament-action-overflow-translations"
```

They land in `lang/vendor/filament-action-overflow/{locale}/action-overflow.php`, each as `'more' => '…'`.

- A label you give in code is used as given, like any Filament label, so translate it there: `->label(__('Manage'))`. `label()` with nothing goes back to the translated **More**.
- A label set in the config is translated when the list is composed, from your own lang files (`lang/ms.json`: `{"Manage": "Urus"}`), because a config file can't call `__()` itself.
- A split button's menu uses the same label as its tooltip and screen-reader name, so it is translated too.

### Put More on the left (and RTL)

By default the **More** control sits **after** the primary actions. Use `MorePosition::Start` to put it first:

```php
use Harvirsidhu\FilamentActionOverflow\Enums\MorePosition;

ActionOverflow::make($actions)
    ->morePosition(MorePosition::Start) // or the string 'start'
    ->toActions();
```

```text
[ ⋮ More ▾ ]  [ Edit ]
```

**This is the right answer to "how do I support RTL?"** — there's nothing extra to do. The package only reorders the *array*, and Filament renders that array in the reading direction. So `Start` means the **reading start**: it lands on the left in LTR and automatically flips to the right under RTL. Use `Start`/`End` (logical) rather than thinking left/right (physical), and direction handling is free.

The position applies to the flattened single-action case too, not just the grouped **More** trigger.

### A split button

When the leftover actions are variations of the main one (where it sends, how it prints, what it saves as), `split()` joins the main action and the menu into one control:

```php
ActionOverflow::make([
    Action::make('callNext'),
    Action::make('counterOne')->label('Counter 1'),
    Action::make('counterTwo')->label('Counter 2'),
])
    ->split()
    ->toActions();
```

```text
[ Call next │ ▾ ]
              ├─ Counter 1
              └─ Counter 2
```

In split mode:

- The menu's trigger is an icon-only chevron in the main button's colour. `icon()` and `color()` still win when you set them on the builder; the config's `icon` and `color` style the **More** button only.
- `label()` names the menu: it is the chevron's tooltip and what a screen reader announces, so say what the choices are (`->label(__('Calling to'))`) rather than leaving it at **More**.
- A single leftover action stays in the menu: a split button with one choice is still a split button. With nothing left over there is only the main button.
- It has **one main action**, whatever `primary_count` the config sets. `toActions()` throws an `InvalidArgumentException` if you ask for any other `primaryCount()`: with two main buttons nobody can tell which one the menu belongs to. Put other buttons beside it.
- If the main action is hidden, the next available one becomes the main, because list order is priority order.
- `morePosition(MorePosition::Start)` puts the menu on the leading side.
- The menu opens below the button along its outer edge; [`dropdownPlacement()`](#which-way-the-menu-opens) changes that.

`ActionGroup::make([...])->withSplit()` is the one-line form, like `withOverflow()`.

The join is drawn by a small stylesheet the package registers with Filament (`resources/dist/filament-action-overflow.css`). It is published with your other Filament assets by `php artisan filament:assets`, which `php artisan filament:upgrade` runs.

### Which way the menu opens

Where the **More** control sits (`morePosition()`) and which way its menu opens are two separate settings. The menu's direction is Filament's own `dropdownPlacement()`, passed to the menu as is:

```php
ActionOverflow::make($actions)
    ->dropdownPlacement('bottom-start')
    ->toActions();
```

```text
bottom-end (header default)        bottom-start
      [ ⋮ More ▾ ]                 [ ⋮ More ▾ ]
 ┌──────────────┐                  ┌──────────────┐
 │ Archive      │                  │ Archive      │
 └──────────────┘                  └──────────────┘
```

- `bottom-end` lines the menu's end edge up with the button's and opens toward the start; `bottom-start` is its mirror; `top-start` / `top-end` open upward. These are Floating UI's placements, so `start` and `end` follow the reading direction.
- Filament flips the menu by itself when there is no room on that side (`dropdownFlip()`, on by default).
- Left unset, the **More** menu keeps Filament's default for where it sits: `bottom-end` in a page's header actions, `bottom-start` elsewhere. A split button's menu opens below the button along its outer edge: `bottom-end` with the ▾ at the end, `bottom-start` with it at the start. Inside a group Filament would otherwise open it to the side, as a submenu.

### Sections inside the menu (dividers)

A `dropdown(false)` group nested inside an `ActionGroup` is Filament's idiom for *"render these as a separated section."* This package treats those as first-class and carries them into the **More** menu:

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

return ActionGroup::make([
    Action::make('submit'),

    ActionGroup::make([
        Action::make('discount'),
        Action::make('tax'),
        Action::make('rounding'),
    ])->dropdown(false),

    ActionGroup::make([
        Action::make('change-billing'),
        Action::make('refresh'),
    ])->dropdown(false),
])->withOverflow(1);
```

```text
[ Submit ]  [ ⋮ More ▾ ]
              ├─ Discount
              ├─ Tax
              ├─ Rounding
              ├─ ──────────
              ├─ Change billing
              └─ Refresh
```

How dividers behave — predictable and lossless:

- **A divider never consumes a primary slot — its children do.** With `primaryCount(2)` and a leading divider whose first child is `submit`, `submit` is promoted to primary; the divider's remaining children stay grouped in overflow.
- **Dividers never appear among the side-by-side primary buttons** — separators only make sense inside a dropdown.
- **A divider at the very top of the menu is unwrapped**, so you don't get an orphan separator line above the first item.
- **Trailing and adjacent dividers are preserved as distinct sections** — exactly how Filament renders multiple `dropdown(false)` groups natively.
- **Unavailable children are dropped from a divider; if every child is gone, the whole divider disappears.**

### Hidden, invisible & unauthorized actions

Hidden and invisible actions are filtered automatically — they're gone before composition, so they can't sneak into **More**:

```php
ActionOverflow::make([
    Action::make('edit')->hidden(),          // dropped
    Action::make('archive'),                 // kept
    Action::make('delete')->visible(false),  // dropped
    Action::make('publish'),                 // kept
])->toActions();
```

Authorization filtering is **opt-in**, because Filament's renderer already disables unauthorized actions visually. Enable it when you'd rather drop them entirely:

```php
ActionOverflow::make($actions)
    ->filterUnauthorized()
    ->toActions();
```

### Visibility that depends on the page or the record

The package reads each action's visibility when it composes the list. Inside `getHeaderActions()` that is before Filament attaches the actions to the page, so a `visible()` or `hidden()` closure that reads `$livewire` or `$record` would get `null`. Give the builder the page with `livewire()`, or set it on the group before a macro:

```php
public function getHeaderActions(): array
{
    return ActionOverflow::make([
        Action::make('publish')->visible(fn (Post $record): bool => $record->isDraft()),
        Action::make('edit'),
        Action::make('delete'),
    ])
        ->livewire($this)
        ->toActions();

    // or: ActionGroup::make([...])->livewire($this)->withOverflow();
}
```

Every action, and every action inside a divider, is attached to the page first, so on a record's page `$record` is that record, as it is when Filament draws the action.

A table is different. `recordActions()` is composed once for the whole table, before any row, so which actions sit out front can't change from row to row. Filament still hides an action in a row where it isn't visible. Just make sure a closure that reads `$record` doesn't hide the action when there is no row yet, or it disappears from every row:

```php
Action::make('publish')->visible(fn (?Post $record): bool => $record?->isDraft() ?? true)
```

### Button vs. link appearance

By default, primary actions and the **More** trigger are promoted to **button** view so the row looks uniform. To leave each action's own render style untouched, opt out:

```php
ActionOverflow::make($actions)->button(false)->toActions();
```

`button(false)` is **opt-out only** — it stops the composer from *adding* `->button()`. An action you already styled with `->button()` keeps its button view regardless.

A split button's two halves are buttons either way: Filament's button group draws its actions as buttons unless an action says otherwise.

### Set defaults globally

Prefer to configure once instead of per-call? Publish the config and edit the defaults — every `withOverflow()`, `withSplit()` and `ActionOverflow::make()` call inherits them (and per-call methods, or a count passed to `withOverflow()`, still override them):

```php
// config/action-overflow.php
return [
    'primary_count'       => 2,
    'label'               => 'Manage',
    'more_position'       => 'start',
    'filter_unauthorized' => true,
    // ...
];
```

---

## API reference

### `ActionGroup::withOverflow()` macro

```php
ActionGroup::make(array $actions)->withOverflow(?int $primary = null): array
```

Terminal. Composes the group's actions with the given primary count (the configured `primary_count` when you pass none), using config defaults for everything else. Returns the finished action array. The actions come out as if you had listed them directly: the group is only their carrier. A `livewire()` set on the group is passed on, and a group hidden by its own `hidden()` / `visible()` returns `[]`.

### `ActionGroup::withSplit()` macro

```php
ActionGroup::make(array $actions)->withSplit(): array
```

Terminal. The [split button](#a-split-button) in one line: the group's first available action is the button, the rest are its menu. The same as `ActionOverflow::make($actions)->split()->toActions()`, and it treats the group as `withOverflow()` does.

### `ActionOverflow` fluent API

Every method returns `$this` for chaining; `toActions()` ends the chain and returns the array.

| Method | Default | Description |
| ------ | ------- | ----------- |
| `make(array $actions)` | — | Start a builder from a flat action list. |
| `primaryCount(int $count)` | `1` | How many actions stay out front. `0` overflows everything; negatives throw. |
| `label(?string $label = null)` | **More**, translated | The **More** trigger's text, used as given. `null` goes back to the translated default. See [Translate the More label](#translate-the-more-label). |
| `icon(string\|BackedEnum\|null $icon)` | ellipsis | Trigger icon. String, backed enum, or Filament icon enum; `null` restores the default. |
| `iconPosition(IconPosition\|string\|BackedEnum\|null $position)` | `After` | `'before'` / `'after'` or an `IconPosition` enum. |
| `color(string $color)` | `'gray'` | Any Filament color name for the trigger. |
| `hiddenLabel(bool $state = true)` | `false` | Icon-only trigger (where the Filament version supports it). |
| `button(bool $state = true)` | `true` | Promote primary actions + trigger to button view. `false` opts out. |
| `morePosition(MorePosition\|string $position)` | `End` | Place the **More** control at the `Start` or `End` of the row. |
| `filterUnauthorized(bool $state = true)` | `false` | Drop unauthorized actions before composing. |
| `livewire(Component $livewire)` | — | Attach every action to the page (or another Livewire component) before visibility is read, so closures that read `$livewire` or `$record` get them. See [Visibility that depends on the page or the record](#visibility-that-depends-on-the-page-or-the-record). |
| `split(bool $state = true)` | `false` | Join the main action and its menu into one control: a [split button](#a-split-button). Exactly one main action, whatever the configured `primary_count`. |
| `dropdownPlacement(?string $placement)` | Filament's | Which way the menu opens: `'bottom-start'`, `'bottom-end'`, `'top-end'`… See [Which way the menu opens](#which-way-the-menu-opens). |
| `toActions()` | — | **Terminal.** Returns `array<Action\|ActionGroup>`. |

### `MorePosition` enum

`Harvirsidhu\FilamentActionOverflow\Enums\MorePosition`

| Case | String | Meaning |
| ---- | ------ | ------- |
| `Start` | `'start'` | Reading start — left in LTR, right in RTL. |
| `End` | `'end'` | Reading end — right in LTR, left in RTL (default). |

### Config keys (`config/action-overflow.php`)

| Key | Type | Default | Maps to |
| --- | ---- | ------- | ------- |
| `primary_count` | `int` | `1` | `primaryCount()` |
| `label` | `string\|null` | `null` (**More**, translated) | `label()`, translated |
| `icon` | `string\|enum` | `'heroicon-m-ellipsis-vertical'` | `icon()` |
| `icon_position` | `string\|enum` | `'after'` | `iconPosition()` |
| `color` | `string` | `'gray'` | `color()` |
| `hidden_label` | `bool` | `false` | `hiddenLabel()` |
| `button` | `bool` | `true` | `button()` |
| `more_position` | `string\|enum` | `'end'` | `morePosition()` |
| `filter_unauthorized` | `bool` | `false` | `filterUnauthorized()` |

> Defaults are stored as **strings** (not Filament enum constants) so the published file loads cleanly on both Filament 4 and 5. You may still pass enums at the call site.
>
> A `label` of `'More'`, as older published config files have it, counts as the default, so it is translated too.

---

## Under the hood

For the curious, `toActions()` runs a fixed five-stage pipeline:

1. **Filter** — attach the actions to the page if `livewire()` was given, then remove hidden / invisible / (opt-in) unauthorized actions, recursing one level into divider groups and dropping any divider left empty.
2. **Partition** — walk left-to-right, taking `primaryCount` *real* actions into primary; a divider contributes its children toward that count and re-wraps any leftovers as a divider in overflow.
3. **Promote** — if `button` is on, call `->button()` on the primary actions.
4. **Sanitize** — unwrap a *leading* divider in the overflow so there's no orphan separator at the top of the menu.
5. **Assemble** — count the available overflow actions and apply the [0 / 1 / 2+ rule](#how-it-decides-what-overflows), then place the result at the start or end per `morePosition`. In split mode, wrap the main action and the menu in one Filament button group instead.

The package never renders HTML — it only restructures the action array and hands it back to Filament, which means it composes naturally with everything Filament already does (theming, RTL, authorization display, etc.). Its one stylesheet only joins the split button's two halves.

---

## FAQ

**Why didn't a "More" button appear?**
You have one or zero overflow actions. A single leftover is shown inline by design; **More** appears only with two or more. (In split mode a single leftover stays in the menu.)

**Why is one of my overflow actions a plain button instead of being in the dropdown?**
Same rule — when exactly one action overflows, it's surfaced inline rather than hidden behind a dropdown for a single item.

**Do I need to do anything for RTL?**
No. Use `morePosition(MorePosition::Start)` for "leading side"; it flips automatically with the layout direction. See [Put More on the left](#put-more-on-the-left-and-rtl).

**Can I use it outside page headers?**
Yes — the output is just an action array, valid anywhere Filament accepts one: a table's `recordActions()`, `headerActions()` or `toolbarActions()`, a schema's actions, a widget. In a table, read [the note on row visibility](#visibility-that-depends-on-the-page-or-the-record).

**An action's visibility depends on the record — is that respected?**
On a page, yes, once the builder has the page: `->livewire($this)`, or `ActionGroup::make([...])->livewire($this)` before a macro. Filtering calls the same `isHidden()` / `isVisible()` / `isAuthorized()` your action defines, and without the page a closure that reads `$record` gets `null`. In a table the list is composed once, before any row, so it can't differ from row to row. See [Visibility that depends on the page or the record](#visibility-that-depends-on-the-page-or-the-record).

**My actions' visibility changes within one request (a list page's tabs, say). What happens?**
Visibility is read once, when `toActions()` composes the list. Filament builds a page's header actions once a request, before an update such as a tab switch lands, so compose them again after it. On a list page with tabs, for example:

```php
public function updatedActiveTab(): void
{
    parent::updatedActiveTab();

    $this->cachedHeaderActions = [];
    $this->cacheInteractsWithHeaderActions();
}
```

---

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for release notes.

## Credits

- [harvirsidhu](https://github.com/harvirsidhu)

## License

MIT — see [LICENSE](LICENSE.md).
