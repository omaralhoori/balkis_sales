<?php

namespace App\Models;

use Database\Factories\AccommodationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    /** @use HasFactory<AccommodationFactory> */
    use HasFactory;

    protected $guarded = [];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }
}
