import 'package:geolocator/geolocator.dart';

class LocationResult {
  final bool isSuccess;
  final double? latitude;
  final double? longitude;
  final double? accuracyMeters;
  final String? errorMessage;
  final bool isPermanentlyDenied;
  final bool isServiceDisabled;

  LocationResult._({
    required this.isSuccess,
    this.latitude,
    this.longitude,
    this.accuracyMeters,
    this.errorMessage,
    this.isPermanentlyDenied = false,
    this.isServiceDisabled = false,
  });

  factory LocationResult.success({
    required double latitude,
    required double longitude,
    required double accuracyMeters,
  }) {
    return LocationResult._(
      isSuccess: true,
      latitude: latitude,
      longitude: longitude,
      accuracyMeters: accuracyMeters,
    );
  }

  factory LocationResult.failure(
    String message, {
    bool isPermanentlyDenied = false,
    bool isServiceDisabled = false,
  }) {
    return LocationResult._(
      isSuccess: false,
      errorMessage: message,
      isPermanentlyDenied: isPermanentlyDenied,
      isServiceDisabled: isServiceDisabled,
    );
  }
}

class LocationService {
  Future<LocationResult> getCurrentLocation() async {
    // 1. Check if location services are enabled on the device
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return LocationResult.failure(
        'Layanan lokasi (GPS) pada perangkat sedang dinonaktifkan. Silakan aktifkan GPS di pengaturan perangkat Anda.',
        isServiceDisabled: true,
      );
    }

    // 2. Check current permission status
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return LocationResult.failure(
          'Izin akses lokasi ditolak. GeoAttend memerlukan akses GPS untuk memverifikasi kehadiran di lokasi kerja.',
        );
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return LocationResult.failure(
        'Izin akses lokasi ditolak secara permanen. Buka Pengaturan Aplikasi dan berikan izin lokasi agar dapat melakukan presensi.',
        isPermanentlyDenied: true,
      );
    }

    // 3. Acquire current location with high accuracy
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );

      // Validate latitude and longitude bounds
      if (position.latitude < -90 || position.latitude > 90) {
        return LocationResult.failure('Koordinat latitude tidak valid (${position.latitude}).');
      }
      if (position.longitude < -180 || position.longitude > 180) {
        return LocationResult.failure('Koordinat longitude tidak valid (${position.longitude}).');
      }
      if (position.accuracy <= 0) {
        return LocationResult.failure('Akurasi GPS tidak valid atau sensor belum siap.');
      }

      return LocationResult.success(
        latitude: position.latitude,
        longitude: position.longitude,
        accuracyMeters: position.accuracy,
      );
    } catch (e) {
      return LocationResult.failure('Gagal membaca koordinat GPS: ${e.toString()}');
    }
  }

  Future<bool> openAppSettings() async {
    return await Geolocator.openAppSettings();
  }

  Future<bool> openLocationSettings() async {
    return await Geolocator.openLocationSettings();
  }
}
