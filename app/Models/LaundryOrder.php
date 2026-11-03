<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryOrder extends Model
{
    use HasFactory;

    protected $table = 'laundry_orders';

    protected $fillable = [
        'user_id',
        'service_type_id',
        'customer_name',
        'customer_phone',
        'weight_kg',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'total_price' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function pickupSchedules()
    {
        return $this->hasMany(PickupSchedule::class);
    }

    public function orderStatusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}

