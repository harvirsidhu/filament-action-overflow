<?php

namespace Harvirsidhu\FilamentActionOverflow\Support;

class FilamentCompatibility
{
    public static function defaultMoreIcon(): string
    {
        return 'heroicon-m-ellipsis-vertical';
    }

    public static function supportsHiddenLabel(object $component): bool
    {
        return method_exists($component, 'hiddenLabel');
    }
}
