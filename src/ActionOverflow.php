<?php

declare(strict_types=1);

namespace Harvirsidhu\FilamentActionOverflow;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentActionOverflow\Enums\MorePosition;
use Harvirsidhu\FilamentActionOverflow\Support\FilamentCompatibility;
use InvalidArgumentException;
use Throwable;

final class ActionOverflow
{
    /**
     * @param  array<mixed>  $actions
     */
    public function __construct(
        private readonly array $actions,
        private int $primaryCount = 1,
        private string $label = 'More',
        private ?string $icon = null,
        private string $color = 'gray',
        private bool $hiddenLabel = false,
        private bool $button = true,
        private IconPosition $iconPosition = IconPosition::After,
        private bool $filterUnauthorized = false,
        private MorePosition $morePosition = MorePosition::End,
    ) {
        $this->icon ??= $this->resolveDefaultMoreIcon();
    }

    /**
     * @param  array<mixed>  $actions
     */
    public static function make(array $actions): self
    {
        return new self(
            actions: $actions,
            primaryCount: (int) config('action-overflow.primary_count', 1),
            label: (string) config('action-overflow.label', 'More'),
            icon: self::normalizeIcon(config('action-overflow.icon')),
            color: (string) config('action-overflow.color', 'gray'),
            hiddenLabel: (bool) config('action-overflow.hidden_label', false),
            button: (bool) config('action-overflow.button', true),
            iconPosition: self::normalizeIconPosition(config('action-overflow.icon_position', IconPosition::After)),
            filterUnauthorized: (bool) config('action-overflow.filter_unauthorized', false),
            morePosition: self::normalizeMorePosition(config('action-overflow.more_position', MorePosition::End)),
        );
    }

