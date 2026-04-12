<?php

namespace Harvirsidhu\FilamentActionOverflow;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Harvirsidhu\FilamentActionOverflow\Support\FilamentCompatibility;
use InvalidArgumentException;
use Throwable;

class ActionOverflow
{
    /**
     * @param  array<mixed>  $actions
     */
    final public function __construct(
        protected array $actions,
        protected int $primaryCount = 1,
        protected string $label = 'More',
        protected ?string $icon = null,
        protected string $color = 'gray',
        protected bool $hiddenLabel = false,
        protected bool $button = true,
        protected IconPosition $iconPosition = IconPosition::After,
        protected bool $filterUnauthorized = false,
    ) {
        $this->icon ??= $this->resolveDefaultMoreIcon();
    }

    /**
     * @param  array<mixed>  $actions
     */
    public static function make(array $actions): static
    {
        return new static(
            actions: $actions,
            primaryCount: (int) config('action-overflow.primary_count', 1),
            label: (string) config('action-overflow.label', 'More'),
            icon: static::normalizeIcon(config('action-overflow.icon')),
            color: (string) config('action-overflow.color', 'gray'),
            hiddenLabel: (bool) config('action-overflow.hidden_label', false),
            button: (bool) config('action-overflow.button', true),
            iconPosition: static::normalizeIconPosition(config('action-overflow.icon_position', IconPosition::After)),
            filterUnauthorized: (bool) config('action-overflow.filter_unauthorized', false),
        );
    }

    public function primaryCount(int $count = 1): static
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Primary count cannot be negative.');
        }

        $this->primaryCount = $count;

        return $this;
    }

    public function label(string $label = 'More'): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string | BackedEnum | null $icon = null): static
    {
        $this->icon = static::normalizeIcon($icon) ?? $this->resolveDefaultMoreIcon();

        return $this;
    }

    public function hiddenLabel(bool $state = true): static
    {
        $this->hiddenLabel = $state;

        return $this;
    }

    public function color(string $color = 'gray'): static
    {
        $this->color = $color;

        return $this;
    }

    public function button(bool $state = true): static
    {
        $this->button = $state;

        return $this;
    }

    public function iconPosition(IconPosition | string | BackedEnum | null $position = IconPosition::After): static
    {
        $this->iconPosition = static::normalizeIconPosition($position);

        return $this;
    }

    public function filterUnauthorized(bool $state = true): static
    {
        $this->filterUnauthorized = $state;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toActions(): array
    {
        $availableActions = $this->filterAvailableActions($this->actions);

        [$primary, $overflow] = $this->partitionPrimaryAndOverflow($availableActions);

        $overflow = $this->sanitizeOverflowDividers($overflow);

        $overflowActionCount = 0;
        foreach ($overflow as $item) {
            if (! $this->isDivider($item)) {
                $overflowActionCount++;
            }
        }

        if ($overflowActionCount === 0) {
            return $primary;
        }

        if ($overflowActionCount === 1) {
            $onlyAction = null;
            foreach ($overflow as $item) {
                if (! $this->isDivider($item)) {
                    $onlyAction = $item;

                    break;
                }
            }

            return [...$primary, $onlyAction];
        }

        return [...$primary, $this->makeMoreGroup($overflow)];
    }

    /**
     * @param  array<mixed>  $actions
     * @return array<mixed>
     */
    protected function filterAvailableActions(array $actions): array
    {
        return array_values(array_filter(
            $actions,
            fn (mixed $action): bool => $this->isActionAvailable($action),
        ));
    }

    protected function isActionAvailable(mixed $action): bool
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
    protected function resolveBooleanMethodResult(object $target, array $methodNames): ?bool
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
     * Walks the filtered list left to right, collecting non-divider items as
     * primary until `primaryCount` real actions have been taken. Dividers
     * encountered before the primary slice is full are dropped (they have no
     * visual meaning between side-by-side primary buttons). Everything after
     * the primary slice — actions and dividers — becomes the overflow list.
     *
     * @param  array<mixed>  $available
     * @return array{0: array<mixed>, 1: array<mixed>}
     */
    protected function partitionPrimaryAndOverflow(array $available): array
    {
        $primary = [];
        $overflow = [];
        $primaryTaken = 0;

        foreach ($available as $item) {
            if ($primaryTaken < $this->primaryCount) {
                if ($this->isDivider($item)) {
                    continue;
                }

                $primary[] = $item;
                $primaryTaken++;

                continue;
            }

            $overflow[] = $item;
        }

        return [$primary, $overflow];
    }

    /**
     * Drops leading + trailing dividers and collapses runs of adjacent dividers
     * to one, so the overflow dropdown never renders an orphan separator.
     *
     * @param  array<mixed>  $overflow
     * @return array<mixed>
     */
    protected function sanitizeOverflowDividers(array $overflow): array
    {
        $result = [];
        $previousWasDivider = true;

        foreach ($overflow as $item) {
            $isDivider = $this->isDivider($item);

            if ($isDivider && $previousWasDivider) {
                continue;
            }

            $result[] = $item;
            $previousWasDivider = $isDivider;
        }

        while ($result !== [] && $this->isDivider(end($result))) {
            array_pop($result);
        }

        return array_values($result);
    }

    protected function isDivider(mixed $item): bool
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
    protected function makeMoreGroup(array $overflow): ActionGroup
    {
        $group = ActionGroup::make($overflow)
            ->label($this->label)
            ->color($this->color);

        if ($this->button) {
            $group->button();
        }

        if ($this->icon !== null) {
            $group->icon($this->icon);
        }

        $group->iconPosition($this->iconPosition);

        if ($this->hiddenLabel && FilamentCompatibility::supportsHiddenLabel($group)) {
            $group->hiddenLabel();
        }

        return $group;
    }

    protected function resolveDefaultMoreIcon(): string
    {
        return FilamentCompatibility::defaultMoreIcon();
    }

    protected static function normalizeIcon(mixed $icon): ?string
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

    protected static function normalizeIconPosition(mixed $position): IconPosition
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
}
