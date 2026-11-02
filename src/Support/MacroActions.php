<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow\Support;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Throwable;

/**
 * @internal The `withOverflow()` and `withSplit()` macros' shared steps.
 */
final class MacroActions
{
    /**
     * Whether the group a macro was called on hides itself, by its own
     * `hidden()` / `visible()`; then it hides all of its actions. A condition
     * that cannot be read hides nothing, as an action's does when the
     * actions are filtered.
     *
     * @param  Closure(): bool  $condition
     */
    public static function isHidden(Closure $condition): bool
    {
        try {
            return $condition();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Takes the actions out of the group a macro was called on, so they are
     * composed as if they had been listed directly. `ActionGroup::make()`
     * made that throwaway group their parent: left in it, Filament asks it
     * whether each action is hidden, and it answers through its
     * `getActions()`, which gives every action the dropdown-item view as its
     * default. It also gave every nested group the side-opening placement of
     * a submenu: as its default, or on Filament 4.0 outright, over the one it
     * had (so there it cannot be got back, and Filament picks again).
     *
     * @param  array<Action | ActionGroup>  $actions
     * @return array<Action | ActionGroup>
     */
    public static function release(array $actions): array
    {
        foreach ($actions as $action) {
            $action->group(null);

            if (! $action instanceof ActionGroup) {
                continue;
            }

            if (FilamentCompatibility::supportsDefaultDropdownPlacement($action)) {
                $action->defaultDropdownPlacement(null);
            } else {
                $action->dropdownPlacement(null);
            }
        }

        return $actions;
    }
}
