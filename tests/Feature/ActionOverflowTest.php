<?php

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;

enum FakeMoreIcon: string
{
    case EllipsisVertical = 'heroicon-m-ellipsis-vertical';
}

it('uses one primary action by default', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($actions[0])
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);
});

it('supports a custom primary action count', function (): void {
    $actions = makeActions(['view', 'edit', 'archive', 'delete']);

    $composed = ActionOverflow::make($actions)
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(3)
        ->and($composed[0])->toBe($actions[0])
        ->and($composed[1])->toBe($actions[1])
        ->and($composed[2])->toBeInstanceOf(ActionGroup::class);
});

it('does not add a more action when there is no overflow', function (): void {
    $actions = makeActions(['view', 'edit']);

    $composed = ActionOverflow::make($actions)
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($actions[0])
        ->and($composed[1])->toBe($actions[1]);
});

it('flattens a single overflow action', function (): void {
    $actions = makeActions(['view', 'edit']);

    $composed = ActionOverflow::make($actions)
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($actions[0])
        ->and($composed[1])->toBe($actions[1])
        ->and($composed[1])->not->toBeInstanceOf(ActionGroup::class);
});

it('groups multiple overflow actions under more', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);
});

it('supports configurable more presentation options', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->label('Options')
        ->icon('heroicon-m-bars-3')
        ->color('danger')
        ->toActions();

    /** @var ActionGroup $group */
    $group = $composed[1];

    expect(getConfiguredValue($group, ['label'], 'getLabel'))->toBe('Options')
        ->and(getConfiguredValue($group, ['icon'], 'getIcon'))->toBe('heroicon-m-bars-3')
        ->and(getConfiguredValue($group, ['color'], 'getColor'))->toBe('danger');
});

it('accepts a backed enum icon with moreIcon', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->icon(FakeMoreIcon::EllipsisVertical)
        ->toActions();

    /** @var ActionGroup $group */
    $group = $composed[1];

    expect(getConfiguredValue($group, ['icon'], 'getIcon'))
        ->toBe('heroicon-m-ellipsis-vertical');
});

it('accepts a Heroicon enum icon from config', function (): void {
    config()->set('action-overflow.icon', Heroicon::EllipsisVertical);

    $actions = makeActions(['view', 'edit', 'archive']);
    $composed = ActionOverflow::make($actions)->toActions();

    /** @var ActionGroup $group */
    $group = $composed[1];

    expect(getConfiguredValue($group, ['icon'], 'getIcon'))
        ->toBe('heroicon-m-ellipsis-vertical');
});

it('can hide the more label when supported by current filament version', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->hiddenLabel()
        ->toActions();

    /** @var ActionGroup $group */
    $group = $composed[1];

    if (method_exists($group, 'isLabelHidden')) {
        expect($group->isLabelHidden())->toBeTrue();

        return;
    }

    expect(getConfiguredValue($group, ['isLabelHidden']))->toBeTrue();
});

it('can configure icon position with right as default', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $default = ActionOverflow::make($actions)->toActions();
    $configured = ActionOverflow::make($actions)
        ->iconPosition(IconPosition::Before)
        ->toActions();

    /** @var ActionGroup $defaultGroup */
    $defaultGroup = $default[1];
    /** @var ActionGroup $configuredGroup */
    $configuredGroup = $configured[1];

    if (method_exists($defaultGroup, 'getIconPosition')) {
        expect(normalizeBackedEnumValue($defaultGroup->getIconPosition()))->toBe('after')
            ->and(normalizeBackedEnumValue($configuredGroup->getIconPosition()))->toBe('before');

        return;
    }

    expect(getConfiguredValue($defaultGroup, ['iconPosition']))->toBe('after')
        ->and(getConfiguredValue($configuredGroup, ['iconPosition']))->toBe('before');
});

it('accepts icon position as string values', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->iconPosition('before')
        ->toActions();

    /** @var ActionGroup $group */
    $group = $composed[1];

    if (method_exists($group, 'getIconPosition')) {
        expect(normalizeBackedEnumValue($group->getIconPosition()))->toBe('before');

        return;
    }

    expect(getConfiguredValue($group, ['iconPosition']))->toBe('before');
});

