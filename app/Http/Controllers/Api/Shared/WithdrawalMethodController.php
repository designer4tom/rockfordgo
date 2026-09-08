<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use App\Traits\ApiResponse;

class WithdrawalMethodController extends Controller
{
    use ApiResponse;

    // Active withdrawal methods for the customer/driver app withdraw screen.
    public function index()
    {
        $methods = WithdrawalMethod::active()->get()->map(fn (WithdrawalMethod $m) => [
            'code' => $m->code,
            'name' => $m->name,
            'instructions' => $m->instructions,
        ])->values();

        return $this->success($methods, 'Withdrawal methods fetched.');
    }
}
