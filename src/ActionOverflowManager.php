<?php

namespace Harvirsidhu\FilamentActionOverflow;

class ActionOverflowManager
{
    /**
     * @param  array<mixed>  $actions
     */
    public function make(array $actions): ActionOverflow
    {
        return ActionOverflow::make($actions);
    }
}
