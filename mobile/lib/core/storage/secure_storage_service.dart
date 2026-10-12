import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/api_config.dart';

class SecureStorageService {
  final FlutterSecureStorage _storage;

  SecureStorageService({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(
                resetOnError: true,
              ),
            );

  static const String _keyToken = 'auth_token';
  static const String _keyBaseUrl = 'custom_base_url';

  Future<void> saveToken(String token) async {
    await _storage.write(key: _keyToken, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: _keyToken);
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: _keyToken);
  }

  Future<void> saveCustomBaseUrl(String url) async {
    await _storage.write(key: _keyBaseUrl, value: url);
  }

  Future<String> getBaseUrl() async {
    final custom = await _storage.read(key: _keyBaseUrl);
    if (custom != null && custom.trim().isNotEmpty) {
      return custom.trim();
    }
    return ApiConfig.defaultBaseUrl;
  }

  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}
