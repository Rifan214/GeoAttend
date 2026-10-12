<?php

namespace App\Services;

use App\Models\WorkLocation;
use InvalidArgumentException;

class HaversineService
{
    /**
     * Earth radius in meters.
     */
    public const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Default maximum GPS accuracy threshold in meters.
     */
    public const DEFAULT_MAX_ACCURACY_METERS = 50;

    /**
     * Validate whether latitude and longitude are valid numeric finite coordinates.
     */
    public function isValidCoordinates(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if (! is_finite($lat) || ! is_finite($lng)) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }

    /**
     * Validate whether the reported GPS accuracy is positive, finite, and within threshold.
     */
    public function isValidAccuracy(mixed $accuracy, int $maxAccuracyMeters = self::DEFAULT_MAX_ACCURACY_METERS): bool
    {
        if (! is_numeric($accuracy)) {
            return false;
        }

        $acc = (float) $accuracy;

        if (! is_finite($acc) || $acc <= 0.0) {
            return false;
        }

        return $acc <= $maxAccuracyMeters;
    }

    /**
     * Calculate great-circle distance between two points in meters using Haversine formula.
     *
     * @throws InvalidArgumentException
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (! $this->isValidCoordinates($lat1, $lon1) || ! $this->isValidCoordinates($lat2, $lon2)) {
            throw new InvalidArgumentException('Koordinat tidak valid untuk perhitungan jarak Haversine.');
        }

        if ($lat1 === $lat2 && $lon1 === $lon2) {
            return 0.0;
        }

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return round($angle * self::EARTH_RADIUS_METERS, 2);
    }

    /**
     * Check if distance is within the allowed radius.
     */
    public function isWithinRadius(float $distanceMeters, int $radiusMeters): bool
    {
        return $distanceMeters <= (float) $radiusMeters;
    }

    /**
     * Validate full geofence criteria against a work location.
     *
     * @return array{is_valid: bool, distance_meters: float, error_message: ?string}
     */
    public function validateLocation(
        float $userLat,
        float $userLng,
        float $userAccuracy,
        WorkLocation $location
    ): array {
        if (! $this->isValidCoordinates($userLat, $userLng)) {
            return [
                'is_valid' => false,
                'distance_meters' => 0.0,
                'error_message' => 'Koordinat GPS yang dikirim tidak valid.',
            ];
        }

        $maxAccuracy = $location->max_accuracy_meters ?: self::DEFAULT_MAX_ACCURACY_METERS;
        if (! $this->isValidAccuracy($userAccuracy, $maxAccuracy)) {
            return [
                'is_valid' => false,
                'distance_meters' => 0.0,
                'error_message' => "Akurasi GPS ({$userAccuracy}m) melebihi batas toleransi maksimum ({$maxAccuracy}m). Pastikan Anda berada di area terbuka.",
            ];
        }

        $distance = $this->calculateDistance(
            $userLat,
            $userLng,
            (float) $location->latitude,
            (float) $location->longitude
        );

        $allowedRadius = (int) $location->radius_meters;
        if (! $this->isWithinRadius($distance, $allowedRadius)) {
            return [
                'is_valid' => false,
                'distance_meters' => $distance,
                'error_message' => "Anda berada di luar radius lokasi kerja. Jarak Anda {$distance} meter, sedangkan batas radius adalah {$allowedRadius} meter.",
            ];
        }

        return [
            'is_valid' => true,
            'distance_meters' => $distance,
            'error_message' => null,
        ];
    }
}
