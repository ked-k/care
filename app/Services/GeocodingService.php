<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Batch 35: turns a service user's free-text address into lat/long for the
 * geofenced check-in restriction, via OpenStreetMap's Nominatim — free and
 * keyless, so the feature works out of the box on a fresh install with no
 * API key to configure. Nominatim's usage policy
 * (https://operations.osmfoundation.org/policies/nominatim/) requires a
 * descriptive User-Agent and caps free usage at roughly one request per
 * second, both respected here.
 *
 * If an agency's volume outgrows that, swap this class for a paid provider
 * (Google, Mapbox, Ordnance Survey) behind the same geocode() signature —
 * nothing else in the app (ServiceUser::geocodeAddress(), the service-user
 * form) needs to change.
 */
class GeocodingService
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'CareAffinity').' geocoding ('.(config('mail.from.address') ?: 'no-reply@example.com').')',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('GeocodingService: non-2xx response', ['address' => $address, 'status' => $response->status()]);

                return null;
            }

            $result = $response->json(0);

            if (empty($result['lat']) || empty($result['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
            ];
        } catch (\Throwable $e) {
            // A geocoding failure (network hiccup, rate limit, an address
            // too vague to resolve) should never block saving a service
            // user's record — it just means the geofence isn't enforced
            // for them until an address/manual pin resolves.
            Log::warning('GeocodingService: lookup failed — '.$e->getMessage(), ['address' => $address]);

            return null;
        }
    }
}
