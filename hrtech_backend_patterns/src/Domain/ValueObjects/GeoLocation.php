<?php

declare(strict_types=1);

namespace HrTech\Domain\ValueObjects;

use HrTech\Exceptions\ValidationException;
use JsonSerializable;
use Stringable;

/**
 * GeoLocation Value Object.
 * Immutable GPS coordinates with spherical Haversine distance calculations for geofencing.
 */
readonly class GeoLocation implements Stringable, JsonSerializable
{
    private const float EARTH_RADIUS_METERS = 6371000.0;

    public float $latitude;
    public float $longitude;
    public ?float $accuracy;

    /**
     * @param float $latitude Range: -90.0 to +90.0 degrees
     * @param float $longitude Range: -180.0 to +180.0 degrees
     * @param float|null $accuracy Accuracy radius in meters (optional, >= 0.0)
     * @throws ValidationException if coordinates are out of bounds
     */
    public function __construct(float $latitude, float $longitude, ?float $accuracy = null)
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw ValidationException::forField(
                'latitude',
                "Latitude must be between -90.0 and +90.0 degrees, {$latitude} provided."
            );
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw ValidationException::forField(
                'longitude',
                "Longitude must be between -180.0 and +180.0 degrees, {$longitude} provided."
            );
        }

        if ($accuracy !== null && $accuracy < 0.0) {
            throw ValidationException::forField(
                'accuracy',
                "Accuracy must be non-negative meters, {$accuracy} provided."
            );
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    /**
     * Calculates great-circle distance to another point in meters using the Haversine formula.
     */
    public function distanceTo(GeoLocation $target): float
    {
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($target->latitude);
        $lonTo = deg2rad($target->longitude);

        $deltaLat = $latTo - $latFrom;
        $deltaLon = $lonTo - $lonFrom;

        $haversineA = (sin($deltaLat / 2) ** 2) +
            cos($latFrom) * cos($latTo) * (sin($deltaLon / 2) ** 2);

        $clampedA = min(1.0, max(0.0, $haversineA));
        $radicand = max(0.0, 1.0 - $haversineA);
        $angularDistanceC = 2 * atan2(sqrt($clampedA), sqrt($radicand));

        return self::EARTH_RADIUS_METERS * $angularDistanceC;
    }

    /**
     * Calculates great-circle distance in kilometers.
     */
    public function distanceToInKilometers(GeoLocation $target): float
    {
        return $this->distanceTo($target) / 1000.0;
    }

    /**
     * Checks if this point lies within a geofence radius centered at $center.
     */
    public function isWithinRadius(GeoLocation $center, float $radiusInMeters): bool
    {
        return $this->distanceTo($center) <= $radiusInMeters;
    }

    /**
     * Checks coordinate equality within a floating-point tolerance.
     */
    public function equals(GeoLocation $other, float $tolerance = 0.00001): bool
    {
        return abs($this->latitude - $other->latitude) < $tolerance &&
            abs($this->longitude - $other->longitude) < $tolerance;
    }

    /**
     * Formatted coordinate string: "lat, lon (±accuracy m)".
     */
    public function format(): string
    {
        $base = sprintf('%.6f, %.6f', $this->latitude, $this->longitude);
        if ($this->accuracy !== null) {
            $base .= sprintf(' (±%.1fm)', $this->accuracy);
        }
        return $base;
    }

    public function getFormatted(): string
    {
        return $this->format();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array{latitude: float, longitude: float, accuracy: float|null, formatted: string}
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'formatted' => $this->format(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
