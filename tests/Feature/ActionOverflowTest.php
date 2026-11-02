<?php

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Harvirsidhu\FilamentActionOverflow\ActionOverflow;
use Harvirsidhu\FilamentActionOverflow\Enums\MorePosition;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

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

it('labels the more menu "More" in English', function (): void {
    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->toActions()[1];

    expect($group->getLabel())->toBe('More');
});

it('labels the more menu in the app\'s language', function (): void {
    app()->setLocale('ms');

    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->toActions()[1];
    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make(makeActions(['callNext', 'counterOne']))->split()->toActions()[0]->getActions()[1];

    expect($group->getLabel())->toBe('Lagi')
        ->and($menu->getTooltip())->toBe('Lagi');
});

it('uses a label given in code as given, and label() goes back to the translated default', function (): void {
    app()->setLocale('ms');
    app('translator')->addLines(['*.Options' => 'Pilihan'], 'ms');

    /** @var ActionGroup $given */
    $given = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->label('Options')->toActions()[1];
    /** @var ActionGroup $reset */
    $reset = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->label('Options')->label()->toActions()[1];

    expect($given->getLabel())->toBe('Options')
        ->and($reset->getLabel())->toBe('Lagi');
});

it('translates a label set in the config from the app\'s own lang files', function (): void {
    app()->setLocale('ms');
    app('translator')->addLines(['*.Manage' => 'Urus'], 'ms');
    config()->set('action-overflow.label', 'Manage');

    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->toActions()[1];

    expect($group->getLabel())->toBe('Urus');
});

it('treats "More" in an older published config as the translated default', function (): void {
    app()->setLocale('ms');
    config()->set('action-overflow.label', 'More');

    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->toActions()[1];

    expect($group->getLabel())->toBe('Lagi');
});

it('has the more label in every language it ships', function (): void {
    $files = glob(dirname(__DIR__, 2) . '/resources/lang/*/action-overflow.php') ?: [];

    expect(count($files))->toBeGreaterThan(40);

    foreach ($files as $file) {
        $lines = require $file;

        expect($lines)->toHaveKey('more')
            ->and($lines['more'])->toBeString()->not->toBe('');
    }

    expect(trans('filament-action-overflow::action-overflow.more', [], 'en'))->toBe('More')
        ->and(trans('filament-action-overflow::action-overflow.more', [], 'zh_CN'))->toBe('更多');
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

it('appends the more group after primary actions by default', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($actions[0])
        ->and($composed[1])->toBeInstanceOf(ActionGroup::class);
});

it('places the more group before primary actions when morePosition is start', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->morePosition(MorePosition::Start)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBeInstanceOf(ActionGroup::class)
        ->and($composed[1])->toBe($actions[0]);
});

it('places a flattened single overflow action before primary when morePosition is start', function (): void {
    $actions = makeActions(['view', 'edit']);

    $composed = ActionOverflow::make($actions)
        ->morePosition(MorePosition::Start)
        ->toActions();

    expect($composed)->toHaveCount(2)
        ->and($composed[0])->toBe($actions[1])
        ->and($composed[1])->toBe($actions[0]);
});

it('accepts more position as a string value', function (): void {
    $actions = makeActions(['view', 'edit', 'archive']);

    $composed = ActionOverflow::make($actions)
        ->morePosition('start')
        ->toActions();

    expect($composed[0])->toBeInstanceOf(ActionGroup::class)
        ->and($composed[1])->toBe($actions[0]);
});

it('reads more position from config', function (): void {
    config()->set('action-overflow.more_position', 'start');

    $actions = makeActions(['view', 'edit', 'archive']);
    $composed = ActionOverflow::make($actions)->toActions();

    expect($composed[0])->toBeInstanceOf(ActionGroup::class)
        ->and($composed[1])->toBe($actions[0]);
});

it('rejects an invalid more position string', function (): void {
    ActionOverflow::make(makeActions(['view', 'edit', 'archive']))
        ->morePosition('center');
})->throws(InvalidArgumentException::class);

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

it('attaches the actions to the page before reading their visibility', function (): void {
    $page = new class extends Component {};
    $publish = Action::make('publish')->visible(fn ($livewire): bool => $livewire !== null);

    $withoutPage = ActionOverflow::make([Action::make('edit'), $publish])->toActions();
    $withPage = ActionOverflow::make([Action::make('edit'), $publish])->livewire($page)->toActions();

    expect($withoutPage)->toHaveCount(1)
        ->and($withPage)->toHaveCount(2)
        ->and($withPage[1])->toBe($publish)
        ->and($publish->getLivewire())->toBe($page);
});

