<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Driver;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Omnisearch for the admin top bar.
 *
 * Every group is gated by the same permission that guards its own screen, so
 * the palette can never surface a record the admin could not open anyway.
 */
class GlobalSearchController extends Controller
{
    private const PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%' . $q . '%';
        $groups = [];

        if ($pages = $this->pages($q)) {
            $groups[] = ['label' => __('admin.pages'), 'icon' => 'nav', 'items' => $pages];
        }

        if (adminCan('drivers', 'read')) {
            $rows = Driver::query()
                ->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->limit(self::PER_GROUP)
                ->get(['id', 'name', 'phone', 'status']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'label' => __('admin.drivers'),
                    'icon' => 'driver',
                    'items' => $rows->map(fn ($d) => [
                        'title' => $d->name,
                        'meta' => $d->phone . ' · ' . ucfirst($d->status),
                        'url' => route('admin.drivers.show', $d->id),
                    ])->all(),
                ];
            }
        }

        if (adminCan('users', 'read')) {
            $rows = User::query()
                ->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('referral_code', 'like', $like))
                ->limit(self::PER_GROUP)
                ->get(['id', 'name', 'phone', 'is_active']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'label' => __('admin.customers'),
                    'icon' => 'customer',
                    'items' => $rows->map(fn ($u) => [
                        'title' => $u->name,
                        'meta' => maskPhone($u->phone) . ' · ' . ($u->is_active ? __('admin.active') : __('admin.blocked')),
                        'url' => route('admin.customers.show', $u->id),
                    ])->all(),
                ];
            }
        }

        if (adminCan('orders', 'read')) {
            $rows = Order::query()
                ->where(fn ($w) => $w->where('order_number', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', $like)->orWhere('name', 'like', $like))
                    ->orWhereHas('driver', fn ($d) => $d->where('phone', 'like', $like)->orWhere('name', 'like', $like)))
                ->latest()
                ->limit(self::PER_GROUP)
                ->get(['id', 'order_number', 'type', 'status', 'total_amount']);

            if ($rows->isNotEmpty()) {
                $groups[] = [
                    'label' => __('admin.orders'),
                    'icon' => 'order',
                    'items' => $rows->map(fn ($o) => [
                        'title' => $o->order_number,
                        'meta' => ucfirst($o->type) . ' · ' . ucfirst(str_replace('_', ' ', $o->status)),
                        'url' => route('admin.orders.show', $o->id),
                    ])->all(),
                ];
            }
        }

        if (adminCan('settings', 'read')) {
            $zones = Zone::where('name', 'like', $like)->limit(self::PER_GROUP)->get(['id', 'name', 'is_active']);
            if ($zones->isNotEmpty()) {
                $groups[] = [
                    'label' => __('admin.zones'),
                    'icon' => 'zone',
                    'items' => $zones->map(fn ($z) => [
                        'title' => $z->name,
                        'meta' => $z->is_active ? __('admin.active') : __('admin.inactive'),
                        'url' => route('admin.zones.show', $z->id),
                    ])->all(),
                ];
            }

            $coupons = Coupon::where('code', 'like', $like)->limit(self::PER_GROUP)->get(['id', 'code', 'is_active']);
            if ($coupons->isNotEmpty()) {
                $groups[] = [
                    'label' => __('admin.coupons'),
                    'icon' => 'coupon',
                    'items' => $coupons->map(fn ($c) => [
                        'title' => $c->code,
                        'meta' => $c->is_active ? __('admin.active') : __('admin.inactive'),
                        'url' => route('admin.coupons.edit', $c->id),
                    ])->all(),
                ];
            }
        }

        return response()->json(['groups' => $groups]);
    }

    /** Admin screens whose label matches the query — lets the palette act as a jump list. */
    private function pages(string $q): array
    {
        $candidates = [
            ['admin.dashboard', __('admin.dashboard'), null, null],
            ['admin.drivers.index', __('admin.drivers'), 'drivers', 'read'],
            ['admin.drivers.pending', __('admin.drivers') . ' — ' . __('admin.pending'), 'drivers', 'read'],
            ['admin.drivers.expiring-documents', __('admin.expiring_docs'), 'drivers', 'read'],
            ['admin.customers.index', __('admin.customers'), 'users', 'read'],
            ['admin.referrals.index', __('admin.referrals'), 'users', 'read'],
            ['admin.orders.index', __('admin.orders'), 'orders', 'read'],
            ['admin.orders.scheduled', __('admin.scheduled'), 'orders', 'read'],
            ['admin.zones.index', __('admin.zones'), 'settings', 'read'],
            ['admin.services.index', __('admin.services'), 'settings', 'read'],
            ['admin.vehicle-categories.index', __('admin.vehicle_categories'), 'settings', 'read'],
            ['admin.parcel-pricing.index', __('admin.parcel_pricing'), 'settings', 'read'],
            ['admin.surge-pricing.index', __('admin.surge_pricing'), 'settings', 'read'],
            ['admin.coupons.index', __('admin.coupons'), 'settings', 'read'],
            ['admin.sos.index', __('admin.sos_alerts'), 'sos', 'read'],
            ['admin.disputes.index', __('admin.complaints'), 'disputes', 'read'],
            ['admin.payments.transactions', __('admin.transactions'), 'payments', 'read'],
            ['admin.payments.withdrawals', __('admin.withdrawals'), 'payments', 'read'],
            ['admin.payments.refunds', __('admin.refunds'), 'payments', 'read'],
            ['admin.payments.driver-dues', __('admin.driver_dues'), 'payments', 'read'],
            ['admin.payments.customer-dues', __('admin.customer_dues'), 'payments', 'read'],
            ['admin.notifications.broadcast', __('admin.notifications'), 'settings', 'read'],
            ['admin.banners.index', __('admin.banners'), 'settings', 'read'],
            ['admin.website.index', __('admin.website_cms'), 'settings', 'read'],
            ['admin.settings.general', __('admin.settings'), 'settings', 'read'],
            ['admin.languages.index', __('admin.languages'), 'settings', 'read'],
            ['admin.sub-admins.index', __('admin.sub_admins'), 'settings', 'read'],
            ['admin.login-history', __('admin.login_history'), null, null],
            ['admin.profile', __('admin.my_profile'), null, null],
            ['admin.setup-guide', __('admin.setup_guide'), null, null],
        ];

        $matches = [];
        foreach ($candidates as [$route, $label, $module, $ability]) {
            if ($module && ! adminCan($module, $ability)) {
                continue;
            }
            if (mb_stripos($label, $q) === false) {
                continue;
            }
            $matches[] = ['title' => $label, 'meta' => __('admin.go_to_page'), 'url' => route($route)];
            if (count($matches) >= self::PER_GROUP) {
                break;
            }
        }

        return $matches;
    }
}