it('returns actions with toActions', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composer = ActionOverflow::make($actions)->primaryCount(1);

    expect($composer->toActions())->toHaveCount(2);
});

it('filters hidden actions before composing', function (): void {
    $hidden = makeFakeAction('hidden', hidden: true);
    $archive = makeFakeAction('archive');
    $delete = makeFakeAction('delete');

    $composed = ActionOverflow::make([$hidden, $archive, $delete])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($archive)
        ->and($composed[1])->toBe($delete);
});

it('filters invisible actions before composing', function (): void {
    $invisible = makeFakeAction('invisible', visible: false);
    $unauthorized = makeFakeAction('unauthorized', authorized: false);
    $view = makeFakeAction('view');

    $composed = ActionOverflow::make([$invisible, $unauthorized, $view])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($unauthorized)
        ->and($composed[1])->toBe($view);
});

it('can filter unauthorized actions when enabled', function (): void {
    $invisible = makeFakeAction('invisible', visible: false);
    $unauthorized = makeFakeAction('unauthorized', authorized: false);
    $view = makeFakeAction('view');

    $composed = ActionOverflow::make([$invisible, $unauthorized, $view])
        ->filterUnauthorized()
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(1)
        ->and($composed[0])->toBe($view);
});

it('preserves a divider between actions inside the overflow group', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $download = Action::make('download');
    $divider = makeDividerGroup([Action::make('publish'), Action::make('unpublish')]);

    $composed = ActionOverflow::make([$edit, $archive, $divider, $delete, $download])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(4);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[1]->hasDropdown())->toBeFalse();
    expect($overflowItems[2]->getName())->toBe('delete');
    expect($overflowItems[3]->getName())->toBe('download');
});

it('extracts divider children as primary actions when slots remain', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $divider, $archive])
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(3)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1]->getName())->toBe('publish')
        ->and($composed[2])->toBe($archive);
});

it('does not count a divider toward the primary action count', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $archive, $divider, $delete])
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(3)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1])->toBe($archive)
        ->and($composed[2])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[2];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('publish');
    expect($overflowItems[1]->getName())->toBe('delete');
});

it('unwraps a leading divider in the overflow preserving its children', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $divider, $archive, $delete])
        ->primaryCount(1)
        ->toActions();

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(3);
    expect($overflowItems[0]->getName())->toBe('publish');
    expect($overflowItems[1]->getName())->toBe('archive');
    expect($overflowItems[2]->getName())->toBe('delete');
});

it('preserves a trailing divider in the overflow as its own section', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $archive, $delete, $divider])
        ->primaryCount(1)
        ->toActions();

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(3);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1]->getName())->toBe('delete');
    expect($overflowItems[2])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[2]->hasDropdown())->toBeFalse();
    expect($overflowItems[2]->getActions()[0]->getName())->toBe('publish');
});

it('preserves adjacent dividers between content as distinct sections', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $dividerA = makeDividerGroup([Action::make('publish')]);
    $dividerB = makeDividerGroup([Action::make('feature')]);

    $composed = ActionOverflow::make([$edit, $archive, $dividerA, $dividerB, $delete])
        ->primaryCount(1)
        ->toActions();

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(4);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[1]->hasDropdown())->toBeFalse();
    expect($overflowItems[1]->getActions()[0]->getName())->toBe('publish');
    expect($overflowItems[2])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[2]->hasDropdown())->toBeFalse();
    expect($overflowItems[2]->getActions()[0]->getName())->toBe('feature');
    expect($overflowItems[3]->getName())->toBe('delete');
});

it('keeps two non-empty divider sections separate when both land in overflow', function (): void {
    $submit = Action::make('submit');
    $print = Action::make('print');
    $editing = makeDividerGroup([
        Action::make('discount'),
        Action::make('tax'),
        Action::make('rounding'),
    ]);
    $billing = makeDividerGroup([
        Action::make('change-billing'),
        Action::make('refresh'),
        Action::make('hold'),
    ]);

    $composed = ActionOverflow::make([$submit, $print, $editing, $billing])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($submit)
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(3);
    expect($overflowItems[0]->getName())->toBe('print');
    expect($overflowItems[1])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[1]->hasDropdown())->toBeFalse();
    expect(array_map(fn ($a) => $a->getName(), $overflowItems[1]->getActions()))
        ->toBe(['discount', 'tax', 'rounding']);
    expect($overflowItems[2])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[2]->hasDropdown())->toBeFalse();
    expect(array_map(fn ($a) => $a->getName(), $overflowItems[2]->getActions()))
        ->toBe(['change-billing', 'refresh', 'hold']);
});

