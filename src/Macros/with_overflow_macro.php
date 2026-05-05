<?php

declare(strict_types=1);

use Filament\Actions\ActionGroup;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;

/*
 * File-scope closure for the `ActionGroup::withOverflow()` macro.
 *
 * Placing the closure at file scope keeps PHPStan from inferring $this
 * from a surrounding class instance, so the @var narrow below is the sole
 * type hint. At runtime Laravel's Macroable rebinds $this to the
 * ActionGroup instance the macro was invoked on.
 */
return function (int $primary = 1): array {
    /** @var ActionGroup $this */
    return ActionOverflow::make($this->getActions())
        ->primaryCount($primary)
        ->toActions();
};
