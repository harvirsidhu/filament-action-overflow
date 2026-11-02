<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow\Support;

final class FilamentCompatibility
{
    public static function defaultMoreIcon(): string
    {
        return 'heroicon-m-ellipsis-vertical';
    }

    public static function supportsHiddenLabel(object $component): bool
    {
        return method_exists($component, 'hiddenLabel');
    }

    /** The split button's menu trigger: a chevron, the usual sign of a menu. */
    public static function defaultSplitIcon(): string
    {
        return 'heroicon-m-chevron-down';
    }

    public static function supportsExtraDropdownAttributes(object $component): bool
    {
        return method_exists($component, 'extraDropdownAttributes');
    }

    public static function supportsDropdownPlacement(object $component): bool
    {
        return method_exists($component, 'dropdownPlacement');
    }

    /**
     * Filament 4.0 has none: a group made inside another is given the
     * placement `right-top` outright, over any it had.
     */
    public static function supportsDefaultDropdownPlacement(object $component): bool
    {
        return method_exists($component, 'defaultDropdownPlacement');
    }
}
