<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\AttachmentModule;
use App\Enums\CustomFieldModule;
use App\Enums\GuestInvitationStatus;
use App\Enums\GuestRsvpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreGuestReferenceRequest;
use App\Http\Requests\Logistics\StoreGuestRequest;
use App\Http\Requests\Logistics\UpdateGuestRequest;
use App\Models\Guest;
use App\Models\Wedding;
use App\Support\CustomFields\SubjectCustomFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function index(Request $request, Wedding $wedding): Response
    {
        $search = $request->string('search')->trim()->toString();
        $sideFilter = $request->string('side')->toString();
        $groupFilter = $request->string('group')->toString();
        $invitationFilter = $request->string('invitation')->toString();
        $rsvpFilter = $request->string('rsvp')->toString();

        $guests = $wedding->guests()
            ->with(['side:id,uuid,name', 'group:id,uuid,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'ilike', '%'.$search.'%'))
            ->when($sideFilter !== '' && $sideFilter !== 'all',
                fn ($query) => $query->whereHas('side', fn ($side) => $side->where('uuid', $sideFilter)))
            ->when($groupFilter !== '' && $groupFilter !== 'all',
                fn ($query) => $query->whereHas('group', fn ($group) => $group->where('uuid', $groupFilter)))
            ->when($invitationFilter !== '' && $invitationFilter !== 'all',
                fn ($query) => $query->where('invitation_status', $invitationFilter))
            ->when($rsvpFilter !== '' && $rsvpFilter !== 'all',
                fn ($query) => $query->where('rsvp_status', $rsvpFilter))
            ->orderBy('name')->orderBy('id')
            ->paginate(20)->withQueryString();

        // Custom fields + attachments for the current page, keyed by guest uuid
        // so each inline row can render the same panels as the detail modules.
        $customFieldDefinitions = SubjectCustomFields::definitions($wedding, CustomFieldModule::Guest);
        $guestCustomValues = collect();

        if ($customFieldDefinitions !== []) {
            $valuesBySubject = SubjectCustomFields::valuesForSubjects($wedding, CustomFieldModule::Guest, $guests->getCollection());
            $guestCustomValues = collect($valuesBySubject)->mapWithKeys(fn ($values, $subjectId): array => [
                (string) $guests->getCollection()->firstWhere('id', (int) $subjectId)?->uuid => $values,
            ]);
        }

        // Attachments load independently of custom-field definitions: a wedding
        // without definitions can still hold uploaded files for its guests.
        $attachmentsBySubject = SubjectCustomFields::attachmentsForSubjects($wedding, AttachmentModule::Guest, $guests->getCollection());
        $guestAttachments = collect($attachmentsBySubject)->mapWithKeys(fn ($list, $subjectId): array => [
            (string) $guests->getCollection()->firstWhere('id', (int) $subjectId)?->uuid => $list,
        ]);

        return Inertia::render('weddings/guests/index', [
            'guests' => $guests->through(fn (Guest $guest): array => [
                'uuid' => $guest->uuid,
                'name' => $guest->name,
                'phone' => $guest->phone,
                'invitationCount' => $guest->invitation_count,
                'paxCount' => $guest->pax_count,
                'invitationStatus' => $guest->invitation_status->value,
                'rsvpStatus' => $guest->rsvp_status?->value,
                'notes' => $guest->notes,
                'side' => $guest->side?->name,
                'sideUuid' => $guest->side?->uuid,
                'group' => $guest->group?->name,
                'groupUuid' => $guest->group?->uuid,
            ]),
            'filters' => [
                'search' => $search,
                'side' => $sideFilter,
                'group' => $groupFilter,
                'invitation' => $invitationFilter,
                'rsvp' => $rsvpFilter,
            ],
            'sides' => $wedding->guestSides()->orderBy('sort_order')->get(['uuid', 'name']),
            'groups' => $wedding->guestGroups()->orderBy('sort_order')->get(['uuid', 'name']),
            'invitationStatuses' => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], GuestInvitationStatus::cases()),
            'rsvpStatuses' => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], GuestRsvpStatus::cases()),
            'aggregates' => $this->aggregates($wedding, $sideFilter, $groupFilter),
            'customFieldDefinitions' => $customFieldDefinitions,
            'customValues' => $guestCustomValues->all(),
            'guestAttachments' => $guestAttachments->all(),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
            'canExport' => $request->user()->can('export', $wedding),
        ]);
    }

    public function store(StoreGuestRequest $request, Wedding $wedding): RedirectResponse
    {
        $data = $request->guestData();

        $guest = $wedding->guests()->create([
            'name' => $data['name'],
            'guest_side_id' => $this->resolveId($wedding, 'guest_sides', $data['guest_side_uuid']),
            'guest_group_id' => $this->resolveId($wedding, 'guest_groups', $data['guest_group_uuid']),
            'phone' => $data['phone'],
            'invitation_count' => $data['invitation_count'],
            'pax_count' => $data['pax_count'],
            'invitation_status' => $data['invitation_status'],
            'rsvp_status' => $data['rsvp_status'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Tamu '.$guest->name.' berhasil ditambahkan.');
    }

    public function update(UpdateGuestRequest $request, Wedding $wedding, Guest $guest): RedirectResponse
    {
        abort_unless($guest->wedding_id === $wedding->id, 404);

        $data = $request->guestData();

        $guest->update([
            'name' => $data['name'],
            'guest_side_id' => $this->resolveId($wedding, 'guest_sides', $data['guest_side_uuid']),
            'guest_group_id' => $this->resolveId($wedding, 'guest_groups', $data['guest_group_uuid']),
            'phone' => $data['phone'],
            'invitation_count' => $data['invitation_count'],
            'pax_count' => $data['pax_count'],
            'invitation_status' => $data['invitation_status'],
            'rsvp_status' => $data['rsvp_status'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Tamu '.$guest->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Wedding $wedding, Guest $guest): RedirectResponse
    {
        abort_unless($guest->wedding_id === $wedding->id, 404);
        abort_unless($request->user()->can('delete', $guest), 403);

        $guest->delete();

        return back()->with('success', 'Tamu berhasil dihapus.');
    }

    public function storeReference(StoreGuestReferenceRequest $request, Wedding $wedding, string $reference): RedirectResponse
    {
        abort_unless($reference === 'sides' || $reference === 'groups', 404);

        if ($reference === 'sides') {
            $wedding->guestSides()->create($request->referenceData());
        } else {
            $wedding->guestGroups()->create($request->referenceData());
        }

        return back()->with('success', 'Referensi tamu berhasil ditambahkan.');
    }

    public function destroyReference(Request $request, Wedding $wedding, string $reference, string $uuid): RedirectResponse
    {
        abort_unless($reference === 'sides' || $reference === 'groups', 404);
        abort_unless($request->user()->can('editOperationalData', $wedding), 403);

        if ($reference === 'sides') {
            $model = $wedding->guestSides()->where('uuid', $uuid)->firstOrFail();

            if ($model->guests()->exists()) {
                return back()->withErrors(['reference' => 'Sisi yang masih digunakan tidak dapat dihapus.']);
            }
        } else {
            $model = $wedding->guestGroups()->where('uuid', $uuid)->firstOrFail();

            if ($model->guests()->exists()) {
                return back()->withErrors(['reference' => 'Grup yang masih digunakan tidak dapat dihapus.']);
            }
        }

        $model->delete();

        return back()->with('success', 'Referensi tamu berhasil dihapus.');
    }

    /**
     * Aggregates for the current filter context: totals computed alongside the
     * active side/group filter so the numbers match what the couple sees.
     *
     * @return array{guestCount: int, invitationCount: int, paxCount: int, attendingCount: int}
     */
    private function aggregates(Wedding $wedding, string $sideFilter, string $groupFilter): array
    {
        $query = $wedding->guests();

        if ($sideFilter !== '' && $sideFilter !== 'all') {
            $query->whereHas('side', fn ($side) => $side->where('uuid', $sideFilter));
        }

        if ($groupFilter !== '' && $groupFilter !== 'all') {
            $query->whereHas('group', fn ($group) => $group->where('uuid', $groupFilter));
        }

        $totals = (clone $query)->selectRaw("
                count(*) as guest_count,
                coalesce(sum(invitation_count), 0) as invitation_total,
                coalesce(sum(pax_count), 0) as pax_total,
                coalesce(sum(case when rsvp_status = 'attending' then pax_count else 0 end), 0) as attending_total
            ")->first();

        /** @var array{guest_count: mixed, invitation_total: mixed, pax_total: mixed, attending_total: mixed} $attributes */
        $attributes = $totals instanceof Model ? $totals->getAttributes() : [];

        return [
            'guestCount' => (int) ($attributes['guest_count'] ?? 0),
            'invitationCount' => (int) ($attributes['invitation_total'] ?? 0),
            'paxCount' => (int) ($attributes['pax_total'] ?? 0),
            'attendingCount' => (int) ($attributes['attending_total'] ?? 0),
        ];
    }

    private function resolveId(Wedding $wedding, string $table, ?string $uuid): ?int
    {
        if ($uuid === null) {
            return null;
        }

        $id = $wedding->getConnection()->table($table)
            ->where('wedding_id', $wedding->id)
            ->where('uuid', $uuid)
            ->value('id');

        abort_unless($id !== null, 404);

        return (int) $id;
    }
}
