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
}
