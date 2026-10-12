import 'package:flutter_test/flutter_test.dart';
import 'package:geoattend/core/network/api_exception.dart';

void main() {
  group('ApiException Tests', () {
    test('detects unauthorized status code 401', () {
      final ex = ApiException(message: 'Unauthenticated', statusCode: 401);
      expect(ex.isUnauthorized, isTrue);
      expect(ex.isForbidden, isFalse);
    });

    test('detects forbidden status code 403', () {
      final ex = ApiException(message: 'Forbidden', statusCode: 403);
      expect(ex.isForbidden, isTrue);
      expect(ex.isUnauthorized, isFalse);
    });

    test('detects conflict status code 409', () {
      final ex = ApiException(message: 'Conflict', statusCode: 409);
      expect(ex.isConflict, isTrue);
    });

    test('detects validation error status code 422 and extracts first message', () {
      final ex = ApiException(
        message: 'The given data was invalid.',
        statusCode: 422,
        errors: {
          'location': ['Posisi Anda berada di luar radius geofence kantor.'],
          'latitude': ['Latitude wajib diisi.'],
        },
      );

      expect(ex.isValidationError, isTrue);
      expect(ex.firstErrorMessage, 'Posisi Anda berada di luar radius geofence kantor.');
    });

    test('falls back to general message when errors map is empty', () {
      final ex = ApiException(message: 'Server Error', statusCode: 500);
      expect(ex.firstErrorMessage, 'Server Error');
    });
  });
}
