<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class CouponController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings,read', only: ['index', 'usages']),
            new Middleware('permission:settings,write', only: ['create', 'store', 'edit', 'update', 'toggleStatus']),
            new Middleware('permission:settings,delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $coupons = Coupon::query()
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['used_count'] = 0;

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(string $id)
    {
        $coupon = Coupon::findOrFail($id);

        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, string $id)
    {
        $coupon = Coupon::findOrFail($id);
        $data = $this->validateData($request, $coupon->id);
        $data['is_active'] = $request->boolean('is_active');

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(string $id)
    {
        $coupon = Coupon::findOrFail($id);

        if ($coupon->usages()->exists()) {
            return back()->with('error', 'This coupon has usage history and cannot be deleted. Deactivate it instead.');
        }

        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }

    public function toggleStatus(string $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return back()->with('success', 'Status updated for ' . $coupon->code . '.');
    }

    public function usages(string $id)
    {
        $coupon = Coupon::findOrFail($id);

        $usages = $coupon->usages()
            ->with(['user:id,name,phone', 'order:id,order_number'])
            ->latest('created_at')
            ->paginate(20);

        return view('admin.coupons.usages', compact('coupon', 'usages'));
    }

    // ---------------------------------------------------------------------

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'service_type' => ['required', 'in:ride,parcel,all'],
        ]);
    }
}
