<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Ads;

use App\Actions\Ad\CreateAdCreativeAction;
use App\Actions\Ad\CreateAdZoneAction;
use App\Actions\Ad\DeleteAdCreativeAction;
use App\Actions\Ad\UpdateAdCreativeAction;
use App\Actions\Ad\UpdateAdZoneAction;
use App\Models\AdCreative;
use App\Models\AdZone;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
#[Title('Ad Manager')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'tab')]
    public string $tab = 'creatives';   // creatives | zones

    // --- Filters (creatives) -------------------------------------------------

    #[Url(as: 'zone')]
    public string $zoneFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    // --- Modal form (zone) ---------------------------------------------------

    public bool $showZoneForm = false;

    public ?int $editingZoneId = null;

    public string $zoneKey = '';

    public string $zoneName = '';

    public string $zoneDescription = '';

    public ?int $zoneWidth = null;

    public ?int $zoneHeight = null;

    public string $zonePosition = AdZone::POSITION_INLINE;

    public bool $zoneIsActive = true;

    public int $zoneMaxCreatives = 1;

    // --- Modal form (creative) ----------------------------------------------

    public bool $showCreativeForm = false;

    public ?int $editingCreativeId = null;

    public ?int $cZoneId = null;

    public string $cName = '';

    public string $cType = AdCreative::TYPE_IMAGE;

    public ?int $cMediaId = null;

    public string $cTargetUrl = '';

    public string $cAltText = '';

    public string $cHtmlCode = '';

    public string $cStatus = AdCreative::STATUS_DRAFT;

    public ?string $cStartAt = null;

    public ?string $cEndAt = null;

    public int $cPriority = 100;

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('ads.view') ?? false,
            403,
            'You do not have access to manage ads.',
        );
    }

    // -------------------------------------------------------------------------
    // Lists
    // -------------------------------------------------------------------------

    /**
     * @return LengthAwarePaginator<AdCreative>
     */
    #[Computed]
    public function creatives(): LengthAwarePaginator
    {
        $q = AdCreative::query()->with(['zone', 'media:id,disk,path,alt_text'])->orderByDesc('id');

        if ($this->zoneFilter !== '') {
            $q->where('zone_id', (int) $this->zoneFilter);
        }
        if ($this->statusFilter !== '') {
            $q->where('status', $this->statusFilter);
        }

        return $q->paginate(perPage: 20);
    }

    /**
     * @return Collection<int, AdZone>
     */
    #[Computed]
    public function zones(): Collection
    {
        return AdZone::query()->withCount('creatives')->orderBy('name')->get();
    }

    // -------------------------------------------------------------------------
    // Zone form
    // -------------------------------------------------------------------------

    public function newZone(): void
    {
        $this->authorize('ads.positions');
        $this->resetZoneForm();
        $this->showZoneForm = true;
    }

    public function editZone(int $id): void
    {
        $this->authorize('ads.positions');
        $z = AdZone::query()->findOrFail($id);
        $this->editingZoneId = $z->id;
        $this->zoneKey = (string) $z->key;
        $this->zoneName = (string) $z->name;
        $this->zoneDescription = (string) ($z->description ?? '');
        $this->zoneWidth = $z->width;
        $this->zoneHeight = $z->height;
        $this->zonePosition = (string) ($z->position ?? AdZone::POSITION_INLINE);
        $this->zoneIsActive = (bool) $z->is_active;
        $this->zoneMaxCreatives = (int) $z->max_creatives;
        $this->showZoneForm = true;
    }

    public function cancelZoneForm(): void
    {
        $this->showZoneForm = false;
        $this->resetZoneForm();
    }

    public function saveZone(CreateAdZoneAction $create, UpdateAdZoneAction $update): void
    {
        $this->validate([
            'zoneName' => ['required', 'string', 'max:120'],
            'zoneMaxCreatives' => ['integer', 'min:1', 'max:10'],
            'zonePosition' => ['required', \Illuminate\Validation\Rule::in(AdZone::POSITIONS)],
        ]);

        try {
            $payload = [
                'key' => $this->zoneKey,
                'name' => $this->zoneName,
                'description' => $this->zoneDescription,
                'width' => $this->zoneWidth,
                'height' => $this->zoneHeight,
                'position' => $this->zonePosition,
                'is_active' => $this->zoneIsActive,
                'max_creatives' => $this->zoneMaxCreatives,
            ];

            if ($this->editingZoneId === null) {
                $this->authorize('ads.positions');
                $create->handle($payload);
                $this->dispatchSuccessToast('Ad zone created.');
            } else {
                $this->authorize('ads.positions');
                $zone = AdZone::query()->findOrFail($this->editingZoneId);
                $update->handle($zone, $payload);
                $this->dispatchSuccessToast('Ad zone updated.');
            }

            $this->cancelZoneForm();
            unset($this->zones);
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast($e->getMessage());
        }
    }

    public function deleteZone(int $id): void
    {
        $this->authorize('ads.positions');
        AdZone::query()->whereKey($id)->delete();
        $this->dispatchSuccessToast('Zone deleted.');
        unset($this->zones);
    }

    private function resetZoneForm(): void
    {
        $this->editingZoneId = null;
        $this->zoneKey = '';
        $this->zoneName = '';
        $this->zoneDescription = '';
        $this->zoneWidth = null;
        $this->zoneHeight = null;
        $this->zonePosition = AdZone::POSITION_INLINE;
        $this->zoneIsActive = true;
        $this->zoneMaxCreatives = 1;
        $this->resetValidation();
    }

    // -------------------------------------------------------------------------
    // Creative form
    // -------------------------------------------------------------------------

    public function newCreative(): void
    {
        $this->authorize('ads.create');
        $this->resetCreativeForm();
        $this->showCreativeForm = true;
    }

    public function editCreative(int $id): void
    {
        $this->authorize('ads.edit');

        $c = AdCreative::query()->findOrFail($id);
        $this->editingCreativeId = $c->id;
        $this->cZoneId = $c->zone_id;
        $this->cName = (string) $c->name;
        $this->cType = (string) $c->type;
        $this->cMediaId = $c->media_id;
        $this->cTargetUrl = (string) ($c->target_url ?? '');
        $this->cAltText = (string) ($c->alt_text ?? '');
        $this->cHtmlCode = (string) ($c->html_code ?? '');
        $this->cStatus = (string) $c->status;
        $this->cStartAt = $c->start_at?->format('Y-m-d\TH:i');
        $this->cEndAt = $c->end_at?->format('Y-m-d\TH:i');
        $this->cPriority = (int) $c->priority;
        $this->showCreativeForm = true;
    }

    public function cancelCreativeForm(): void
    {
        $this->showCreativeForm = false;
        $this->resetCreativeForm();
    }

    public function saveCreative(CreateAdCreativeAction $create, UpdateAdCreativeAction $update): void
    {
        $this->validate([
            'cZoneId' => ['required', 'integer', 'exists:ad_zones,id'],
            'cName' => ['required', 'string', 'max:255'],
            'cType' => ['required', \Illuminate\Validation\Rule::in(AdCreative::TYPES)],
            'cStatus' => ['required', \Illuminate\Validation\Rule::in(AdCreative::STATUSES)],
            'cPriority' => ['integer', 'min:1', 'max:1000'],
            'cTargetUrl' => ['nullable', 'url', 'max:1000'],
        ]);

        try {
            $payload = [
                'zone_id' => $this->cZoneId,
                'name' => $this->cName,
                'type' => $this->cType,
                'media_id' => $this->cMediaId,
                'target_url' => $this->cTargetUrl !== '' ? $this->cTargetUrl : null,
                'alt_text' => $this->cAltText !== '' ? $this->cAltText : null,
                'html_code' => $this->cHtmlCode !== '' ? $this->cHtmlCode : null,
                'status' => $this->cStatus,
                'start_at' => $this->cStartAt,
                'end_at' => $this->cEndAt,
                'priority' => $this->cPriority,
            ];

            if ($this->editingCreativeId === null) {
                $create->handle($payload);
                $this->dispatchSuccessToast('Creative created.');
            } else {
                $row = AdCreative::query()->findOrFail($this->editingCreativeId);
                $update->handle($row, $payload);
                $this->dispatchSuccessToast('Creative updated.');
            }

            $this->cancelCreativeForm();
            $this->resetPage();
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast($e->getMessage());
        }
    }

    public function deleteCreative(int $id, DeleteAdCreativeAction $delete): void
    {
        $this->authorize('ads.delete');

        $row = AdCreative::query()->findOrFail($id);
        $delete->handle($row);
        $this->dispatchSuccessToast('Creative deleted.');
    }

    public function toggleCreativeStatus(int $id, UpdateAdCreativeAction $update): void
    {
        $this->authorize('ads.edit');

        $row = AdCreative::query()->findOrFail($id);
        $new = $row->status === AdCreative::STATUS_ACTIVE
            ? AdCreative::STATUS_PAUSED
            : AdCreative::STATUS_ACTIVE;
        $update->handle($row, ['status' => $new]);
        $this->dispatchSuccessToast("Creative {$new}.");
    }

    private function resetCreativeForm(): void
    {
        $this->editingCreativeId = null;
        $this->cZoneId = null;
        $this->cName = '';
        $this->cType = AdCreative::TYPE_IMAGE;
        $this->cMediaId = null;
        $this->cTargetUrl = '';
        $this->cAltText = '';
        $this->cHtmlCode = '';
        $this->cStatus = AdCreative::STATUS_DRAFT;
        $this->cStartAt = null;
        $this->cEndAt = null;
        $this->cPriority = 100;
        $this->resetValidation();
    }

    // -------------------------------------------------------------------------
    // Media picker integration (creative image)
    // -------------------------------------------------------------------------

    public function openMediaPickerForCreative(): void
    {
        $this->dispatch('media-picker.open', payload: [
            'target' => 'ad_creative_image',
            'mime' => 'image/',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[On('media.selected')]
    public function onMediaSelected(array $payload): void
    {
        if (($payload['target'] ?? null) !== 'ad_creative_image') {
            return;
        }
        $this->cMediaId = isset($payload['mediaId']) ? (int) $payload['mediaId'] : null;
    }

    #[Computed]
    public function selectedMedia(): ?Media
    {
        return $this->cMediaId !== null ? Media::query()->find($this->cMediaId) : null;
    }

    public function render(): View
    {
        return view('livewire.admin.ads.index');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        $this->dispatch('toast.danger', message: $message);
    }
}