it('attaches divider children to the page so they keep their place', function (): void {
    $page = new class extends Component {};
    $publish = Action::make('publish')->visible(fn ($livewire): bool => $livewire !== null);

    $composed = ActionOverflow::make([
        makeDividerGroup([$publish, Action::make('archive'), Action::make('delete')]),
    ])->livewire($page)->toActions();

    expect($composed[0])->toBe($publish);
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

it('withOverflow macro uses the configured primary count when given none', function (): void {
    config()->set('action-overflow.primary_count', 2);

    $composed = ActionGroup::make(makeActions(['edit', 'archive', 'publish', 'delete']))->withOverflow();

    expect($composed)->toHaveCount(3)
        ->and($composed[0]->getName())->toBe('edit')
        ->and($composed[1]->getName())->toBe('archive')
        ->and($composed[2])->toBeInstanceOf(ActionGroup::class);
});

it('withOverflow macro leaves each action its own view', function (): void {
    config()->set('action-overflow.button', false);

    $composed = ActionGroup::make(makeActions(['edit', 'archive']))->withOverflow();
    $ownView = Action::make('reference')->getView();

    expect($composed[0]->getView())->toBe($ownView)
        ->and($composed[1]->getView())->toBe($ownView);
});

it('withOverflow macro leaves a nested group out front to open where Filament puts it', function (): void {
    $export = ActionGroup::make(makeActions(['csv', 'pdf']))->label('Export');

    $composed = ActionGroup::make([$export, ...makeActions(['edit', 'delete'])])->withOverflow();

    expect($composed[0])->toBe($export)
        ->and($export->getDropdownPlacement())->toBeNull()
        ->and($export->getGroup())->toBeNull();
});

it('withOverflow and withSplit macros hide every action of a hidden group', function (): void {
    expect(ActionGroup::make(makeActions(['edit', 'archive', 'delete']))->hidden()->withOverflow())->toBe([])
        ->and(ActionGroup::make(makeActions(['callNext', 'counterOne']))->visible(false)->withSplit())->toBe([]);
});

it('withOverflow macro hides nothing when the group\'s condition cannot be read yet', function (): void {
    $composed = ActionGroup::make(makeActions(['edit', 'archive']))
        ->visible(fn (Model $record): bool => false)
        ->withOverflow();

    expect($composed)->toHaveCount(2);
});

it('withOverflow macro passes on the livewire() set on the group', function (): void {
    $page = new class extends Component {};

    $composed = ActionGroup::make([
        makeDividerGroup([
            Action::make('publish')->visible(fn ($livewire): bool => $livewire !== null),
            Action::make('archive'),
            Action::make('delete'),
        ]),
    ])->livewire($page)->withOverflow();

    expect($composed[0]->getName())->toBe('publish');
});

it('joins the primary action and the menu into one button group when split', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    $composed = ActionOverflow::make($actions)->split()->toActions();

    expect($composed)->toHaveCount(1)
        ->and($composed[0])->toBeInstanceOf(ActionGroup::class)
        ->and($composed[0]->isButtonGroup())->toBeTrue();

    $parts = $composed[0]->getActions();

    expect($parts)->toHaveCount(2)
        ->and($parts[0])->toBe($actions[0])
        ->and($parts[1])->toBeInstanceOf(ActionGroup::class)
        ->and($parts[1]->getActions())->toBe([$actions[1], $actions[2]]);
});

it('keeps a single leftover action in the split menu', function (): void {
    $actions = makeActions(['callNext', 'counterOne']);

    $parts = ActionOverflow::make($actions)->split()->toActions()[0]->getActions();

    expect($parts[1])->toBeInstanceOf(ActionGroup::class)
        ->and($parts[1]->getActions())->toBe([$actions[1]]);
});

it('shows only the main button when split has nothing to put in the menu', function (): void {
    $actions = makeActions(['callNext']);

    $composed = ActionOverflow::make($actions)->split()->toActions();

    expect($composed)->toBe($actions);
});

it('draws the split menu as an icon-only chevron in the main button colour', function (): void {
    $actions = [Action::make('callNext')->color('success'), ...makeActions(['counterOne', 'counterTwo'])];

    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make($actions)->split()->toActions()[0]->getActions()[1];

    expect(getConfiguredValue($menu, ['icon'], 'getIcon'))->toBe('heroicon-m-chevron-down')
        ->and(getConfiguredValue($menu, ['color'], 'getColor'))->toBe('success')
        ->and(getConfiguredValue($menu, ['tooltip'], 'getTooltip'))->toBe('More')
        ->and(getConfiguredValue($menu, ['isLabelHidden'], 'isLabelHidden'))->toBeTrue()
        ->and($menu->isButton())->toBeTrue();
});

it('colours the split menu primary when the main button has no colour of its own', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make($actions)->split()->toActions()[0]->getActions()[1];

    expect(getConfiguredValue($menu, ['color'], 'getColor'))->toBe('primary');
});

it('keeps an explicit icon and colour on the split menu', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make($actions)
        ->split()
        ->icon('heroicon-m-bars-3')
        ->color('gray')
        ->toActions()[0]
        ->getActions()[1];

    expect(getConfiguredValue($menu, ['icon'], 'getIcon'))->toBe('heroicon-m-bars-3')
        ->and(getConfiguredValue($menu, ['color'], 'getColor'))->toBe('gray');
});

