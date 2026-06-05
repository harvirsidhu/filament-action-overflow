# Changelog

All notable changes to `filament-action-overflow` will be documented in this file.

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
