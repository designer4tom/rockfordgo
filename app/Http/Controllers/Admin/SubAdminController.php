<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SubAdminController extends Controller
{
    // Modules that can be granted, with the abilities applicable to each.
    public const MODULES = [
        'dashboard' => ['read'],
        'users' => ['read', 'write', 'delete'],
        'drivers' => ['read', 'write', 'delete'],
        'orders' => ['read', 'write'],
        'payments' => ['read', 'write'],
        'settings' => ['read', 'write'],
        'reports' => ['read'],
        'sos' => ['read', 'write'],
        'disputes' => ['read', 'write'],
    ];

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $admins = Admin::query()
            ->whereIn('role', ['sub_admin', 'fleet_manager'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.sub-admins.index', compact('admins', 'search'));
    }

    public function create()
    {
        return view('admin.sub-admins.create', [
            'modules' => self::MODULES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['sub_admin', 'fleet_manager'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $admin = Admin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncPermissions($admin, $request->input('permissions', []));
        });

        return redirect()->route('admin.sub-admins.index')
            ->with('success', 'Sub-admin created successfully.');
    }

    public function edit(string $id)
    {
        $admin = Admin::whereIn('role', ['sub_admin', 'fleet_manager'])
            ->with('permissions')
            ->findOrFail($id);

        // Build a lookup: [module => [read=>bool, write=>bool, delete=>bool]]
        $current = [];
        foreach ($admin->permissions as $permission) {
            $current[$permission->module] = [
                'read' => (bool) $permission->can_read,
                'write' => (bool) $permission->can_write,
                'delete' => (bool) $permission->can_delete,
            ];
        }

        return view('admin.sub-admins.edit', [
            'admin' => $admin,
            'modules' => self::MODULES,
            'current' => $current,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $admin = Admin::whereIn('role', ['sub_admin', 'fleet_manager'])->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['sub_admin', 'fleet_manager'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data, $admin) {
            $admin->name = $data['name'];
            $admin->email = $data['email'];
            $admin->role = $data['role'];
            $admin->is_active = $request->boolean('is_active');

            // Only change the password when a new one is provided.
            if (! empty($data['password'])) {
                $admin->password = Hash::make($data['password']);
            }

            $admin->save();

            $this->syncPermissions($admin, $request->input('permissions', []));
        });

        return redirect()->route('admin.sub-admins.index')
            ->with('success', 'Sub-admin updated successfully.');
    }

    public function destroy(string $id)
    {
        $admin = Admin::whereIn('role', ['sub_admin', 'fleet_manager'])->findOrFail($id);

        // Permissions are removed automatically via FK cascade.
        $admin->delete();

        return redirect()->route('admin.sub-admins.index')
            ->with('success', 'Sub-admin deleted.');
    }

    public function toggleStatus(string $id)
    {
        $admin = Admin::whereIn('role', ['sub_admin', 'fleet_manager'])->findOrFail($id);
        $admin->is_active = ! $admin->is_active;
        $admin->save();

        return back()->with('success', 'Status updated for ' . $admin->name . '.');
    }

    // Replace the admin's permission rows with the submitted matrix.
    private function syncPermissions(Admin $admin, array $submitted): void
    {
        $admin->permissions()->delete();

        foreach (self::MODULES as $module => $abilities) {
            $row = $submitted[$module] ?? [];

            $canRead = ! empty($row['read']);
            $canWrite = ! empty($row['write']);
            $canDelete = ! empty($row['delete']);

            // Skip modules with nothing granted.
            if (! $canRead && ! $canWrite && ! $canDelete) {
                continue;
            }

            $admin->permissions()->create([
                'module' => $module,
                'can_read' => $canRead,
                'can_write' => $canWrite,
                'can_delete' => $canDelete,
            ]);
        }
    }
}
