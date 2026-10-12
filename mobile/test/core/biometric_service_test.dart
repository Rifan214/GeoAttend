import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/core/services/biometric_service.dart';
import 'package:local_auth/local_auth.dart';
// ignore: depend_on_referenced_packages
import 'package:local_auth_platform_interface/local_auth_platform_interface.dart';

class FakeLocalAuthentication implements LocalAuthentication {
  bool supported = true;
  bool canCheck = true;
  List<BiometricType> available = [BiometricType.fingerprint];
  bool authenticateResult = true;
  PlatformException? throwException;
  bool? lastBiometricOnly;
  bool? lastPersistAcrossBackgrounding;

  @override
  Future<bool> isDeviceSupported() async => supported;

  @override
  Future<bool> get canCheckBiometrics async => canCheck;

  @override
  Future<List<BiometricType>> getAvailableBiometrics() async => available;

  @override
  Future<bool> authenticate({
    required String localizedReason,
    Iterable<AuthMessages> authMessages = const <AuthMessages>[],
    bool biometricOnly = false,
    bool sensitiveTransaction = true,
    bool persistAcrossBackgrounding = false,
  }) async {
    lastBiometricOnly = biometricOnly;
    lastPersistAcrossBackgrounding = persistAcrossBackgrounding;
    if (throwException != null) {
      throw throwException!;
    }
    return authenticateResult;
  }

  @override
  Future<bool> stopAuthentication() async => true;
}

void main() {
  late FakeLocalAuthentication fakeAuth;
  late BiometricService biometricService;

  setUp(() {
    fakeAuth = FakeLocalAuthentication();
    biometricService = BiometricService(auth: fakeAuth);
  });

  group('BiometricService Strict Biometrics Tests', () {
    test('authenticates successfully with biometricOnly: true', () async {
      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isTrue);
      expect(result.status, BiometricStatus.success);
      expect(fakeAuth.lastBiometricOnly, isTrue);
      expect(fakeAuth.lastPersistAcrossBackgrounding, isTrue);
    });

    test('returns notAvailable when device is not supported', () async {
      fakeAuth.supported = false;

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.isNotAvailable, isTrue);
      expect(result.status, BiometricStatus.notAvailable);
      expect(fakeAuth.lastBiometricOnly, isNull); // Never prompts authentication
    });

    test('returns notAvailable when canCheckBiometrics is false', () async {
      fakeAuth.canCheck = false;

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.isNotAvailable, isTrue);
      expect(result.status, BiometricStatus.notAvailable);
      expect(fakeAuth.lastBiometricOnly, isNull);
    });

    test('returns notEnrolled when no biometrics are enrolled', () async {
      fakeAuth.available = [];

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.isNotEnrolled, isTrue);
      expect(result.status, BiometricStatus.notEnrolled);
      expect(fakeAuth.lastBiometricOnly, isNull);
    });

    test('returns canceled when user cancels biometric prompt', () async {
      fakeAuth.authenticateResult = false;

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.isCanceled, isTrue);
      expect(result.status, BiometricStatus.canceled);
      expect(fakeAuth.lastBiometricOnly, isTrue);
    });

    test('handles LockedOut exception gracefully', () async {
      fakeAuth.throwException = PlatformException(code: 'LockedOut', message: 'Sensor locked');

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.status, BiometricStatus.failed);
      expect(result.message, contains('Sensor biometrik terkunci'));
    });

    test('handles NotEnrolled exception from platform', () async {
      fakeAuth.throwException = PlatformException(code: 'NotEnrolled');

      final result = await biometricService.authenticate(actionName: 'Check-in');

      expect(result.isSuccess, isFalse);
      expect(result.isNotEnrolled, isTrue);
      expect(result.status, BiometricStatus.notEnrolled);
    });
  });
}
