<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerStatusLog;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\WalletService;
use App\Support\TableExport;
use App\Support\TableImport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:users,read', only: ['index', 'show', 'export', 'importTemplate']),
            new Middleware('permission:users,write', only: ['toggleBlock', 'create', 'store', 'import']),
        ];
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request, WalletService $wallet)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'wallet_balance' => ['nullable', 'numeric', 'min:0'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'referral_code' => $this->uniqueReferralCode($data['name']),
            'wallet_balance' => 0,
            'is_active' => $request->boolean('is_active', true),
            'avatar' => $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'public') : null,
        ]);

        // Opening balance via WalletService so the transaction history is consistent.
        if (! empty($data['wallet_balance']) && (float) $data['wallet_balance'] > 0) {
            $wallet->creditUser($customer, (float) $data['wallet_balance'], 'top_up', null, 'Opening balance (added by admin)');
        }

        return redirect()->route('admin.customers.show', $customer->id)->with('success', 'Customer added.');
    }

    private function uniqueReferralCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'USER', 0, 4));
        do {
            $code = $base . random_int(1000, 9999);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function index(Request $request)
    {
        $customers = $this->filteredQuery($request)
            ->withCount([
                'orders as total_trips' => fn ($q) => $q->where('type', 'ride'),
                'orders as total_parcels' => fn ($q) => $q->where('type', 'parcel'),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'stats' => $this->stats(),
            'filters' => $request->only(['search', 'status', 'has_balance', 'from', 'to']),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $customer = User::with('referrer:id,name')
            ->withCount([
                'orders as total_trips' => fn ($q) => $q->where('type', 'ride'),
                'orders as total_parcels' => fn ($q) => $q->where('type', 'parcel'),
            ])
            ->findOrFail($id);

        $tab = $request->query('tab', 'profile');

        $typeFilter = $request->query('type');
        $orders = $customer->orders()
            ->with(['driver:id,name', 'service:id,name'])
            ->when(in_array($typeFilter, ['ride', 'parcel'], true), fn ($q) => $q->where('type', $typeFilter))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate(10, ['*'], 'orders')
            ->withQueryString();

        $transactions = $customer->walletTransactions()
            ->latest()
            ->paginate(15, ['*'], 'tx')
            ->withQueryString();

        // Sender delivery-charge due (COD) + ledger history.
        $dueLimit = (float) SystemSetting::get('sender_due_limit_amount', 500);
        $dueTransactions = $customer->dueTransactions()
            ->with('order:id,order_number')
            ->latest()
            ->paginate(15, ['*'], 'due')
            ->withQueryString();

        $disputes = $customer->disputes()
            ->with('order:id,order_number')
            ->latest()
            ->limit(30)
            ->get();

        $referrals = $customer->referralRecords()
            ->with('referee:id,name,phone,created_at')
            ->latest()
            ->get();

        $statusLogs = $customer->statusLogs()
            ->with('changedBy:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.customers.show', compact(
            'customer', 'tab', 'orders', 'transactions',
            'dueLimit', 'dueTransactions',
            'disputes', 'referrals', 'statusLogs'
        ));
    }

    // Block / unblock a customer and record the change.
    public function toggleBlock(Request $request, string $id)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = User::findOrFail($id);

        $old = (bool) $customer->is_active;
        $new = ! $old;

        $customer->update(['is_active' => $new]);

        CustomerStatusLog::create([
            'user_id' => $customer->id,
            'changed_by' => auth('admin')->id(),
            'old_status' => $old,
            'new_status' => $new,
            'reason' => $data['reason'] ?? null,
        ]);

        // Inform the customer about the change.
        $this->notifyCustomer(
            $customer,
            $new ? 'Account পুনরায় active করা হয়েছে' : 'Account block করা হয়েছে',
            $new
                ? 'আপনার ReadyRide account আবার চালু করা হয়েছে।'
                : 'আপনার account block করা হয়েছে। ' . ($data['reason'] ?? ''),
            'broadcast'
        );

        $msg = $new
            ? $customer->name . ' unblocked.'
            : $customer->name . ' blocked.';

        // Warn if the customer had an ongoing order at block time.
        if (! $new && $customer->orders()->whereNotIn('status', ['completed', 'cancelled', 'rejected'])->exists()) {
            return back()->with('warning', $msg . ' Note: this customer has an ongoing order.');
        }

        return back()->with('success', $msg);
    }

    /** Columns shared by every customer export format. */
    private const EXPORT_HEADERS = [
        'ID', 'Name', 'Phone', 'Email', 'Wallet', 'Due',
        'Trips', 'Parcels', 'Referral Code', 'Status', 'Joined',
    ];

    private const IMPORT_HEADERS = ['name', 'phone', 'email', 'status'];

    // Export the currently filtered customers as CSV / Excel / Word / PDF.
    public function export(Request $request)
    {
        $format = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx,docx,pdf'],
        ])['format'] ?? 'csv';

        $customers = $this->filteredQuery($request)
            ->withCount([
                'orders as total_trips' => fn ($q) => $q->where('type', 'ride'),
                'orders as total_parcels' => fn ($q) => $q->where('type', 'parcel'),
            ])
            ->get();

        $rows = $customers->map(fn ($c) => [
            $c->id, $c->name, $c->phone, $c->email ?: '—',
            number_format((float) $c->wallet_balance, 2),
            number_format((float) $c->due_amount, 2),
            $c->total_trips, $c->total_parcels, $c->referral_code ?: '—',
            $c->is_active ? 'Active' : 'Blocked',
            $c->created_at?->format('d M Y'),
        ])->all();

        $stamp = now()->format('Ymd-His');
        $title = __('admin.customers');

        return match ($format) {
            'xlsx' => TableExport::xlsx("customers-{$stamp}.xlsx", $title, self::EXPORT_HEADERS, $rows),
            'docx' => TableExport::docx(
                "customers-{$stamp}.docx",
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
            default => TableExport::csv("customers-{$stamp}.csv", self::EXPORT_HEADERS, $rows),
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
        if ($request->filled('has_balance')) {
            $labels[__('admin.wallet')] = $request->input('has_balance') === 'yes'
                ? __('admin.has_balance')
                : __('admin.no_balance');
        }
        if ($request->filled('from') || $request->filled('to')) {
            $labels[__('admin.joined')] = trim(($request->from ?? '…') . ' → ' . ($request->to ?? '…'));
        }

        return $labels;
    }

    /** Blank CSV with just the import headers, so buyers know the column shape. */
    public function importTemplate(): StreamedResponse
    {
        return TableExport::csv('customers-import-template.csv', self::IMPORT_HEADERS, [
            ['Rahim Uddin', '01700000099', 'rahim@example.com', 'active'],
        ]);
    }

    /**
     * Create or update customers from an uploaded CSV/Excel file.
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

        foreach ($rows as $i => $row) {
            $line = $i + 2; // header is line 1
            $name = trim((string) ($row['name'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));

            if ($name === '' || $phone === '') {
                $errors[] = __('admin.row') . " {$line}: name/phone missing";
                continue;
            }

            $isActive = strtolower(trim((string) ($row['status'] ?? 'active'))) !== 'blocked';

            $existing = User::where('phone', $phone)->first();
            $attributes = [
                'name' => $name,
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'is_active' => $isActive,
            ];

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                User::create($attributes + [
                    'phone' => $phone,
                    'referral_code' => $this->uniqueReferralCode($name),
                    'wallet_balance' => 0,
                ]);
                $created++;
            }
        }

        $message = __('admin.import_summary') . ": {$created} " . __('admin.created')
            . ", {$updated} " . __('admin.updated')
            . ', ' . count($errors) . ' ' . __('admin.skipped') . '.';

        return back()->with($errors ? 'warning' : 'success', $message . ($errors ? ' — ' . implode('; ', array_slice($errors, 0, 5)) : ''));
    }

    // ---------------------------------------------------------------------

    // Base filtered query shared by index + export.
    private function filteredQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search')->toString();
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->active())
            ->when($request->input('status') === 'blocked', fn ($q) => $q->blocked())
            ->when($request->input('has_balance') === 'yes', fn ($q) => $q->where('wallet_balance', '>', 0))
            ->when($request->input('has_balance') === 'no', fn ($q) => $q->where('wallet_balance', '<=', 0))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to));
    }

    private function stats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::active()->count(),
            'blocked' => User::blocked()->count(),
            'wallet_total' => (float) User::sum('wallet_balance'),
        ];
    }

    // Notify the customer in-app and via push (FCM, when configured).
    private function notifyCustomer(User $customer, string $title, string $body, string $type): void
    {
        app(\App\Services\NotificationService::class)->sendPush('user', $customer->id, $title, $body, $type);
    }
}
