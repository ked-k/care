<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceUser extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'agency_id', 'name',
        // Batch 39: profile photo — plain storage path, same reasoning as
        // users.avatar (no audit/approval need for a decorative photo).
        'photo_path',
        'dob', 'gender', 'address', 'nhs_number',
        // Batch 35: geofenced check-in — see the migration's doc comment
        // and GeocodingService.
        'latitude', 'longitude', 'location_source', 'geocoded_address',
        'next_of_kin_name', 'next_of_kin_contact', 'metadata', 'consent_status',
        // Batch 15: key care information, kept on the profile itself so it's
        // visible without needing a care plan to exist first — see the
        // migration's doc comment.
        'allergies', 'medical_conditions', 'communication_needs',
        'preferences', 'important_information',
        // Batch 16: the "named person responsible" for this service user's
        // alerts — see App\Support\AlertGenerator and the migration's doc
        // comment.
        'care_coordinator_id',
        // Batch 21: who pays for this person's care and how the charge is
        // worked out — see InvoiceRun::generateInvoices().
        'billing_type', 'billing_rate', 'billing_package_amount', 'billing_package_period',
        'payer_type', 'payer_name', 'payer_address', 'payer_email',
        'local_authority_name', 'local_authority_reference', 'local_authority_share_amount',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'metadata' => 'array',
        'consent_status' => 'boolean',
        'billing_rate' => 'decimal:2',
        'billing_package_amount' => 'decimal:2',
        'local_authority_share_amount' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Batch 20: created/updated/deleted/restored (toggleActive() uses
     * SoftDeletes, which LogsActivity tracks automatically) all land in the
     * activity_log table now — this replaces the explicit
     * AuditLogger::log('SERVICE_USER_CREATED'/'_UPDATED'/'_DEACTIVATED'/
     * '_REACTIVATED', ...) calls that used to live in
     * ServiceUserManagerComponent. A soft-delete now logs as a generic
     * "deleted" event rather than the old custom action name — the Audit
     * Log page's Entity/Description columns still make this unambiguous.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Service User')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Batch 39: full URL for the profile photo, or null when none has been
     * uploaded yet — the caller falls back to the initials avatar, same as
     * App\Models\User::avatarUrl().
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function carers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_user_carer')
            ->withPivot('is_primary_carer')
            ->withTimestamps();
    }

    public function safeguardingReports(): HasMany
    {
        return $this->hasMany(SafeguardingReport::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function careTimelineEntries(): HasMany
    {
        return $this->hasMany(CareTimelineEntry::class);
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Batch 16: the named Manager/Admin/Super Admin responsible for this
     * service user's alerts (late/missed calls, missing records,
     * medication concerns) — set by a manager in the service user form.
     * Nullable; App\Support\AlertGenerator falls back to any manager/admin
     * at the agency when this isn't set.
     */
    public function careCoordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'care_coordinator_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Batch 21: every invoice ever generated for this service user,
     * across however many InvoiceRuns — see InvoiceRun::generateInvoices().
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * True if the fields InvoiceRun::generateInvoices() needs for this
     * person's billing_type are actually filled in — used to decide
     * whether to show a "billing not set up" hint rather than silently
     * skipping them on the next invoice run.
     */
    public function hasBillingConfigured(): bool
    {
        return $this->billing_type === 'flat_package'
            ? (bool) $this->billing_package_amount
            : (bool) $this->billing_rate;
    }

    /**
     * True if any of the Batch 15 "key care information" fields are set —
     * used to decide whether to show the warning icon in the service user
     * list and the "Key care information" card on the carer's client
     * profile page, without repeating the same long @if in both views.
     */
    public function hasKeyCareInformation(): bool
    {
        return (bool) ($this->allergies || $this->medical_conditions
            || $this->communication_needs || $this->preferences || $this->important_information);
    }

    /**
     * Family-portal gate: medication details (names, doses, schedules,
     * administration history) are only ever shown to a linked family member
     * once an active "medication_communication" consent is on file for this
     * service user — checked live rather than cached on the model, since a
     * consent can be revoked at any time.
     */
    public function hasActiveMedicationConsent(): bool
    {
        return $this->consents()
            ->where('consent_type', 'medication_communication')
            ->get()
            ->contains(fn (Consent $consent) => $consent->isActive());
    }

    /**
     * How many staff-authored chat messages, across every chat session for
     * this service user, a linked family member hasn't seen yet — used for
     * the unread badge on the family portal's list of people and on the
     * per-person Messages tab.
     */
    public function unreadMessagesCountForFamily(): int
    {
        return Message::whereIn('chat_session_id', $this->chatSessions()->pluck('id'))
            ->whereNull('read_at')
            ->whereDoesntHave('sender.roles', fn ($q) => $q->where('name', 'Family'))
            ->count();
    }

    /**
     * Batch 35: whether this service user has a usable location for the
     * geofenced check-in restriction — either geocoded from the address or
     * pinned manually.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * True when the address has changed since the last successful geocode,
     * or there's no location at all yet. Never true for a manually-pinned
     * location — a manager's exact pin isn't something a later address
     * edit should silently overwrite.
     */
    public function needsGeocoding(): bool
    {
        if (! $this->address || $this->location_source === 'manual') {
            return false;
        }

        return ! $this->hasLocation() || $this->geocoded_address !== $this->address;
    }

    /**
     * Geocodes the current address via GeocodingService and saves the
     * result. Silently does nothing (returns false) if there's no address
     * or the lookup fails — see that class's doc comment for why a failed
     * geocode is never an error the caller needs to handle.
     */
    public function geocodeAddress(): bool
    {
        if (! $this->address) {
            return false;
        }

        $result = app(GeocodingService::class)->geocode($this->address);

        if (! $result) {
            return false;
        }

        $this->forceFill([
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'location_source' => 'geocoded',
            'geocoded_address' => $this->address,
        ])->save();

        return true;
    }

    /**
     * Batch 37: a one-tap "get directions" link using this service user's
     * geocoded/pinned coordinates when there are any (Google Maps' own
     * directions URL scheme opens in whichever maps app the device has
     * set as default — including Apple Maps on iOS — so this doesn't need
     * a separate per-platform link). Null when there's nothing to point
     * at yet, same "quietly absent" rule Batch 35 already uses for the
     * geofenced check-in restriction — the caller just hides the button.
     */
    public function directionsUrl(): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.$this->latitude.','.$this->longitude;
    }

    /**
     * Haversine distance in meters between this service user's stored
     * location and a carer's check-in coordinates — the core calculation
     * behind the geofenced check-in restriction (see
     * App\Livewire\Rota\ShiftVisitComponent::checkIn()). Null if this
     * service user has no location on file yet, so the caller can tell
     * "too far away" apart from "nothing to check against".
     */
    public function distanceInMetersTo(float $lat, float $lng): ?float
    {
        if (! $this->hasLocation()) {
            return null;
        }

        $earthRadiusMeters = 6371000;

        $latFrom = deg2rad((float) $this->latitude);
        $lngFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }
}
