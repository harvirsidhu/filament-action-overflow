<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow;

final class ActionOverflowManager
{
    /**
     * @param  array<mixed>  $actions
     */
    public function make(array $actions): ActionOverflow
    {
        return ActionOverflow::make($actions);
    }
}
