<?php

namespace App\Models;

use Database\Factories\TourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    /** @use HasFactory<TourFactory> */
    protected $guarded = [];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
