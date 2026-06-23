<?php

namespace App\Models;

use App\Models\Concerns\FlushesReferenceDataCache;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use FlushesReferenceDataCache;

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
