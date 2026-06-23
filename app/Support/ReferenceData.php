<?php

namespace App\Support;

use App\Models\Accommodation;
use App\Models\Car;
use App\Models\Destination;
use App\Models\Tour;
use Illuminate\Support\Collection;
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
 *
 * IMPORTANT: we cache plain attribute arrays (pure scalars), NOT Eloquent
 * collections/models. Serialising model objects into the cache store proved
 * fragile in production (unserialize() returned an "incomplete object" for the
 * cached collection). Caching raw arrays is always safe to serialise; we
 * rehydrate them back into real models on read, which is cheap and in-memory.
 */
class ReferenceData
{
    /** Versioned key: bumping it transparently invalidates any stale cache. */
    public const CACHE_KEY = 'itinerary_reference_data_v2';

    /** Cache lifetime in seconds (24h). The cache is also flushed on every edit. */
    public const TTL = 86400;

    /** Per-request memoisation so we don't rebuild the derived views repeatedly. */
    protected static ?array $memo = null;

    /**
     * Get all reference data (flat lists plus derived keyed/grouped views).
     *
     * @return array<string, Collection>
     */
    public static function get(): array
    {
        if (static::$memo !== null) {
            return static::$memo;
        }

        $raw = Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return [
                'cars' => Car::all()->map->getAttributes()->all(),
                'accommodations' => Accommodation::all()->map->getAttributes()->all(),
                'tours' => Tour::query()
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name', 'asc')
                    ->get()
                    ->map->getAttributes()
                    ->all(),
                'destinations' => Destination::all()->map->getAttributes()->all(),
            ];
        });

        $cars = Car::hydrate($raw['cars']);
        $accommodations = Accommodation::hydrate($raw['accommodations']);
        $tours = Tour::hydrate($raw['tours']);
        $destinations = Destination::hydrate($raw['destinations']);

        return static::$memo = [
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
    }

    /**
     * Invalidate the cached reference data so the next request rebuilds it.
     */
    public static function flush(): void
    {
        static::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }
}
