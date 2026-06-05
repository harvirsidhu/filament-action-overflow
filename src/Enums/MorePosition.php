<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow\Enums;

/**
 * Where the overflow control (the "More" group, or a flattened single
 * overflow action) sits relative to the primary actions in the returned
 * array. Filament renders the array in the reading direction, so `Start`
 * is direction-aware: it lands on the left in LTR and the right in RTL.
 */
enum MorePosition: string
{
    case Start = 'start';

    case End = 'end';
}
