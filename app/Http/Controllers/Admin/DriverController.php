<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverStatusLog;
use App\Models\DriverVehicle;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Service;
use App\Models\VehicleCategory;
use App\Models\Zone;
use App\Support\TableExport;
use App\Support\TableImport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:drivers,read', only: ['index', 'show', 'pending', 'expiringDocuments', 'export', 'importTemplate']),
            new Middleware('permission:drivers,write', only: ['edit', 'update', 'review', 'approve', 'reject', 'changeStatus', 'notify', 'bulkAction', 'notifyExpiring', 'create', 'store', 'import']),
        ];
    }

    public function create()
    {
        return view('admin.drivers.create', [
            'categories' => VehicleCategory::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'zones' => Zone::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('drivers', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('drivers', 'email')],
            'status' => ['required', Rule::in(['approved', 'pending', 'suspended'])],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            // optional vehicle
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
            'vehicle_make' => ['nullable', 'string', 'max:50'],
            'vehicle_model' => ['nullable', 'string', 'max:50'],
            'vehicle_year' => ['nullable', 'string', 'max:10'],
            'vehicle_color' => ['nullable', 'string', 'max:30'],
            'vehicle_registration_number' => ['nullable', 'string', 'max:50'],
            // Documents (optional for admin quick-add; same set as the driver app register API)
            'nid_front' => ['nullable', 'image', 'max:4096'],
            'nid_back' => ['nullable', 'image', 'max:4096'],
            'license_front' => ['nullable', 'image', 'max:4096'],
            'license_expiry' => ['nullable', 'date'],
            'vehicle_registration_doc' => ['nullable', 'image', 'max:4096'],
            'vehicle_registration_expiry' => ['nullable', 'date'],
            'insurance_doc' => ['nullable', 'image', 'max:4096'],
            'insurance_expiry' => ['nullable', 'date'],
            'vehicle_front_photo' => ['nullable', 'image', 'max:4096'],
            'vehicle_back_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $driver = DB::transaction(function () use ($request, $data) {
            $approved = $data['status'] === 'approved';

            $driver = Driver::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'status' => $data['status'],
                'zone_id' => $data['zone_id'] ?? null,
                'is_active' => true,
                'is_online' => false,
                'can_accept_orders' => $approved,
                'avatar' => $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'public') : null,
            ]);

            // Store uploaded files under a per-driver folder.
            $store = fn ($field) => $request->hasFile($field) ? $request->file($field)->store('drivers/' . $driver->id, 'public') : null;

            // Documents — create a record per uploaded document (same types as the API).
            $verified = $approved ? now() : null;
            $docStatus = $approved ? 'approved' : 'pending';

            if ($request->hasFile('nid_front')) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'nid', 'front_image' => $store('nid_front'), 'back_image' => $store('nid_back'), 'status' => $docStatus, 'verified_at' => $verified]);
            }
            if ($request->hasFile('license_front')) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'driving_license', 'front_image' => $store('license_front'), 'expiry_date' => $data['license_expiry'] ?? null, 'status' => $docStatus, 'verified_at' => $verified]);
            }
            if ($request->hasFile('vehicle_registration_doc')) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_registration', 'front_image' => $store('vehicle_registration_doc'), 'expiry_date' => $data['vehicle_registration_expiry'] ?? null, 'status' => $docStatus, 'verified_at' => $verified]);
            }
            if ($request->hasFile('insurance_doc')) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_insurance', 'front_image' => $store('insurance_doc'), 'expiry_date' => $data['insurance_expiry'] ?? null, 'status' => $docStatus, 'verified_at' => $verified]);
            }

            $vehicleFront = $store('vehicle_front_photo');
            $vehicleBack = $store('vehicle_back_photo');
            if ($vehicleFront || $vehicleBack) {
                DriverDocument::create(['driver_id' => $driver->id, 'type' => 'vehicle_photo', 'front_image' => $vehicleFront, 'back_image' => $vehicleBack, 'status' => $docStatus, 'verified_at' => $verified]);
            }

            if (! empty($data['vehicle_category_id'])) {
                DriverVehicle::create([
                    'driver_id' => $driver->id,
                    'vehicle_category_id' => $data['vehicle_category_id'],
                    'make' => $data['vehicle_make'] ?? '',
                    'model' => $data['vehicle_model'] ?? '',
                    'year' => $data['vehicle_year'] ?? '',
                    'color' => $data['vehicle_color'] ?? '',
                    'registration_number' => $data['vehicle_registration_number'] ?? '',
                    'front_photo' => $vehicleFront ?? '',
                    'back_photo' => $vehicleBack ?? '',
                    'is_active' => true,
                ]);
            }

            return $driver;
        });

        return redirect()->route('admin.drivers.show', $driver->id)->with('success', 'Driver added.');
    }

    public function index(Request $request)
    {
        $drivers = $this->filteredQuery($request)
            ->with(['zone', 'vehicles.vehicleCategory'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.drivers.index', [
            'drivers' => $drivers,
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::orderBy('name')->get(),
            'stats' => $this->statusStats(),
            'filters' => $request->only(['search', 'status', 'zone_id', 'vehicle_category_id', 'online', 'from', 'to']),
        ]);
    }

    public function pending()
    {
        $drivers = Driver::pending()
            ->with(['zone', 'documents'])
            ->latest()
            ->paginate(20);

        return view('admin.drivers.pending', compact('drivers'));
    }

    public function expiringDocuments()
    {
        $base = fn () => DriverDocument::with('driver:id,name,phone,status')
            ->where('status', 'approved')
            ->whereNotNull('expiry_date');

        $expired = (clone $base())->whereDate('expiry_date', '<', today())->orderBy('expiry_date')->get();
        $within7 = (clone $base())->whereBetween('expiry_date', [today(), today()->addDays(7)])->orderBy('expiry_date')->get();
        $within30 = (clone $base())->whereBetween('expiry_date', [today()->addDays(8), today()->addDays(30)])->orderBy('expiry_date')->get();

        return view('admin.drivers.expiring-documents', compact('expired', 'within7', 'within30'));
    }

    // Notify every driver with a document in the given expiry bucket.
    public function notifyExpiring(Request $request)
    {
        $section = $request->validate(['section' => ['required', 'in:expired,within7,within30']])['section'];

        $query = DriverDocument::with('driver:id,name')
            ->where('status', 'approved')
            ->whereNotNull('expiry_date');

        match ($section) {
            'expired' => $query->whereDate('expiry_date', '<', today()),
            'within7' => $query->whereBetween('expiry_date', [today(), today()->addDays(7)]),
            'within30' => $query->whereBetween('expiry_date', [today()->addDays(8), today()->addDays(30)]),
        };

        $docs = $query->get();
        $notifications = app(\App\Services\NotificationService::class);

        foreach ($docs as $doc) {
            if (! $doc->driver) {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', $doc->type));
            $notifications->sendPush('driver', $doc->driver_id, 'Document expiry',
            "Your {$label} is expiring soon. Please upload a new document.", 'document_expiry');
        }

        return back()->with('success', $docs->count() . ' driver(s) notified.');
    }

    public function show(Request $request, string $id)
    {
        $driver = Driver::with(['zone', 'documents.verifier', 'vehicles.vehicleCategory'])
            ->findOrFail($id);

        $tab = $request->query('tab', 'profile');

        // Lazy-load per-tab data to keep queries lean. Trips tab supports
        // type / status / date-range filters (spec §2 Tab 4).
        $trips = $driver->orders()
            ->with(['user:id,name', 'service:id,name'])
            ->when(in_array($request->query('trip_type'), ['ride', 'parcel'], true), fn ($q) => $q->where('type', $request->query('trip_type')))
            ->when($request->filled('trip_status'), fn ($q) => $q->where('status', $request->query('trip_status')))
            ->when($request->filled('trip_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('trip_from')))
            ->when($request->filled('trip_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('trip_to')))
            ->latest()
            ->paginate(15, ['*'], 'trips')
            ->withQueryString();

        $transactions = $driver->walletTransactions()
            ->latest()
            ->paginate(15, ['*'], 'tx')
            ->withQueryString();

        $withdrawals = $driver->withdrawalRequests()->latest()->limit(20)->get();
        $shifts = $driver->shiftLogs()->latest()->limit(10)->get();
        $statusLogs = $driver->statusLogs()->with('changedBy:id,name')->latest()->limit(20)->get();

        // Online hours (from shift logs) for Today / This Week / This Month.
        $onlineHours = [
            'today' => round((int) $driver->shiftLogs()->whereDate('went_online_at', today())->sum('total_minutes') / 60, 1),
            'week' => round((int) $driver->shiftLogs()->where('went_online_at', '>=', now()->startOfWeek())->sum('total_minutes') / 60, 1),
            'month' => round((int) $driver->shiftLogs()->where('went_online_at', '>=', now()->startOfMonth())->sum('total_minutes') / 60, 1),
        ];

        $ratingBreakdown = DB::table('ratings')
            ->where('ratee_type', 'driver')->where('ratee_id', $driver->id)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')->pluck('count', 'rating')->toArray();

        $notifications = Notification::where('notifiable_type', 'driver')
            ->where('notifiable_id', $driver->id)
            ->latest()->limit(30)->get();

        $dueLimit = (float) \App\Models\SystemSetting::get('due_limit_amount', 500);

        return view('admin.drivers.show', compact(
            'driver', 'tab', 'trips', 'transactions', 'withdrawals',
            'shifts', 'statusLogs', 'ratingBreakdown', 'notifications', 'dueLimit', 'onlineHours'
        ));
    }

    public function edit(string $id)
    {
        $driver = Driver::with(['vehicles', 'documents'])->findOrFail($id);

        return view('admin.drivers.edit', [
            'driver' => $driver,
            'zones' => Zone::orderBy('name')->get(),
            'categories' => VehicleCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $driver = Driver::with(['vehicles', 'documents'])->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('drivers', 'email')->ignore($driver->id)],
            'status' => ['required', Rule::in(['approved', 'pending', 'suspended', 'blocked', 'rejected'])],
            'status_reason' => ['nullable', 'string', 'max:1000'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            // vehicle
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
            'vehicle_make' => ['nullable', 'string', 'max:50'],
            'vehicle_model' => ['nullable', 'string', 'max:50'],
            'vehicle_year' => ['nullable', 'string', 'max:10'],
            'vehicle_color' => ['nullable', 'string', 'max:30'],
            'vehicle_registration_number' => ['nullable', 'string', 'max:50'],
            // documents — same set as the add form and the driver app register API
            'nid_front' => ['nullable', 'image', 'max:4096'],
            'nid_back' => ['nullable', 'image', 'max:4096'],
            'license_front' => ['nullable', 'image', 'max:4096'],
            'license_expiry' => ['nullable', 'date'],
            'vehicle_registration_doc' => ['nullable', 'image', 'max:4096'],
            'vehicle_registration_expiry' => ['nullable', 'date'],
            'insurance_doc' => ['nullable', 'image', 'max:4096'],
            'insurance_expiry' => ['nullable', 'date'],
            'vehicle_front_photo' => ['nullable', 'image', 'max:4096'],
            'vehicle_back_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $statusChanged = $data['status'] !== $driver->status;

        // Same guard the status panel uses — suspending or blocking must be explained.
        if ($statusChanged && in_array($data['status'], ['suspended', 'blocked'], true) && empty($data['status_reason'])) {
            return back()->withInput()->withErrors(['status_reason' => __('admin.status_reason_required')]);
        }

        DB::transaction(function () use ($request, $data, $driver, $statusChanged) {
            if ($request->hasFile('avatar')) {
                if ($driver->avatar) {
                    Storage::disk('public')->delete($driver->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('drivers', 'public');
            }

            // Phone & password are intentionally not editable by admin.
            $driver->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'zone_id' => $data['zone_id'] ?? null,
                'avatar' => $data['avatar'] ?? $driver->avatar,
            ]);

            // Status goes through the audited transition so the change is logged
            // and the driver is told, exactly as the status panel does.
            if ($statusChanged) {
                $this->transitionStatus($driver, $data['status'], $data['status_reason'] ?? null);

                if (in_array($data['status'], ['suspended', 'blocked'], true)) {
                    $driver->update(['is_online' => false]);
                }
            }

            $store = fn ($field) => $request->hasFile($field)
                ? $request->file($field)->store('drivers/' . $driver->id, 'public')
                : null;

            // Replace a stored file and clean up the one it supersedes.
            $swap = function (?string $new, ?string $old) {
                if ($new && $old) {
                    Storage::disk('public')->delete($old);
                }

                return $new ?: $old;
            };

            // ── Documents ────────────────────────────────────────────────────
            // New records follow the driver's standing: an approved driver's
            // paperwork is trusted, anything else waits for review.
            $approved = $driver->status === 'approved';
            $newStatus = $approved ? 'approved' : 'pending';
            $newVerified = $approved ? now() : null;

            $saveDoc = function (string $type, ?string $front, ?string $back, $expiry, bool $expiryGiven)
                use ($driver, $swap, $newStatus, $newVerified) {
                $doc = $driver->documents->firstWhere('type', $type);

                if (! $doc) {
                    // Nothing uploaded and no date given — nothing to record.
                    if (! $front && ! $back && ! $expiryGiven) {
                        return;
                    }

                    DriverDocument::create([
                        'driver_id' => $driver->id,
                        'type' => $type,
                        'front_image' => $front,
                        'back_image' => $back,
                        'expiry_date' => $expiry,
                        'status' => $newStatus,
                        'verified_at' => $newVerified,
                    ]);

                    return;
                }

                $doc->update([
                    'front_image' => $swap($front, $doc->front_image),
                    'back_image' => $swap($back, $doc->back_image),
                    'expiry_date' => $expiryGiven ? $expiry : $doc->expiry_date,
                ]);
            };

            $vehicleFront = $store('vehicle_front_photo');
            $vehicleBack = $store('vehicle_back_photo');

            $saveDoc('nid', $store('nid_front'), $store('nid_back'), null, false);
            $saveDoc('driving_license', $store('license_front'), null, $data['license_expiry'] ?? null, $request->filled('license_expiry'));
            $saveDoc('vehicle_registration', $store('vehicle_registration_doc'), null, $data['vehicle_registration_expiry'] ?? null, $request->filled('vehicle_registration_expiry'));
            $saveDoc('vehicle_insurance', $store('insurance_doc'), null, $data['insurance_expiry'] ?? null, $request->filled('insurance_expiry'));
            $saveDoc('vehicle_photo', $vehicleFront, $vehicleBack, null, false);

            // ── Vehicle ──────────────────────────────────────────────────────
            $vehicle = $driver->vehicles->firstWhere('is_active', true) ?? $driver->vehicles->first();

            $vehicleFields = array_filter([
                'vehicle_category_id' => $data['vehicle_category_id'] ?? null,
                'make' => $data['vehicle_make'] ?? null,
                'model' => $data['vehicle_model'] ?? null,
                'year' => $data['vehicle_year'] ?? null,
                'color' => $data['vehicle_color'] ?? null,
                'registration_number' => $data['vehicle_registration_number'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            if ($vehicle) {
                if ($vehicleFields || $vehicleFront || $vehicleBack) {
                    $vehicle->update($vehicleFields + array_filter([
                        'front_photo' => $swap($vehicleFront, $vehicle->front_photo),
                        'back_photo' => $swap($vehicleBack, $vehicle->back_photo),
                    ]));
                }
            } elseif (! empty($data['vehicle_category_id'])) {
                // The driver had no vehicle on file yet — start one.
                DriverVehicle::create([
                    'driver_id' => $driver->id,
                    'vehicle_category_id' => $data['vehicle_category_id'],
                    'make' => $data['vehicle_make'] ?? '',
                    'model' => $data['vehicle_model'] ?? '',
                    'year' => $data['vehicle_year'] ?? '',
                    'color' => $data['vehicle_color'] ?? '',
                    'registration_number' => $data['vehicle_registration_number'] ?? '',
                    'front_photo' => $vehicleFront ?? '',
                    'back_photo' => $vehicleBack ?? '',
                    'is_active' => true,
                ]);
            }
        });

        if ($statusChanged) {
            $this->notifyDriver($driver, 'Account status update', 'Your account status is now ' . $driver->status . '.', 'order_update');
        }

        return redirect()->route('admin.drivers.show', $driver->id)->with('success', 'Driver updated.');
    }

    public function review(string $id)
    {
        $driver = Driver::with(['documents', 'vehicles.vehicleCategory', 'zone'])->findOrFail($id);

        return view('admin.drivers.review', compact('driver'));
    }

    public function approve(string $id)
    {
        $driver = Driver::findOrFail($id);
        $this->transitionStatus($driver, 'approved', null);

        // driver_approval rule event (respects Notification Rules: push + SMS).
        app(\App\Services\NotificationService::class)->notifyEvent(
            'driver_approval', 'driver', $driver->id,
            'Your account has been approved',
            'Congratulations! Your ' . appName() . ' driver account has been approved.',
            'order_update', [], $driver->phone,
        );

        return redirect()->route('admin.drivers.pending')->with('success', $driver->name . ' approved.');
    }

    public function reject(Request $request, string $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $driver = Driver::findOrFail($id);
        $driver->rejection_reason = $request->reason;
        $driver->save();

        $this->transitionStatus($driver, 'rejected', $request->reason);

        $this->notifyDriver($driver, 'Your application has been rejected',
            'Reason: ' . $request->reason, 'order_update');

        return redirect()->route('admin.drivers.pending')->with('success', $driver->name . ' rejected.');
    }

    // Suspend / block / unblock / reactivate.
    public function changeStatus(Request $request, string $id)
    {
        $data = $request->validate([
            'new_status' => ['required', 'in:approved,suspended,blocked,rejected'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        // Reason is required when suspending or blocking.
        if (in_array($data['new_status'], ['suspended', 'blocked'], true) && empty($data['reason'])) {
            return back()->withErrors(['reason' => 'A reason is required to suspend or block a driver.']);
        }

        $driver = Driver::findOrFail($id);

        $reason = $data['reason'] ?? null;
        if ($data['new_status'] === 'suspended' && ! empty($data['duration_days'])) {
            $reason = trim(($reason ?? '') . ' (Duration: ' . $data['duration_days'] . ' days)');
        }

        $this->transitionStatus($driver, $data['new_status'], $reason);

        // If suspended/blocked, force the driver offline.
        if (in_array($data['new_status'], ['suspended', 'blocked'], true)) {
            $driver->update(['is_online' => false]);
        }

        $labels = [
            'approved' => 'Your account has been approved.',
            'suspended' => 'Your account has been suspended. ' . ($reason ?? ''),
            'blocked' => 'Your account has been blocked. ' . ($reason ?? ''),
            'rejected' => 'Your account has been rejected. ' . ($reason ?? ''),
        ];
        $this->notifyDriver($driver, 'Account status update', $labels[$data['new_status']], 'order_update');

        return back()->with('success', 'Driver status changed to ' . $data['new_status'] . '.');
    }

    public function notify(Request $request, string $id)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $driver = Driver::findOrFail($id);
        $this->notifyDriver($driver, $data['title'], $data['body'], 'broadcast');

        return back()->with('success', 'Notification sent to ' . $driver->name . '.');
    }

    /** Columns shared by every driver export format. */
    private const EXPORT_HEADERS = [
        'ID', 'Name', 'Phone', 'Email', 'Status', 'Online', 'Zone',
        'Rating', 'Total Trips', 'Wallet', 'Due', 'Joined',
    ];

    // Export the currently filtered drivers as CSV / Excel / Word / PDF.
    public function export(Request $request)
    {
        $format = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx,docx,pdf'],
        ])['format'] ?? 'csv';

        $drivers = $this->filteredQuery($request)->with('zone')->get();

        $rows = $drivers->map(fn ($d) => [
            $d->id, $d->name, $d->phone, $d->email, ucfirst($d->status),
            $d->is_online ? 'Yes' : 'No', $d->zone->name ?? '—',
            number_format((float) $d->average_rating, 1), $d->total_trips,
            number_format((float) $d->wallet_balance, 2),
            number_format((float) $d->due_amount, 2),
            $d->created_at?->format('d M Y'),
        ])->all();

        $stamp = now()->format('Ymd-His');
        $title = __('admin.drivers');

        return match ($format) {
            'xlsx' => TableExport::xlsx("drivers-{$stamp}.xlsx", $title, self::EXPORT_HEADERS, $rows),
            'docx' => TableExport::docx(
                "drivers-{$stamp}.docx",
                $title,
                [
                    __('admin.generated') . ': ' . now()->format('d M Y, h:i a'),
                    __('admin.total_records') . ': ' . count($rows),
                ],
                self::EXPORT_HEADERS,
                $rows
            ),
            'pdf' => response()->view('admin.exports.table-pdf', [
                'title' => $title,
                'headers' => self::EXPORT_HEADERS,
                'rows' => $rows,
                'total' => count($rows),
                'generatedAt' => now()->format('d M Y, h:i a'),
                'generatedBy' => adminUser()?->name ?? '—',
                'appliedFilters' => $this->filterLabels($request),
            ]),
            default => TableExport::csv("drivers-{$stamp}.csv", self::EXPORT_HEADERS, $rows),
        };
    }

    /** Human-readable summary of the active filters, shown on the PDF header. */
    private function filterLabels(Request $request): array
    {
        $labels = [];

        if ($request->filled('search')) {
            $labels[__('admin.search')] = $request->string('search')->toString();
        }
        if ($request->filled('status')) {
            $labels[__('admin.status')] = ucfirst($request->string('status')->toString());
        }
        if ($request->filled('zone_id')) {
            $labels[__('admin.zone')] = Zone::find($request->zone_id)?->name ?? $request->zone_id;
        }
        if ($request->filled('vehicle_category_id')) {
            $labels[__('admin.vehicle')] = VehicleCategory::find($request->vehicle_category_id)?->name ?? $request->vehicle_category_id;
        }
        if ($request->filled('online')) {
            $labels[__('admin.online')] = ucfirst($request->string('online')->toString());
        }
        if ($request->filled('from') || $request->filled('to')) {
            $labels[__('admin.joined')] = trim(($request->from ?? '…') . ' → ' . ($request->to ?? '…'));
        }

        return $labels;
    }

    /** Blank CSV with just the import headers, so buyers know the column shape. */
    public function importTemplate(): StreamedResponse
    {
        return TableExport::csv('drivers-import-template.csv', self::IMPORT_HEADERS, [
            ['Jamal Mia', '01800000099', 'jamal@example.com', 'pending', ''],
        ]);
    }

    private const IMPORT_HEADERS = ['name', 'phone', 'email', 'status', 'zone'];

    /**
     * Create or update drivers from an uploaded CSV/Excel file.
     * Matching is by phone, which is already unique on the table.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        try {
            $rows = TableImport::read($request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $created = $updated = 0;
        $errors = [];
        $zones = Zone::pluck('id', 'name');

        foreach ($rows as $i => $row) {
            $line = $i + 2; // header is line 1
            $name = trim((string) ($row['name'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));

            if ($name === '' || $phone === '') {
                $errors[] = __('admin.row') . " {$line}: name/phone missing";
                continue;
            }

            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            if (! in_array($status, ['pending', 'approved', 'rejected', 'suspended', 'blocked'], true)) {
                $status = 'pending';
            }

            $zoneName = trim((string) ($row['zone'] ?? ''));

            $existing = Driver::where('phone', $phone)->first();
            $attributes = [
                'name' => $name,
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'status' => $status,
                'zone_id' => $zoneName !== '' ? ($zones[$zoneName] ?? null) : ($existing->zone_id ?? null),
                'can_accept_orders' => $status === 'approved',
            ];

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                Driver::create($attributes + [
                    'phone' => $phone,
                    'is_active' => true,
                    'is_online' => false,
                ]);
                $created++;
            }
        }

        $message = __('admin.import_summary') . ": {$created} " . __('admin.created')
            . ", {$updated} " . __('admin.updated')
            . ', ' . count($errors) . ' ' . __('admin.skipped') . '.';

        return back()->with($errors ? 'warning' : 'success', $message . ($errors ? ' — ' . implode('; ', array_slice($errors, 0, 5)) : ''));
    }

    // Bulk approve/reject/suspend/block from the list page.
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approved,rejected,suspended,blocked'],
            'driver_ids' => ['required', 'array'],
            'driver_ids.*' => ['exists:drivers,id'],
        ]);

        $drivers = Driver::whereIn('id', $data['driver_ids'])->get();
        foreach ($drivers as $driver) {
            $this->transitionStatus($driver, $data['action'], 'Bulk action');
            if (in_array($data['action'], ['suspended', 'blocked'], true)) {
                $driver->update(['is_online' => false]);
            }
        }

        return back()->with('success', count($drivers) . ' driver(s) updated to ' . $data['action'] . '.');
    }

    // ---------------------------------------------------------------------

    // Build the base filtered query used by index + export.
    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Driver::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search')->toString();
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('zone_id'), fn ($q) => $q->where('zone_id', $request->zone_id))
            ->when($request->filled('online'), fn ($q) => $q->where('is_online', $request->online === 'online'))
            ->when($request->filled('vehicle_category_id'), function ($q) use ($request) {
                $q->whereHas('vehicles', fn ($v) => $v->where('vehicle_category_id', $request->vehicle_category_id));
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
    }

    private function statusStats(): array
    {
        $counts = Driver::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'total' => Driver::count(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'online' => Driver::online()->count(),
            'suspended' => (int) ($counts['suspended'] ?? 0),
            'blocked' => (int) ($counts['blocked'] ?? 0),
        ];
    }

    // Apply a status change and record it in driver_status_logs.
    private function transitionStatus(Driver $driver, string $newStatus, ?string $reason): void
    {
        $old = $driver->status;
        $driver->update(['status' => $newStatus]);

        DriverStatusLog::create([
            'driver_id' => $driver->id,
            'changed_by' => auth('admin')->id(),
            'old_status' => $old,
            'new_status' => $newStatus,
            'reason' => $reason,
        ]);
    }

    // Notify the driver in-app and via push (FCM, when configured).
    private function notifyDriver(Driver $driver, string $title, string $body, string $type): void
    {
        app(\App\Services\NotificationService::class)->sendPush('driver', $driver->id, $title, $body, $type);
    }
}
