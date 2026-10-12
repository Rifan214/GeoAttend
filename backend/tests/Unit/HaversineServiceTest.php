<?php

namespace Tests\Unit;

use App\Models\WorkLocation;
use App\Services\HaversineService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HaversineServiceTest extends TestCase
{
    protected HaversineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HaversineService();
    }

    public function test_zero_distance_for_identical_points(): void
    {
        $distance = $this->service->calculateDistance(-6.1753924, 106.8271528, -6.1753924, 106.8271528);
        $this->assertEquals(0.0, $distance);
    }

    public function test_known_distance_between_jakarta_landmarks(): void
    {
        // Monas (-6.175392, 106.827153) to Bundaran HI (-6.195022, 106.823055)
        // Straight line distance is approximately 2.22 km (2200m - 2250m)
        $distance = $this->service->calculateDistance(-6.175392, 106.827153, -6.195022, 106.823055);
        $this->assertGreaterThan(2200.0, $distance);
        $this->assertLessThan(2250.0, $distance);
    }

    public function test_coordinate_validation(): void
    {
        $this->assertTrue($this->service->isValidCoordinates(-6.175, 106.827));
        $this->assertTrue($this->service->isValidCoordinates(0.0, 0.0));
        $this->assertTrue($this->service->isValidCoordinates(-90.0, 180.0));
        $this->assertTrue($this->service->isValidCoordinates(90.0, -180.0));

        // Invalid latitudes
        $this->assertFalse($this->service->isValidCoordinates(-90.1, 106.827));
        $this->assertFalse($this->service->isValidCoordinates(90.1, 106.827));

        // Invalid longitudes
        $this->assertFalse($this->service->isValidCoordinates(0.0, -180.1));
        $this->assertFalse($this->service->isValidCoordinates(0.0, 180.1));

        // Non-numeric or non-finite
        $this->assertFalse($this->service->isValidCoordinates('invalid', 106.827));
        $this->assertFalse($this->service->isValidCoordinates(NAN, 106.827));
        $this->assertFalse($this->service->isValidCoordinates(INF, 106.827));
    }

    public function test_accuracy_validation(): void
    {
        // Valid accuracy
        $this->assertTrue($this->service->isValidAccuracy(10.0, 50));
        $this->assertTrue($this->service->isValidAccuracy(50.0, 50));
        $this->assertTrue($this->service->isValidAccuracy('25.5', 50));

        // Exceeding accuracy threshold
        $this->assertFalse($this->service->isValidAccuracy(50.1, 50));
        $this->assertFalse($this->service->isValidAccuracy(100.0, 50));

        // Zero, negative, non-finite
        $this->assertFalse($this->service->isValidAccuracy(0.0, 50));
        $this->assertFalse($this->service->isValidAccuracy(-5.0, 50));
        $this->assertFalse($this->service->isValidAccuracy('abc', 50));
        $this->assertFalse($this->service->isValidAccuracy(NAN, 50));
    }

    public function test_calculate_distance_throws_for_invalid_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculateDistance(999.0, 0.0, 0.0, 0.0);
    }

    public function test_radius_boundary_check(): void
    {
        $this->assertTrue($this->service->isWithinRadius(100.0, 100));
        $this->assertTrue($this->service->isWithinRadius(99.9, 100));
        $this->assertFalse($this->service->isWithinRadius(100.1, 100));
    }

    public function test_validate_location_with_mock_model(): void
    {
        $location = new WorkLocation([
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        // Inside radius (exact same point)
        $result = $this->service->validateLocation(-6.1753924, 106.8271528, 15.0, $location);
        $this->assertTrue($result['is_valid']);
        $this->assertNull($result['error_message']);
        $this->assertEquals(0.0, $result['distance_meters']);

        // Outside radius (Bundaran HI)
        $outsideResult = $this->service->validateLocation(-6.195022, 106.823055, 15.0, $location);
        $this->assertFalse($outsideResult['is_valid']);
        $this->assertNotNull($outsideResult['error_message']);
        $this->assertGreaterThan(100.0, $outsideResult['distance_meters']);

        // Poor accuracy (> 50m)
        $badAccuracyResult = $this->service->validateLocation(-6.1753924, 106.8271528, 75.0, $location);
        $this->assertFalse($badAccuracyResult['is_valid']);
        $this->assertStringContainsString('Akurasi GPS', $badAccuracyResult['error_message']);
    }
}
