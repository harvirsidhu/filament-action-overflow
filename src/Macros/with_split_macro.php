<?php

declare(strict_types=1);

use Filament\Actions\ActionGroup;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;
use Harvirsidhu\FilamentActionOverflow\Support\MacroActions;
use Livewire\Component;

/*
 * File-scope closure for the `ActionGroup::withSplit()` macro: the split
 * twin of `withOverflow()`. The group's first available action is the
 * button; the rest are its menu.
 *
 * Placing the closure at file scope keeps PHPStan from inferring $this
 * from a surrounding class instance, so the @var narrow below is the sole
 * type hint. At runtime the macro is bound to the ActionGroup it was
 * invoked on, in that class's scope.
 *
 * Like withOverflow(): a hidden group hides all of its actions, the
 * actions are released from the group, and a livewire() set on the group
 * is passed on.
 */
return function (): array {
    /** @var ActionGroup $this */
    if (MacroActions::isHidden(fn (): bool => $this->evaluate($this->isHidden) || (! $this->evaluate($this->isVisible)))) {
        return [];
    }

    $split = ActionOverflow::make(MacroActions::release($this->actions))->split();

    if (($livewire = $this->getLivewire()) instanceof Component) {
        $split->livewire($livewire);
    }

    return $split->toActions();
};
