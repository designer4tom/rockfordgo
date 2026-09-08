<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class WithdrawalMethodController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments,read', only: ['index']),
            new Middleware('permission:payments,write', only: ['store', 'update', 'toggleStatus']),
            new Middleware('permission:payments,delete', only: ['destroy']),
        ];
    }

    public function index()
    {
        $methods = WithdrawalMethod::orderBy('sort_order')->get();

        return view('admin.payments.withdrawal-methods.index', compact('methods'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('withdrawal_methods', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = true;
        WithdrawalMethod::create($data);

        return back()->with('success', 'Withdrawal method added.');
    }

    public function update(Request $request, string $id)
    {
        $method = WithdrawalMethod::findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('withdrawal_methods', 'code')->ignore($method->id)],
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $method->update($data);

        return back()->with('success', 'Withdrawal method updated.');
    }

    public function toggleStatus(string $id)
    {
        $method = WithdrawalMethod::findOrFail($id);
        $method->is_active = ! $method->is_active;
        $method->save();

        return back()->with('success', 'Status updated for ' . $method->name . '.');
    }

    public function destroy(string $id)
    {
        WithdrawalMethod::findOrFail($id)->delete();

        return back()->with('success', 'Withdrawal method deleted.');
    }
}