it('puts the split menu first when morePosition is start', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    $parts = ActionOverflow::make($actions)
        ->split()
        ->morePosition(MorePosition::Start)
        ->toActions()[0]
        ->getActions();

    expect($parts[0])->toBeInstanceOf(ActionGroup::class)
        ->and($parts[1])->toBe($actions[0]);
});

it('drops the split menu below the button, along its outer edge', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    /** @var ActionGroup $end */
    $end = ActionOverflow::make($actions)->split()->toActions()[0]->getActions()[1];
    /** @var ActionGroup $start */
    $start = ActionOverflow::make(makeActions(['callNext', 'counterOne', 'counterTwo']))
        ->split()
        ->morePosition(MorePosition::Start)
        ->toActions()[0]
        ->getActions()[0];

    expect($end->getDropdownPlacement())->toBe('bottom-end')
        ->and($start->getDropdownPlacement())->toBe('bottom-start');
});

it('marks the split menu for the split button stylesheet', function (): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make($actions)->split()->toActions()[0]->getActions()[1];

    expect(getConfiguredValue($menu, ['extraDropdownAttributes'], 'getExtraDropdownAttributes'))
        ->toHaveKey('class', 'fi-ao-split-menu');
});

it('leaves hidden actions out of the split menu', function (): void {
    $actions = [
        Action::make('callNext'),
        Action::make('counterOne')->hidden(),
        Action::make('counterTwo'),
        Action::make('counterThree'),
    ];

    $parts = ActionOverflow::make($actions)->split()->toActions()[0]->getActions();

    expect($parts[1]->getActions())->toBe([$actions[2], $actions[3]]);
});

it('refuses a split button with other than one main action', function (int $count): void {
    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    ActionOverflow::make($actions)->primaryCount($count)->split()->toActions();
})->with([0, 2])->throws(InvalidArgumentException::class, 'A split button has one main action.');

it('refuses a split button with two main actions whatever the call order', function (): void {
    ActionOverflow::make(makeActions(['callNext', 'counterOne', 'counterTwo']))
        ->split()
        ->primaryCount(2)
        ->toActions();
})->throws(InvalidArgumentException::class, 'A split button has one main action.');

it('keeps one main action in a split button whatever the configured primary count', function (): void {
    config()->set('action-overflow.primary_count', 2);

    $actions = makeActions(['callNext', 'counterOne', 'counterTwo']);

    $parts = ActionOverflow::make($actions)->split()->toActions()[0]->getActions();
    $viaMacro = ActionGroup::make(makeActions(['callNext', 'counterOne', 'counterTwo']))->withSplit()[0]->getActions();

    expect($parts[0])->toBe($actions[0])
        ->and($parts[1]->getActions())->toBe([$actions[1], $actions[2]])
        ->and($viaMacro[0]->getName())->toBe('callNext');
});

it('makes the next available action the main one when the first is hidden', function (): void {
    $actions = [
        Action::make('callNext')->hidden(),
        Action::make('counterOne'),
        Action::make('counterTwo'),
        Action::make('counterThree'),
    ];

    $parts = ActionOverflow::make($actions)->split()->toActions()[0]->getActions();

    expect($parts[0])->toBe($actions[1])
        ->and($parts[1]->getActions())->toBe([$actions[2], $actions[3]]);
});

it('opens the more menu where dropdownPlacement says', function (): void {
    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))
        ->dropdownPlacement('top-end')
        ->toActions()[1];

    expect($group->getDropdownPlacement())->toBe('top-end');
});

it('leaves the more menu to Filament\'s own placement when none is set', function (): void {
    /** @var ActionGroup $group */
    $group = ActionOverflow::make(makeActions(['view', 'edit', 'archive']))->toActions()[1];

    expect($group->getDropdownPlacement())->toBeNull();
});

it('opens the split menu where dropdownPlacement says', function (): void {
    /** @var ActionGroup $menu */
    $menu = ActionOverflow::make(makeActions(['callNext', 'counterOne', 'counterTwo']))
        ->split()
        ->dropdownPlacement('bottom-start')
        ->toActions()[0]
        ->getActions()[1];

    expect($menu->getDropdownPlacement())->toBe('bottom-start');
});

it('exposes a withSplit macro on ActionGroup that matches split composition', function (): void {
    $callNext = Action::make('callNext');
    $counterOne = Action::make('counterOne');
    $counterTwo = Action::make('counterTwo');

    /** @var array<int, mixed> $composed */
    $composed = ActionGroup::make([$callNext, $counterOne, $counterTwo])->withSplit();

    expect($composed)->toHaveCount(1)
        ->and($composed[0]->isButtonGroup())->toBeTrue();

    $parts = $composed[0]->getActions();

    expect($parts[0]->getName())->toBe('callNext')
        ->and(array_map(static fn (Action $action): string => $action->getName(), $parts[1]->getActions()))
        ->toBe(['counterOne', 'counterTwo']);
});

it('registers the split button stylesheet', function (): void {
    $ids = array_map(
        static fn (object $asset): string => $asset->getId(),
        FilamentAsset::getStyles(['harvirsidhu/filament-action-overflow']),
    );

    expect($ids)->toContain('filament-action-overflow');
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
