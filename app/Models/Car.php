<?php

namespace App\Models;

use App\Models\Concerns\FlushesReferenceDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use FlushesReferenceDataCache, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }
}
