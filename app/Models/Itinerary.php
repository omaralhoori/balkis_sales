<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'destinations',
        'arriving_date',
        'leaving_date',
        'total_days',
        'total_nights',
        'data',
        'is_pinned',
        'deposit',
        'customer_whatsapp',
    ];

    protected $casts = [
        'destinations' => 'array',
        'data' => 'array',
        'arriving_date' => 'date',
        'leaving_date' => 'date',
        'is_pinned' => 'boolean',
        'deposit' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
