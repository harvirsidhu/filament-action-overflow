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
use Livewire\Component;
use Throwable;

final class ActionOverflow
{
    private bool $hasExplicitPrimaryCount = false;

    private bool $hasExplicitIcon = false;

    private bool $hasExplicitColor = false;

    private ?Component $livewire = null;

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
        private bool $split = false,
        private ?string $dropdownPlacement = null,
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
        $this->hasExplicitPrimaryCount = true;

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
        $this->hasExplicitIcon = $icon !== null;

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
        $this->hasExplicitColor = true;

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
     * Joins the main action and its menu into one control: a split button,
     * for one action and its variants. It has exactly one main action,
     * whatever `primary_count` the config sets (`toActions()` refuses a
     * `primaryCount()` other than 1): other buttons stand beside it. The
     * menu's trigger becomes an icon-only chevron in the main button's colour
     * (unless `icon()` or `color()` say otherwise), and a single leftover
     * action stays in the menu instead of becoming a second button. With
     * nothing left over there is only the main button.
     */
    public function split(bool $state = true): self
    {
        $this->split = $state;

        return $this;
    }

    /**
     * Which way the menu opens: Filament's `dropdownPlacement()`, passed to
     * the menu as is (`'bottom-start'`, `'bottom-end'`, `'top-end'`…). Unset,
     * the More menu keeps Filament's default for where it sits, and a split
     * button's menu opens below it along its outer edge.
     */
    public function dropdownPlacement(?string $placement): self
    {
        $this->dropdownPlacement = $placement;

        return $this;
    }

    /**
     * The Livewire component the actions belong to, usually the page
     * (`$this` in `getHeaderActions()`). Every action, and every action in a
     * divider, is attached to it before its visibility is read, so a
     * `visible()` or `hidden()` closure that reads `$livewire` or `$record`
     * gets them: Filament attaches a page's actions only after the page has
     * handed them over.
     */
    public function livewire(Component $livewire): self
    {
        $this->livewire = $livewire;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toActions(): array
    {
        if ($this->split && $this->hasExplicitPrimaryCount && $this->primaryCount !== 1) {
            throw new InvalidArgumentException('A split button has one main action. Put the other buttons beside it.');
        }

        $this->attachLivewire($this->actions);

        $availableActions = $this->filterAvailableActions($this->actions);

        [$primary, $overflow] = $this->partitionPrimaryAndOverflow(
            $availableActions,
            $this->split ? 1 : $this->primaryCount,
        );

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

        if ($this->split) {
            return [$this->makeSplitGroup($primary[0], $overflow)];
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
     * Attaches every action, and every action in a divider, to the component
     * given to `livewire()`. Attached directly, an action keeps it when the
     * steps below regroup it into a new divider or the More menu.
     *
     * @param  array<mixed>  $actions
     */
    private function attachLivewire(array $actions): void
    {
        if ($this->livewire === null) {
            return;
        }

        foreach ($actions as $action) {
            if (! is_object($action) || ! method_exists($action, 'livewire')) {
                continue;
            }

            $action->livewire($this->livewire);

            if ($action instanceof ActionGroup && $this->isDivider($action)) {
                $this->attachLivewire($action->getActions());
            }
        }
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
     * until `$primaryCount` real actions have been taken. When a divider
     * (ActionGroup with dropdown(false)) is encountered while still filling
     * primary slots, its available children are extracted toward the count;
     * any remaining children form a reconstructed divider in overflow.
     * Everything after the primary slice is full goes to overflow unchanged.
     *
     * @param  array<mixed>  $available
     * @return array{0: array<mixed>, 1: array<mixed>}
     */
    private function partitionPrimaryAndOverflow(array $available, int $primaryCount): array
    {
        $primary = [];
        $overflow = [];
        $primaryTaken = 0;

        foreach ($available as $item) {
            if ($primaryTaken >= $primaryCount) {
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

                if ($primaryTaken < $primaryCount) {
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

        if ($this->dropdownPlacement !== null && FilamentCompatibility::supportsDropdownPlacement($group)) {
            $group->dropdownPlacement($this->dropdownPlacement);
        }

        return $group;
    }

    /**
     * The split button: the main action and the menu side by side in
     * Filament's button group, the menu's trigger joined to the main button
     * by the package's stylesheet (`fi-ao-split-menu`).
     *
     * @param  array<mixed>  $overflow
     */
    private function makeSplitGroup(mixed $main, array $overflow): ActionGroup
    {
        $more = $this->makeMoreGroup($overflow)->button();

        $more->icon($this->hasExplicitIcon ? $this->icon : FilamentCompatibility::defaultSplitIcon())
            ->color($this->hasExplicitColor ? $this->color : $this->colorOf($main))
            ->tooltip($this->label);

        if (FilamentCompatibility::supportsHiddenLabel($more)) {
            $more->hiddenLabel();
        }

        if (FilamentCompatibility::supportsExtraDropdownAttributes($more)) {
            $more->extraDropdownAttributes(['class' => 'fi-ao-split-menu'], merge: true);
        }

        $group = ActionGroup::make($this->placeOverflow([$main], $more))->buttonGroup();

        // Inside a group Filament opens a dropdown to the side, as a submenu;
        // a split button's menu drops below, along its outer edge. Set once
        // the group is made: Filament 4.0 sets a nested group's placement
        // itself as the group is made, over the one it was given.
        if (FilamentCompatibility::supportsDropdownPlacement($more)) {
            $more->dropdownPlacement($this->dropdownPlacement ?? ($this->morePosition === MorePosition::Start ? 'bottom-start' : 'bottom-end'));
        }

        return $group;
    }

    /**
     * The colour the main button is drawn in: its own, or Filament's
     * default for a button, primary.
     */
    private function colorOf(mixed $action): string
    {
        $color = is_object($action) && method_exists($action, 'getColor') ? $action->getColor() : null;

        return is_string($color) ? $color : 'primary';
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
