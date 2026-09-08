<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        return $this->success($this->format($request->user()), 'Emergency contact fetched.');
    }

    public function store(Request $request)
    {
        $this->save($request, $request->user());

        return $this->success($this->format($request->user()->fresh()), 'Emergency contact saved.');
    }

    public function driverShow(Request $request)
    {
        return $this->success($this->format($request->user()), 'Emergency contact fetched.');
    }

    public function driverStore(Request $request)
    {
        $this->save($request, $request->user());

        return $this->success($this->format($request->user()->fresh()), 'Emergency contact saved.');
    }

    // ---------------------------------------------------------------------

    private function save(Request $request, $model): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'max:50'],
        ]);

        $model->update([
            'emergency_contact_name' => $data['name'],
            'emergency_contact_phone' => $data['phone'],
            'emergency_contact_relationship' => $data['relationship'] ?? null,
        ]);
    }

    private function format($model): array
    {
        return [
            'name' => $model->emergency_contact_name,
            'phone' => $model->emergency_contact_phone,
            'relationship' => $model->emergency_contact_relationship,
        ];
    }
}
