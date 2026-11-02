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
}
