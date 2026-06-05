# Filament Action Overflow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)
[![Total Downloads](https://img.shields.io/packagist/dt/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)

Turn any Filament action list into **a few primary buttons + a "More" dropdown**, automatically.

```text
[ Edit ] [ Archive ] [ ⋮ More ▾ ]
                       ├─ Publish
                       ├─ Delete
                       └─ Download
```

You write a flat list of actions; the package decides which sit out front and which get tucked under **More**. Works anywhere Filament accepts an action array — page headers, table actions, record actions, bulk actions, widgets.

## Why use it

- **One line.** Append `->withOverflow()` to an `ActionGroup` and you're done.
- **Smart defaults.** No overflow → no `More`. One overflow action → flattened. Two or more → grouped.
- **Divider aware.** Filament 5's `->dropdown(false)` divider sections pass through into the **More** menu cleanly.
- **Hidden-action aware.** `->hidden()` / `->visible(false)` / (optionally) `->authorize(...)` are honored before composing.

## Compatibility

| Package  | Versions          |
| -------- | ----------------- |
| Filament | `^4.0` ‖ `^5.0`   |
| PHP      | `^8.2`            |

## Installation

```bash
composer require harvirsidhu/filament-action-overflow
```

The package works without any config. Publish only if you want to change defaults:

```bash
php artisan vendor:publish --tag="filament-action-overflow-config"
```

## Quick start

The macro is the simplest path. Build an `ActionGroup` like normal, append `->withOverflow($primary)`, and return the result.

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

public function getHeaderActions(): array
{
    return ActionGroup::make([
        Action::make('edit'),
        Action::make('archive'),
        Action::make('delete'),
    ])->withOverflow(1);
    // → [ Edit ] [ ⋮ More ▾ (Archive, Delete) ]
}
```

`->withOverflow()` is terminal — it returns the composed `array<Action | ActionGroup>` ready for `getHeaderActions()`, `getTableActions()`, `getRecordActions()`, etc.

By default both the primary actions and the **More** trigger are promoted to button view, so you get a row of matching buttons without sprinkling `->button()` on each action.

## Customizing the More button

For control over the dropdown's label, icon, color, etc., use the `ActionOverflow` facade:

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

## Positioning the More button

By default the **More** control sits *after* the primary actions. Pass `MorePosition::Start` to put it first instead:

```php
use Filament\Actions\Action;
use Harvirsidhu\FilamentActionOverflow\Enums\MorePosition;
use Harvirsidhu\FilamentActionOverflow\Facades\ActionOverflow;

return ActionOverflow::make([
    Action::make('edit'),
    Action::make('archive'),
    Action::make('delete'),
])
    ->morePosition(MorePosition::Start)
    ->toActions();
// → [ ⋮ More ▾ (Archive, Delete) ] [ Edit ]
```

`morePosition()` also accepts the string forms `'start'` / `'end'`.

`Start` is **direction-aware**: the package only reorders the action array, and Filament renders it in the reading direction — so `Start` lands on the left in LTR layouts and automatically flips to the right under RTL. There's nothing extra to configure for RTL.

The same positioning applies when overflow collapses to a single flattened action, not just the **More** group.

## Dividers (sections inside the More menu)

A `dropdown(false)` group nested inside another `ActionGroup` is Filament's way of saying *"render these as a separated section."* This package treats them as first-class:

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

Renders as:

```text
[ Submit ] [ ⋮ More ▾ ]
              ├─ ──────────
              ├─ Discount
              ├─ Tax
              ├─ Rounding
              ├─ ──────────
              ├─ Change billing
              └─ Refresh
```

How dividers behave in the composer:

- A divider doesn't take up a primary slot — its **children** do. With `primaryCount: 2` and a divider whose first child is `submit`, `submit` is promoted to primary.
- Dividers never appear among side-by-side primary buttons (they only make sense inside a dropdown).
- A divider at the very top of the **More** menu is unwrapped (no orphan line above the first item).
- Trailing and adjacent dividers stay as distinct sections — exactly how Filament renders them natively.
- Hidden / invisible children are dropped from dividers; if every child is dropped, the divider disappears.

## Hidden, invisible, unauthorized

Hidden and invisible actions are filtered automatically:

```php
return ActionOverflow::make([
    Action::make('edit')->hidden(),         // dropped
    Action::make('archive'),                // kept
    Action::make('delete')->visible(false), // dropped
    Action::make('publish'),                // kept
])->toActions();
```

Authorization filtering is **opt-in** because Filament's renderer already disables unauthorized actions visually:

```php
return ActionOverflow::make($actions)
    ->filterUnauthorized()
    ->toActions();
```

## Reference

### Config keys (`config/action-overflow.php`)

| Key                   | Type           | Default                          | What it does                                  |
| --------------------- | -------------- | -------------------------------- | --------------------------------------------- |
| `primary_count`       | `int`          | `1`                              | How many primary actions to surface           |
| `label`               | `string`       | `'More'`                         | The dropdown trigger's label                  |
| `icon`                | `string\|enum` | `'heroicon-m-ellipsis-vertical'` | Trigger icon                                  |
| `color`               | `string`       | `'gray'`                         | Filament color name for the trigger           |
| `hidden_label`        | `bool`         | `false`                          | Hide the trigger label, show icon only        |
| `button`              | `bool`         | `true`                           | Promote primary + trigger to button view      |
| `icon_position`       | `string\|enum` | `'after'`                        | `'before'` or `'after'` (or `IconPosition`)   |
| `filter_unauthorized` | `bool`         | `false`                          | Drop unauthorized actions before composing    |
| `more_position`       | `string\|enum` | `'end'`                          | `'start'` or `'end'` (or `MorePosition`)      |

`icon` accepts a string, a `BackedEnum` (e.g. `Filament\Support\Icons\Heroicon::EllipsisVertical` on Filament 5), or `null` for the default. `icon_position` accepts the string forms or a `Filament\Support\Enums\IconPosition` enum. Strings are the published defaults so the file loads cleanly on both Filament 4 and 5.

### Fluent API

```php
ActionOverflow::make($actions)
    ->primaryCount(int $count = 1)
    ->label(string $label = 'More')
    ->icon(string|\BackedEnum|null $icon = null)
    ->color(string $color = 'gray')
    ->hiddenLabel(bool $state = true)
    ->button(bool $state = true)
    ->iconPosition(\Filament\Support\Enums\IconPosition|string|\BackedEnum|null $position = \Filament\Support\Enums\IconPosition::After)
    ->filterUnauthorized(bool $state = true)
    ->morePosition(\Harvirsidhu\FilamentActionOverflow\Enums\MorePosition|string $position = \Harvirsidhu\FilamentActionOverflow\Enums\MorePosition::End)
    ->toActions();
```

`->button(false)` is opt-out only — it stops the composer from calling `->button()`. If a caller already passed a pre-buttoned action, it keeps its button view.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Credits

- [harvirsidhu](https://github.com/harvirsidhu)

## License

MIT — see [LICENSE](LICENSE.md).
