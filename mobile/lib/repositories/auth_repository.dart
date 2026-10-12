import '../core/network/api_client.dart';
import '../core/network/api_exception.dart';
import '../core/storage/secure_storage_service.dart';
import '../models/user.dart';
import '../models/work_location.dart';

class AuthUserSummary {
  final User user;
  final WorkLocation? location;

  AuthUserSummary({required this.user, this.location});
}

class AuthRepository {
  final ApiClient _client;
  final SecureStorageService _storage;

  AuthRepository({
    ApiClient? client,
    SecureStorageService? storage,
  })  : _client = client ?? ApiClient(),
        _storage = storage ?? SecureStorageService();

  Future<User> login({
    required String email,
    required String password,
    String deviceName = 'Flutter Android App',
  }) async {
    final response = await _client.post(
      '/login',
      body: {
        'email': email.trim(),
        'password': password,
        'device_name': deviceName,
      },
      includeAuth: false,
    );

    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final data = response['data'] as Map<String, dynamic>;
      final token = data['token'] as String?;
      final userJson = data['user'] as Map<String, dynamic>?;

      if (token == null || userJson == null) {
        throw ApiException(message: 'Respons server tidak lengkap (token/user null).');
      }

      await _storage.saveToken(token);
      return User.fromJson(userJson);
    }

    throw ApiException(message: 'Format respons login server tidak valid.');
  }

  Future<void> logout() async {
    try {
      await _client.post('/logout');
    } catch (_) {
      // Even if network or token revocation fails on server, clear local storage
    } finally {
      await _storage.deleteToken();
    }
  }

  Future<AuthUserSummary> getCurrentUser() async {
    final response = await _client.get('/me');

    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final data = response['data'] as Map<String, dynamic>;
      final userJson = data['user'] as Map<String, dynamic>;
      final locationJson = data['location'] as Map<String, dynamic>?;

      final user = User.fromJson(userJson);
      final location = locationJson != null ? WorkLocation.fromJson(locationJson) : null;

      return AuthUserSummary(user: user, location: location);
    }

    throw ApiException(message: 'Format respons user profile tidak valid.');
  }

  Future<bool> hasToken() async {
    final token = await _storage.getToken();
    return token != null && token.isNotEmpty;
  }
}
