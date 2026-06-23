<?php

namespace App\Models;

use App\Models\Concerns\FlushesReferenceDataCache;
use Database\Factories\TourFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use FlushesReferenceDataCache;

    protected $guarded = [];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}
