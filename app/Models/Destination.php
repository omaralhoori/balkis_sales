<?php

namespace App\Models;

use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    protected $guarded = [];

    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
}
