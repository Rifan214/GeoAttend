import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../storage/secure_storage_service.dart';
import 'api_exception.dart';

class ApiClient {
  final http.Client _client;
  final SecureStorageService _storage;

  ApiClient({
    http.Client? client,
    SecureStorageService? storage,
  })  : _client = client ?? http.Client(),
        _storage = storage ?? SecureStorageService();

  Future<Map<String, String>> _buildHeaders({bool includeAuth = true}) async {
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (includeAuth) {
      final token = await _storage.getToken();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    return headers;
  }

  Future<Uri> _resolveUri(String endpoint, [Map<String, dynamic>? queryParams]) async {
    final baseUrl = await _storage.getBaseUrl();
    final cleanBase = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    final cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/$endpoint';
    final fullUrl = '$cleanBase$cleanEndpoint';

    final uri = Uri.parse(fullUrl);
    if (queryParams != null && queryParams.isNotEmpty) {
      final stringParams = queryParams.map((key, value) => MapEntry(key, value?.toString() ?? ''));
      return uri.replace(queryParameters: stringParams);
    }
    return uri;
  }

  Future<dynamic> get(String endpoint, {Map<String, dynamic>? queryParams, bool includeAuth = true}) async {
    try {
      final uri = await _resolveUri(endpoint, queryParams);
      final headers = await _buildHeaders(includeAuth: includeAuth);

      final response = await _client.get(uri, headers: headers).timeout(ApiConfig.timeout);
      return _processResponse(response);
    } on SocketException {
      throw ApiException(message: 'Tidak dapat terhubung ke server. Periksa koneksi internet atau alamat server.');
    } on TimeoutException {
      throw ApiException(message: 'Waktu koneksi ke server telah habis (Timeout).');
    }
  }

  Future<dynamic> post(String endpoint, {Map<String, dynamic>? body, bool includeAuth = true}) async {
    try {
      final uri = await _resolveUri(endpoint);
      final headers = await _buildHeaders(includeAuth: includeAuth);

      final response = await _client
          .post(uri, headers: headers, body: body != null ? jsonEncode(body) : null)
          .timeout(ApiConfig.timeout);
      return _processResponse(response);
    } on SocketException {
      throw ApiException(message: 'Tidak dapat terhubung ke server. Periksa koneksi internet atau alamat server.');
    } on TimeoutException {
      throw ApiException(message: 'Waktu koneksi ke server telah habis (Timeout).');
    }
  }

  dynamic _processResponse(http.Response response) {
    dynamic jsonBody;
    try {
      if (response.body.isNotEmpty) {
        jsonBody = jsonDecode(response.body);
      }
    } catch (_) {
      // Non-JSON response
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return jsonBody;
    }

    // Handle error codes
    String message = 'Terjadi kesalahan pada server (HTTP ${response.statusCode})';
    Map<String, List<String>>? validationErrors;

    if (jsonBody is Map<String, dynamic>) {
      if (jsonBody.containsKey('message') && jsonBody['message'] is String) {
        message = jsonBody['message'] as String;
      }

      if (jsonBody.containsKey('errors') && jsonBody['errors'] is Map) {
        final rawErrors = jsonBody['errors'] as Map;
        validationErrors = {};
        rawErrors.forEach((key, val) {
          if (val is List) {
            validationErrors![key.toString()] = val.map((e) => e.toString()).toList();
          } else if (val is String) {
            validationErrors![key.toString()] = [val];
          }
        });
      }
    }

    throw ApiException(
      message: message,
      statusCode: response.statusCode,
      errors: validationErrors,
    );
  }
}
