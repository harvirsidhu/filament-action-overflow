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

it('drops a divider that would appear between primary actions', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $divider, $archive])
        ->primaryCount(2)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1])->toBe($archive);
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
        ->and($composed[2])->toBe($delete)
        ->and($composed[2])->not->toBeInstanceOf(ActionGroup::class);
});

it('strips a leading divider from the overflow', function (): void {
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

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1]->getName())->toBe('delete');
});

it('strips a trailing divider from the overflow', function (): void {
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

    expect($overflowItems)->toHaveCount(2);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1]->getName())->toBe('delete');
});

it('collapses adjacent dividers to a single one', function (): void {
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

    expect($overflowItems)->toHaveCount(3);
    expect($overflowItems[0]->getName())->toBe('archive');
    expect($overflowItems[1])->toBeInstanceOf(ActionGroup::class);
    expect($overflowItems[1]->hasDropdown())->toBeFalse();
    expect($overflowItems[2]->getName())->toBe('delete');
});

it('flattens a single real overflow action even when accompanied by dividers', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $divider = makeDividerGroup([Action::make('publish')]);

    $composed = ActionOverflow::make([$edit, $divider, $archive])
        ->primaryCount(1)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($edit)
        ->and($composed[1])->toBe($archive)
        ->and($composed[1])->not->toBeInstanceOf(ActionGroup::class);
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
