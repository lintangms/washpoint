<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $table = 'service_types';

    protected $fillable = [
        'user_id',
        'name',
        'price_per_kg',
        'estimated_hours',
        'is_active',
    ];

    protected $casts = [
        'price_per_kg' => 'integer',
        'estimated_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function laundryOrders()
    {
        return $this->hasMany(LaundryOrder::class);
    }
}

