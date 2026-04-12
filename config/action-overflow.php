<?php

use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

return [
    'primary_count' => 1,
    'label' => 'More',
    'icon' => Heroicon::EllipsisVertical,
    'color' => 'gray',
    'hidden_label' => false,
    'button' => true,
    'icon_position' => IconPosition::After,
    'filter_unauthorized' => false,
];
