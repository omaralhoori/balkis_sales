<?php

namespace App\Models\Concerns;

use App\Support\ReferenceData;

/**
 * Flushes the cached itinerary reference data whenever a model that feeds it is
 * created, updated or deleted (e.g. via the Filament admin panel), so employees
 * always see up-to-date cars, accommodations, tours and destinations.
 */
trait FlushesReferenceDataCache
{
    public static function bootFlushesReferenceDataCache(): void
    {
        static::saved(fn () => ReferenceData::flush());
        static::deleted(fn () => ReferenceData::flush());
    }
}
