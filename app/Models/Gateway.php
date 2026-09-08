<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MultiPay gateway credentials row (name + JSON `data`). This is the table the
 * package reads/writes via config('multipay.data_table'). The runtime on/off
 * switch lives inside `data->is_active`.
 */
class Gateway extends Model
{
    protected $table = 'gateways';

    protected $fillable = ['name', 'data'];
}
