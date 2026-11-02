<?php

declare(strict_types=1);

use Filament\Actions\ActionGroup;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;
use Harvirsidhu\FilamentActionOverflow\Support\MacroActions;
use Livewire\Component;

/*
 * File-scope closure for the `ActionGroup::withOverflow()` macro.
 *
 * Placing the closure at file scope keeps PHPStan from inferring $this
 * from a surrounding class instance, so the @var narrow below is the sole
 * type hint. At runtime the macro is bound to the ActionGroup it was
 * invoked on, in that class's scope, which is what lets it read the
 * group's list and conditions as given.
 *
 * A group hidden by its own hidden() / visible() hides all of its actions.
 * Otherwise the actions are released from the group (MacroActions) and
 * composed as if listed directly: with the given count, or the configured
 * `primary_count`, and with a livewire() set on the group passed on, so
 * visibility closures get the page.
 */
return function (?int $primary = null): array {
    /** @var ActionGroup $this */
    if (MacroActions::isHidden(fn (): bool => $this->evaluate($this->isHidden) || (! $this->evaluate($this->isVisible)))) {
        return [];
    }

    $overflow = ActionOverflow::make(MacroActions::release($this->actions));

    if ($primary !== null) {
        $overflow->primaryCount($primary);
    }

    if (($livewire = $this->getLivewire()) instanceof Component) {
        $overflow->livewire($livewire);
    }

    return $overflow->toActions();
};