it('unwraps dividers alongside other overflow actions preserving all children', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $divider, $archive])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('publish');
    expect($overflowItems[1]->getName())->toBe('archive');
});

it('promotes link-style primary actions to button view by default', function (): void {
    $view = Action::make('view')->link();
    $edit = Action::make('edit')->link();
    $archive = Action::make('archive')->link();
    $delete = Action::make('delete')->link();

    $composed = ActionOverflow::make([$view, $edit, $archive, $delete])
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(3);

    /** @var Action $primaryOne */
    $primaryOne = $composed[0];
    /** @var Action $primaryTwo */
    $primaryTwo = $composed[1];

    expect($primaryOne->isButton())->toBeTrue()
        ->and($primaryTwo->isButton())->toBeTrue()
        ->and($composed[2])->toBeInstanceOf(ActionGroup::class);
});

it('promotes a link-style flattened single overflow action to button view', function (): void {
    $view = Action::make('view')->link();
    $edit = Action::make('edit')->link();

    $composed = ActionOverflow::make([$view, $edit])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2);

    /** @var Action $flattened */
    $flattened = $composed[1];

    expect($flattened->isButton())->toBeTrue();
});

it('leaves primary render view untouched when button flag is disabled', function (): void {
    $view = Action::make('view')->link();
    $edit = Action::make('edit')->link();
    $archive = Action::make('archive')->link();

    $composed = ActionOverflow::make([$view, $edit, $archive])
        ->primaryCount(1)
        ->button(false)
        ->toActions();

    /** @var Action $primary */
    $primary = $composed[0];

    expect($primary->isButton())->toBeFalse();
});

it('splits a divider across primary and overflow when primaryCount is reached mid-divider', function (): void {
    $divider = makeDividerGroup([
        Action::make('submit'),
        Action::make('print'),
    ]);
    $change = Action::make('change');
    $refresh = Action::make('refresh');

    $composed = ActionOverflow::make([$divider, $change, $refresh])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('submit')
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(3);
    expect($overflowItems[0]->getName())->toBe('print');
    expect($overflowItems[1]->getName())->toBe('change');
    expect($overflowItems[2]->getName())->toBe('refresh');
});

it('extracts primary actions across multiple divider groups', function (): void {
    $divider1 = makeDividerGroup([Action::make('a1'), Action::make('a2')]);
    $divider2 = makeDividerGroup([Action::make('b1'), Action::make('b2')]);
    $solo = Action::make('solo');

    $composed = ActionOverflow::make([$divider1, $divider2, $solo])
        ->primaryCount(3)
        ->toActions();

    expect($composed)->toHaveCount(4)
        ->and($composed[0]->getName())->toBe('a1')
        ->and($composed[1]->getName())->toBe('a2')
        ->and($composed[2]->getName())->toBe('b1')
        ->and($composed[3])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[3];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('b2');
    expect($overflowItems[1]->getName())->toBe('solo');
});

it('drops a divider whose children are all extracted as primary', function (): void {
    $divider = makeDividerGroup([Action::make('only')]);
    $other = Action::make('other');

    $composed = ActionOverflow::make([$divider, $other])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('only')
        ->and($composed[1])->toBe($other);
});

it('drops unavailable divider children entirely so they never appear in primary or overflow', function (): void {
    $hidden = Action::make('hidden-child')->hidden();
    $visible = Action::make('visible-child');

    $divider = makeDividerGroup([$hidden, $visible]);
    $other1 = Action::make('other1');
    $other2 = Action::make('other2');

    $composed = ActionOverflow::make([$divider, $other1, $other2])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('visible-child')
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];

    expect(collect($moreGroup->getActions())->pluck('name')->all())
        ->not->toContain('hidden-child');
});

