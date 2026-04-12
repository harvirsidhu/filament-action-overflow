# Changelog

All notable changes to `filament-action-overflow` will be documented in this file.

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
