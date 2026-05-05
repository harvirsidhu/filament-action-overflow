<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow\Facades;

use Harvirsidhu\FilamentActionOverflow\ActionOverflowManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see ActionOverflowManager
 *
 * @method static \Harvirsidhu\FilamentActionOverflow\ActionOverflow make(array $actions)
 */
class ActionOverflow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActionOverflowManager::class;
    }
}
