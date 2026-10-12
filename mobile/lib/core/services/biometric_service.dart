import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

enum BiometricStatus {
  success,
  failed,
  canceled,
  notAvailable,
  notEnrolled,
}

class BiometricResult {
  final BiometricStatus status;
  final String message;

  BiometricResult({required this.status, required this.message});

  bool get isSuccess => status == BiometricStatus.success;
  bool get isCanceled => status == BiometricStatus.canceled;
  bool get isNotAvailable => status == BiometricStatus.notAvailable;
  bool get isNotEnrolled => status == BiometricStatus.notEnrolled;
}

class BiometricService {
  final LocalAuthentication _auth;

  BiometricService({LocalAuthentication? auth}) : _auth = auth ?? LocalAuthentication();

  Future<bool> isDeviceSupported() async {
    try {
      return await _auth.isDeviceSupported();
    } catch (_) {
      return false;
    }
  }

  Future<bool> canCheckBiometrics() async {
    try {
      return await _auth.canCheckBiometrics;
    } catch (_) {
      return false;
    }
  }

  Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _auth.getAvailableBiometrics();
    } catch (_) {
      return [];
    }
  }

  /// Performs device-local biometric authentication.
  /// Strictly requires biometric authentication (biometricOnly: true),
  /// preventing fallbacks to device lockscreen PIN, pattern, or password.
  ///
  /// No biometric templates, images, or data are transmitted over the network or to the server.
  Future<BiometricResult> authenticate({
    required String actionName,
  }) async {
    try {
      final isSupported = await isDeviceSupported();
      if (!isSupported) {
        return BiometricResult(
          status: BiometricStatus.notAvailable,
          message: 'Perangkat ini tidak mendukung fitur autentikasi biometrik lokal.',
        );
      }

      final canCheck = await canCheckBiometrics();
      if (!canCheck) {
        return BiometricResult(
          status: BiometricStatus.notAvailable,
          message: 'Sensor biometrik tidak dapat diakses atau sedang dinonaktifkan.',
        );
      }

      final available = await getAvailableBiometrics();
      if (available.isEmpty) {
        return BiometricResult(
          status: BiometricStatus.notEnrolled,
          message: 'Biometrik belum didaftarkan pada perangkat. Silakan daftarkan sidik jari di pengaturan perangkat terlebih dahulu.',
        );
      }

      final authenticated = await _auth.authenticate(
        localizedReason: 'Konfirmasi biometrik perangkat untuk memverifikasi tindakan $actionName.',
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );

      if (authenticated) {
        return BiometricResult(
          status: BiometricStatus.success,
          message: 'Autentikasi biometrik berhasil dikonfirmasi.',
        );
      } else {
        return BiometricResult(
          status: BiometricStatus.canceled,
          message: 'Autentikasi biometrik dibatalkan oleh pengguna.',
        );
      }
    } on LocalAuthException catch (e) {
      switch (e.code) {
        case LocalAuthExceptionCode.userCanceled:
          return BiometricResult(
            status: BiometricStatus.canceled,
            message: 'Autentikasi biometrik dibatalkan oleh pengguna.',
          );
        case LocalAuthExceptionCode.noBiometricHardware:
        case LocalAuthExceptionCode.biometricHardwareTemporarilyUnavailable:
          return BiometricResult(
            status: BiometricStatus.notAvailable,
            message: 'Sensor biometrik tidak tersedia pada perangkat ini.',
          );
        case LocalAuthExceptionCode.noBiometricsEnrolled:
        case LocalAuthExceptionCode.noCredentialsSet:
          return BiometricResult(
            status: BiometricStatus.notEnrolled,
            message: 'Belum ada sidik jari/biometrik yang didaftarkan pada perangkat.',
          );
        case LocalAuthExceptionCode.temporaryLockout:
          return BiometricResult(
            status: BiometricStatus.failed,
            message: 'Sensor biometrik terkunci sementara karena terlalu banyak percobaan gagal. Silakan coba lagi nanti.',
          );
        case LocalAuthExceptionCode.biometricLockout:
          return BiometricResult(
            status: BiometricStatus.failed,
            message: 'Sensor biometrik terkunci permanen. Buka kunci perangkat secara manual terlebih dahulu.',
          );
        default:
          return BiometricResult(
            status: BiometricStatus.failed,
            message: 'Gagal melakukan verifikasi biometrik: ${e.description ?? e.code.name}',
          );
      }
    } on PlatformException catch (e) {
      if (e.code == 'NotAvailable') {
        return BiometricResult(
          status: BiometricStatus.notAvailable,
          message: 'Sensor biometrik tidak tersedia saat ini.',
        );
      } else if (e.code == 'NotEnrolled' || e.code == 'PasscodeNotSet') {
        return BiometricResult(
          status: BiometricStatus.notEnrolled,
          message: 'Belum ada sidik jari/biometrik yang terdaftar di perangkat.',
        );
      } else if (e.code == 'LockedOut') {
        return BiometricResult(
          status: BiometricStatus.failed,
          message: 'Sensor biometrik terkunci sementara karena terlalu banyak percobaan gagal. Buka kunci perangkat terlebih dahulu.',
        );
      } else if (e.code == 'PermanentlyLockedOut') {
        return BiometricResult(
          status: BiometricStatus.failed,
          message: 'Sensor biometrik terkunci permanen. Buka kunci perangkat secara manual terlebih dahulu.',
        );
      }

      return BiometricResult(
        status: BiometricStatus.failed,
        message: 'Gagal melakukan verifikasi biometrik: ${e.message ?? e.code}',
      );
    } catch (e) {
      return BiometricResult(
        status: BiometricStatus.failed,
        message: 'Terjadi kesalahan pada verifikasi biometrik lokal: $e',
      );
    }
  }
}
