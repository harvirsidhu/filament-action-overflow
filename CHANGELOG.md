# Changelog

All notable changes to `filament-action-overflow` will be documented in this file.

## 1.0.0 - 2026-04-12

- Initial release. Previously published as `harvirsidhu/filament-header-actions`.
- Added `ActionOverflow` composer for deterministic primary + overflow action composition.
- Added `ActionGroup::withOverflow(int $primary = 1)` macro for native Filament syntax.
- Added divider support: nested `ActionGroup::make([...])->dropdown(false)` entries are preserved inside the overflow dropdown, with leading/trailing/adjacent dividers sanitized.
- Filament 4 and 5 compatible via `FilamentCompatibility` helper.
