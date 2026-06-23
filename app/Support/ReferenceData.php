<?php

namespace App\Support;

use App\Models\Accommodation;
use App\Models\Car;
use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;

/**
 * Centralised, cached access to the static reference data used by the itinerary
 * generator (cars, accommodations, tours and destinations).
 *
 * These lists rarely change during a working session but were previously
 * re-queried on every Livewire roundtrip (and once per day-slot inside the
 * Blade loop), which caused the page to freeze when several employees were
 * building itineraries at the same time. We now load everything once and serve
 * it from the cache, flushing it whenever the underlying data is edited.
 */
class ReferenceData
{
    public const CACHE_KEY = 'itinerary_reference_data';

    /** Cache lifetime in seconds (24h). The cache is also flushed on every edit. */
    public const TTL = 86400;

    /**
     * Get all reference data as a single cached payload.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $accommodations = Accommodation::all();

            $tours = Tour::query()
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            $destinations = Destination::all();

            $cars = Car::all();

            return [
                'cars' => $cars,
                'carsById' => $cars->keyBy('id'),

                'accommodations' => $accommodations,
                'accommodationsById' => $accommodations->keyBy('id'),
                'accommodationsByDestination' => $accommodations->groupBy('destination_id'),

                'tours' => $tours,
                'toursById' => $tours->keyBy('id'),
                'toursByDestination' => $tours->groupBy('destination_id'),

                'accommodationDestinations' => $destinations->whereIn('type', ['accommodation', 'both'])->values(),
                'tourDestinations' => $destinations->whereIn('type', ['tour', 'both'])->values(),
            ];
        });
    }

    /**
     * Invalidate the cached reference data so the next request rebuilds it.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