    public function primaryCount(int $count = 1): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Primary count cannot be negative.');
        }

        $this->primaryCount = $count;

        return $this;
    }

    public function label(string $label = 'More'): self
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string | BackedEnum | null $icon = null): self
    {
        $this->icon = self::normalizeIcon($icon) ?? $this->resolveDefaultMoreIcon();

        return $this;
    }

    public function hiddenLabel(bool $state = true): self
    {
        $this->hiddenLabel = $state;

        return $this;
    }

    public function color(string $color = 'gray'): self
    {
        $this->color = $color;

        return $this;
    }

    public function button(bool $state = true): self
    {
        $this->button = $state;

        return $this;
    }

    public function iconPosition(IconPosition | string | BackedEnum | null $position = IconPosition::After): self
    {
        $this->iconPosition = self::normalizeIconPosition($position);

        return $this;
    }

    public function filterUnauthorized(bool $state = true): self
    {
        $this->filterUnauthorized = $state;

        return $this;
    }

    public function morePosition(MorePosition | string $position = MorePosition::End): self
    {
        $this->morePosition = self::normalizeMorePosition($position);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toActions(): array
    {
        $availableActions = $this->filterAvailableActions($this->actions);

        [$primary, $overflow] = $this->partitionPrimaryAndOverflow($availableActions);

        $primary = array_map($this->promoteToButton(...), $primary);

        $overflow = $this->sanitizeOverflowDividers($overflow);

        $overflowActionCount = 0;
        $onlyAction = null;
        foreach ($overflow as $item) {
            if ($this->isDivider($item)) {
                foreach ($item->getActions() as $child) {
                    $overflowActionCount++;
                    $onlyAction ??= $child;
                }

                continue;
            }

            $overflowActionCount++;
            $onlyAction ??= $item;
        }

        if ($overflowActionCount === 0) {
            return $primary;
        }

        if ($overflowActionCount === 1) {
            return $this->placeOverflow($primary, $this->promoteToButton($onlyAction));
        }

        return $this->placeOverflow($primary, $this->makeMoreGroup($overflow));
    }

    /**
     * Positions the overflow control (the "More" group, or a flattened
     * single overflow action) before or after the primary actions, per
     * the configured `morePosition`.
     *
     * @param  array<mixed>  $primary
     * @return array<mixed>
     */
    private function placeOverflow(array $primary, mixed $overflow): array
    {
        if ($this->morePosition === MorePosition::Start) {
            return [$overflow, ...$primary];
        }

        return [...$primary, $overflow];
    }

    private function promoteToButton(mixed $action): mixed
    {
        if (! $this->button) {
            return $action;
        }

        if (is_object($action) && method_exists($action, 'button')) {
            $action->button();
        }

        return $action;
    }

    /**
     * Filters out hidden / invisible / (optionally) unauthorized actions,
     * recursing one level into divider groups so unavailable children never
     * leak through — whether the divider ends up in primary or overflow.
     * Dividers whose children are all unavailable are dropped entirely.
     *
     * @param  array<mixed>  $actions
     * @return array<mixed>
     */
    private function filterAvailableActions(array $actions): array
    {
        $result = [];

        foreach ($actions as $action) {
            if (! $this->isActionAvailable($action)) {
                continue;
            }

            if ($this->isDivider($action)) {
                $availableChildren = array_values(array_filter(
                    $action->getActions(),
                    $this->isActionAvailable(...),
                ));

                if ($availableChildren === []) {
                    continue;
                }

                $result[] = ActionGroup::make($availableChildren)->dropdown(false);

                continue;
            }

            $result[] = $action;
        }

        return $result;
    }

    private function isActionAvailable(mixed $action): bool
    {
        if (! is_object($action)) {
            return true;
        }

        $isHidden = $this->resolveBooleanMethodResult($action, ['isHidden']);
        if ($isHidden === true) {
            return false;
        }

        $isVisible = $this->resolveBooleanMethodResult($action, ['isVisible']);
        if ($isVisible === false) {
            return false;
        }

        if ($this->filterUnauthorized) {
            $isAuthorized = $this->resolveBooleanMethodResult($action, ['isAuthorized']);
            if ($isAuthorized === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string>  $methodNames
     */
    private function resolveBooleanMethodResult(object $target, array $methodNames): ?bool
    {
        foreach ($methodNames as $methodName) {
            if (! method_exists($target, $methodName)) {
                continue;
            }

            try {
                $result = $target->{$methodName}();
            } catch (Throwable) {
                return null;
            }

            return is_bool($result) ? $result : null;
        }

        return null;
    }

    /**
     * Walks the filtered list left to right, collecting items as primary
     * until `primaryCount` real actions have been taken. When a divider
     * (ActionGroup with dropdown(false)) is encountered while still filling
     * primary slots, its available children are extracted toward primaryCount;
     * any remaining children form a reconstructed divider in overflow.
     * Everything after the primary slice is full goes to overflow unchanged.
     *
     * @param  array<mixed>  $available
     * @return array{0: array<mixed>, 1: array<mixed>}
     */
    private function partitionPrimaryAndOverflow(array $available): array
    {
        $primary = [];
        $overflow = [];
        $primaryTaken = 0;

        foreach ($available as $item) {
            if ($primaryTaken >= $this->primaryCount) {
                $overflow[] = $item;

                continue;
            }

            if (! $this->isDivider($item)) {
                $primary[] = $item;
                $primaryTaken++;

                continue;
            }

            $remaining = [];

            foreach ($item->getActions() as $child) {
                if (! $this->isActionAvailable($child)) {
                    continue;
                }

                if ($primaryTaken < $this->primaryCount) {
                    $primary[] = $child;
                    $primaryTaken++;
                } else {
                    $remaining[] = $child;
                }
            }

            if ($remaining !== []) {
                $overflow[] = ActionGroup::make($remaining)->dropdown(false);
            }
        }

        return [$primary, $overflow];
    }

    /**
     * Strips a leading divider from the overflow — the divider line above
     * the very first item has no section above it to separate from, so it
     * would render as a visually orphaned line at the top of the dropdown.
     * Its children are unwrapped in place so no actions are silently lost.
     * Trailing and adjacent dividers between content are preserved, matching
     * Filament's native rendering of multiple `dropdown(false)` groups.
     *
     * @param  array<mixed>  $overflow
     * @return array<mixed>
     */
    private function sanitizeOverflowDividers(array $overflow): array
    {
        $result = [];

        foreach ($overflow as $item) {
            if ($result === [] && $this->isDivider($item)) {
                foreach ($item->getActions() as $child) {
                    $result[] = $child;
                }

                continue;
            }

            $result[] = $item;
        }

        return array_values($result);
    }

    private function isDivider(mixed $item): bool
    {
        if (! $item instanceof ActionGroup) {
            return false;
        }

        try {
            return $item->hasDropdown() === false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<mixed>  $overflow
     */
    private function makeMoreGroup(array $overflow): ActionGroup
    {
        $group = ActionGroup::make($overflow)
            ->label($this->label)
            ->color($this->color);

        if ($this->button) {
            $group->button();
        }

        if ($this->icon !== null) {
            $group->icon($this->icon);
            $group->iconPosition($this->iconPosition);
        }

        if ($this->hiddenLabel && FilamentCompatibility::supportsHiddenLabel($group)) {
            $group->hiddenLabel();
        }

        return $group;
    }

    private function resolveDefaultMoreIcon(): string
    {
        return FilamentCompatibility::defaultMoreIcon();
    }

    private static function normalizeIcon(mixed $icon): ?string
    {
        if ($icon === null) {
            return null;
        }

        if (is_string($icon)) {
            return $icon;
        }

        if ($icon instanceof ScalableIcon) {
            return $icon->getIconForSize(IconSize::Medium);
        }

        if ($icon instanceof BackedEnum) {
            return (string) $icon->value;
        }

        throw new InvalidArgumentException('More icon must be a string, backed enum, or null.');
    }

    private static function normalizeIconPosition(mixed $position): IconPosition
    {
        if ($position === null) {
            return IconPosition::After;
        }

        if ($position instanceof IconPosition) {
            return $position;
        }

        if ($position instanceof BackedEnum) {
            $value = $position->value;

            if (is_string($value) && IconPosition::tryFrom($value) !== null) {
                return IconPosition::from($value);
            }
        }

        if (is_string($position) && IconPosition::tryFrom($position) !== null) {
            return IconPosition::from($position);
        }

        throw new InvalidArgumentException('Icon position must be a Filament IconPosition enum value.');
    }

    private static function normalizeMorePosition(mixed $position): MorePosition
    {
        if ($position instanceof MorePosition) {
            return $position;
        }

        if (is_string($position) && MorePosition::tryFrom($position) !== null) {
            return MorePosition::from($position);
        }

        throw new InvalidArgumentException("More position must be a MorePosition enum value or one of 'start', 'end'.");
    }
}
