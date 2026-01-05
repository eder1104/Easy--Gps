<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehiculo extends Model
{
    protected $fillable = [
        'user_id',
        'traccar_device_id',
        'placa',
        'plan'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}