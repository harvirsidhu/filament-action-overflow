# Filament Action Overflow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)
[![Total Downloads](https://img.shields.io/packagist/dt/harvirsidhu/filament-action-overflow.svg?style=flat-square)](https://packagist.org/packages/harvirsidhu/filament-action-overflow)

Compose any Filament action list into:
- primary actions (first `N`, default `1`),
- and an overflow `More` dropdown for the remainder,
- with pass-through support for Filament 5's divider pattern.

Usable anywhere Filament accepts an action array — page headers, table actions, record actions, bulk actions, widgets.

Behavior is deterministic:
- no overflow => no `More`,
- one overflow action => flattened directly,
- two or more overflow actions => grouped under `More`,
- actions that evaluate as hidden or invisible are ignored before composing,
- authorization filtering is opt-in via `filter_unauthorized` (default `false`),
- nested `ActionGroup::make([...])->dropdown(false)` entries are preserved as dividers inside the overflow dropdown; leading / trailing / adjacent dividers are stripped so the menu never has orphan separators.

## Compatibility

| Package | Supported versions |
| --- | --- |
| Filament | `^4.0` and `^5.0` |
| PHP | `^8.2` |

## Installation

```bash
composer require harvirsidhu/filament-action-overflow
```

Config is optional — the package works without publishing it.

```bash
php artisan vendor:publish --tag="filament-action-overflow-config"
```

```php
return [
    'primary_count' => 1,
    'label' => 'More',
    'icon' => \Filament\Support\Icons\Heroicon::EllipsisVertical,
    'color' => 'gray',
    'hidden_label' => false,
    'button' => true,
    'icon_position' => \Filament\Support\Enums\IconPosition::After,
    'filter_unauthorized' => false,
];
```

## Usage

### Native syntax via `ActionGroup::withOverflow()`

The package registers a `withOverflow(int $primary = 1)` macro on Filament's `ActionGroup`, so you can write plain Filament code:

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
}
```

`->withOverflow()` is a terminal call — it returns the composed `array<Action | ActionGroup>`, ready to be returned from `getHeaderActions()`, `getTableActions()`, `getRecordActions()`, etc.

By default the package promotes both the primary actions and the `More` dropdown trigger to Filament's button view, so a plain `withOverflow(1)` produces a row of matching buttons without the caller needing to add `->button()` to each action individually. Disable this with `->button(false)` (or set `'button' => false` in config) if the surrounding context prefers link-style actions (e.g., table row actions) — the composer will then leave each action's render view untouched.

### Fluent entry point

For full control over the More button's label, icon, color, and other presentation options, use the `ActionOverflow` facade:

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

### Dividers

Filament 5 renders a divider when you nest an `ActionGroup::make([...])->dropdown(false)` inside another `ActionGroup`. This package preserves those nested groups inside the overflow dropdown:

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

return ActionGroup::make([
    Action::make('edit'),
    Action::make('archive'),
    ActionGroup::make([
        Action::make('publish'),
        Action::make('unpublish'),
    ])->dropdown(false), // renders as a divider-section inside More
    Action::make('delete'),
    Action::make('download'),
])->withOverflow(1);
```

The composer automatically:
- skips dividers when counting toward `primaryCount` (dividers are logical groupings, not action slots);
- drops dividers that would land among side-by-side primary buttons (dividers only make sense inside a dropdown);
- strips leading / trailing dividers in the overflow so the menu never starts or ends with a separator;
- collapses adjacent dividers to one.

### Visibility filtering

```php
return ActionOverflow::make([
    Action::make('edit')->hidden(true),     // ignored
    Action::make('archive'),                // kept
    Action::make('delete')->visible(false), // ignored
    Action::make('publish'),                // kept
])->toActions();
```

### Opt-in authorization filtering

```php
return ActionOverflow::make($actions)
    ->filterUnauthorized() // default is false
    ->toActions();
```

### Full fluent API

```php
ActionOverflow::make($actions)
    ->primaryCount(int $count = 1)
    ->label(string $label = 'More')
    ->icon(string|\BackedEnum|null $icon = null)
    ->color(string $color = 'gray')
    ->hiddenLabel(bool $state = true)
    ->button(bool $state = true)
    ->iconPosition(\Filament\Support\Enums\IconPosition $position = \Filament\Support\Enums\IconPosition::After)
    ->filterUnauthorized(bool $state = true)
    ->toActions();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [harvirsidhu](https://github.com/harvirsidhu)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
