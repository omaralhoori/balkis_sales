<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Itinerary extends Model
{
    use SoftDeletes;

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
        'deleted_by',
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

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function logs()
    {
        return $this->hasMany(ItineraryLog::class)->orderBy('created_at', 'desc');
    }
}
