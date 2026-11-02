<?php

declare(strict_types=1);

use Filament\Actions\ActionGroup;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;

/*
 * File-scope closure for the `ActionGroup::withSplit()` macro: the split
 * twin of `withOverflow()`. The group's first available action is the
 * button; the rest are its menu.
 *
 * Placing the closure at file scope keeps PHPStan from inferring $this
 * from a surrounding class instance, so the @var narrow below is the sole
 * type hint. At runtime Laravel's Macroable rebinds $this to the
 * ActionGroup instance the macro was invoked on.
 */
return function (): array {
    /** @var ActionGroup $this */
    return ActionOverflow::make($this->getActions())
        ->split()
        ->toActions();
};
