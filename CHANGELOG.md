# Changelog

All notable changes to `filament-action-overflow` will be documented in this file.

## Unreleased

### Added
- `ActionOverflow::split()` joins the main action and its **More** menu into one control: a split button, for one action and its variants. It has exactly one main action, whatever `primary_count` the config sets (`toActions()` throws if `primaryCount()` asks for another). The menu's trigger becomes an icon-only chevron in the main button's colour (unless `icon()` or `color()` are set), a single leftover action stays in the menu, the menu opens below the button along its outer edge, and `morePosition(MorePosition::Start)` puts the menu first.
- `ActionGroup::withSplit()`, the one-line form of a split button, like `withOverflow()`.
- `ActionOverflow::dropdownPlacement()`, Filament's own placement (`'bottom-start'`, `'bottom-end'`, `'top-end'`…) for the menu, in both modes. Unset, the **More** menu keeps Filament's default for where it sits.
- `ActionOverflow::livewire()` attaches every action, divider children included, to the page before their visibility is read, so a `visible()` / `hidden()` closure that reads `$livewire` or `$record` gets them. Filament attaches a page's header actions only after `getHeaderActions()` returns, so until now such a closure got `null`. The macros pass on a `livewire()` set on the group.
- A small stylesheet, registered with Filament's asset manager, that joins the split button's two halves. It is published with the app's other Filament assets by `php artisan filament:assets`.

### Fixed
- `withOverflow()` called without a count uses the configured `primary_count`, as the README said, instead of always `1`.
- The macros compose the group's actions as if they had been listed directly. The group they were listed in stayed their parent, and through it Filament gave every action its dropdown-item view as its default (so with `button => false` the actions out front were drawn as menu rows), and had a nested group out front open to the side, as a submenu. A group hidden by its own `hidden()` / `visible()` still hides all of its actions.

### Docs
- README: three ways to use it (two macros and the builder); the table methods of Filament 4 and 5 (`recordActions()`, `headerActions()`, `toolbarActions()`) in place of ones they don't have; the split stylesheet under Installation; what the config does to a split button; the split menu's label as its tooltip and screen-reader name; visibility that depends on the page or the record, and why a table can't vary by row.

## 1.1.2 - 2026-07-21

### Security
- Added `SECURITY.md` documenting supported versions, private disclosure channels, and response timelines.
- Added Dependabot config for `composer` and `github-actions` updates, with a cooldown window so freshly published releases are not adopted immediately.

### Added
- CI workflows: Pest across PHP 8.2/8.3/8.4, plus PHPStan and Pint. All third-party actions are pinned to full commit SHAs.

### Notes
- No library code changed in this release; it contains repository and supply-chain hardening only. `.github/` is `export-ignore`d, so the distributed archive gains only `SECURITY.md`.

## 1.1.1 - 2026-06-06

### Added
- `ActionOverflow::morePosition()` and a `MorePosition` enum (`Start` / `End`) to place the **More** control before or after the primary actions. Accepts the enum or the string forms `'start'` / `'end'`, and is configurable via the new `more_position` config key (default `end`).
- The position also applies when overflow collapses to a single flattened action, not just the grouped **More** trigger.

### Notes
- `Start` is direction-aware: composition only reorders the action array and Filament renders it in the reading direction, so it lands left in LTR and flips to the right under RTL — no extra RTL configuration needed.

## 1.1.0 - 2026-05-05

### Fixed
- Hidden / invisible / (when enabled) unauthorized children of `dropdown(false)` divider groups now never appear in the **More** menu. `filterAvailableActions()` recurses into dividers and drops dividers whose children are all unavailable.
- Two non-empty `dropdown(false)` groups that both land in overflow are now preserved as separate sections instead of being collapsed into one. The "adjacent dividers" rule and the "trailing divider" unwrap were too aggressive and discarded user-authored separators.
- The "single overflow item gets flattened" optimization no longer counts dividers as having zero actions — divider children are tallied, so a flat list with only divider sections renders correctly under **More**.
- The published config (`config/action-overflow.php`) no longer references Filament 5-only enum constants. Defaults are stored as strings so the file loads on both Filament 4 and 5.

### Changed
- Sanitizer behavior simplified: only a *leading* divider is unwrapped (an orphan separator above the first item). Trailing and adjacent dividers are preserved as distinct sections, matching Filament's native rendering of multiple `dropdown(false)` groups.
- Internal API tightened: `ActionOverflow`, `ActionOverflowManager`, and `FilamentCompatibility` are now `final`. Properties and helper methods are `private`. `declare(strict_types=1);` added across the package.
- Removed unused `resources/lang/en/action-overflow.php` and the corresponding `hasTranslations()` registration.
- Cleaned up `composer.json` `allow-plugins` entries that were never required.

### Docs
- README rewritten for clarity with a visual example, simpler structure, and a reference table for config keys.

## 1.0.3 - 2026-04-12

- Fixed missing trailing newline in service provider for consistent code style.

## 1.0.1 - 2026-04-12

- Primary actions and the More trigger are now promoted to Filament's button view by default (`button => true`), so `withOverflow(1)` produces matching buttons without per-action `->button()` calls. Disable with `->button(false)` or config.
- Divider groups (`ActionGroup::dropdown(false)`) are now transparent to primary extraction: children are extracted toward `primaryCount`, remaining children form a reconstructed divider in overflow.
- Overflow divider sanitization now unwraps leading/trailing/collapsed dividers instead of dropping them, so no actions are silently lost.

## 1.0.0 - 2026-04-12

- Initial release. Previously published as `harvirsidhu/filament-header-actions`.
- Added `ActionOverflow` composer for deterministic primary + overflow action composition.
- Added `ActionGroup::withOverflow(int $primary = 1)` macro for native Filament syntax.
- Added divider support: nested `ActionGroup::make([...])->dropdown(false)` entries are preserved inside the overflow dropdown, with leading/trailing/adjacent dividers sanitized.
- Filament 4 and 5 compatible via `FilamentCompatibility` helper.