it('drops hidden divider children that would otherwise land in overflow', function (): void {
    $a = Action::make('a');
    $hidden = Action::make('hidden-overflow-child')->hidden();
    $visible = Action::make('visible-overflow-child');
    $divider = makeDividerGroup([$hidden, $visible]);

    $composed = ActionOverflow::make([$a, $divider, Action::make('z')])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('a')
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];

    $names = [];
    foreach ($moreGroup->getActions() as $item) {
        if ($item instanceof ActionGroup && ! $item->hasDropdown()) {
            foreach ($item->getActions() as $child) {
                $names[] = $child->getName();
            }

            continue;
        }

        $names[] = $item->getName();
    }

    expect($names)->not->toContain('hidden-overflow-child');
});

it('exposes a withOverflow macro on ActionGroup that matches direct composition', function (): void {
    $a = Action::make('edit');
    $b = Action::make('archive');
    $c = Action::make('delete');

    /** @var array<int, mixed> $composedViaMacro */
    $composedViaMacro = ActionGroup::make([$a, $b, $c])->withOverflow(1);

    expect($composedViaMacro)->toBeArray()
        ->and($composedViaMacro)->toHaveCount(2)
        ->and($composedViaMacro[0]->getName())->toBe('edit')
        ->and($composedViaMacro[1])->toBeInstanceOf(ActionGroup::class);

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composedViaMacro[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1]->getName())->toBe('delete');
});

it('withOverflow macro defaults primary count to 1', function (): void {
    $composed = ActionGroup::make([
        Action::make('edit'),
        Action::make('archive'),
        Action::make('delete'),
    ])->withOverflow();

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('edit')
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);
});

it('withOverflow macro supports dividers from nested dropdown(false) groups', function (): void {
    $composed = ActionGroup::make([
        Action::make('edit'),
        Action::make('archive'),
        ActionGroup::make([
            Action::make('publish'),
            Action::make('unpublish'),
        ])->dropdown(false),
        Action::make('delete'),
        Action::make('download'),
    ])->withOverflow(1);

    expect($composed)->toHaveCount(2)
        ->and($composed[0]->getName())->toBe('edit');

    /** @var ActionGroup $moreGroup */
    $moreGroup = $composed[1];
    $overflowItems = $moreGroup->getActions();

    expect($overflowItems)->toHaveCount(4);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[1]->hasDropdown())->toBeFalse();
    expect($overflowItems[2]->getName())->toBe('delete');
    expect($overflowItems[3]->getName())->toBe('download');
});

/**
 * @param  array<string>  $names
 * @return array<Action>
 */
function makeActions(array $names): array
{
    return array_map(static fn (string $name): Action => Action::make($name), $names);
}

function makeFakeAction(
    string $name,
    bool $hidden = false,
    ?bool $visible = true,
    ?bool $authorized = true,
): object {
    return new class($name, $hidden, $visible, $authorized)
    {
        public function __construct(
            public string $name,
            private bool $hidden,
            private ?bool $visible,
            private ?bool $authorized,
        ) {}

        public function isHidden(): bool
        {
            return $this->hidden;
        }

        public function isVisible(): bool
        {
            return $this->visible ?? true;
        }

        public function isAuthorized(): bool
        {
            return $this->authorized ?? true;
        }
    };
}

/**
 * @param  array<int, Action|ActionGroup>  $children
 */
function makeDividerGroup(array $children): ActionGroup
{
    return ActionGroup::make($children)->dropdown(false);
}

/**
 * @param  array<string>  $propertyNames
 */
function getConfiguredValue(object $target, array $propertyNames, ?string $getter = null): mixed
{
    if ($getter !== null && method_exists($target, $getter)) {
        return $target->{$getter}();
    }

    $reflection = new ReflectionClass($target);

    foreach ($propertyNames as $propertyName) {
        if (! $reflection->hasProperty($propertyName)) {
            continue;
        }

        $property = $reflection->getProperty($propertyName);

        return $property->getValue($target);
    }

    return null;
}

function normalizeBackedEnumValue(mixed $value): mixed
{
    if ($value instanceof BackedEnum) {
        return $value->value;
    }

    return $value;
}
