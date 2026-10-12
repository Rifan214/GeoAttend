/// Application-wide API configuration.
/// The base URL can be supplied at compile-time via `--dart-define=API_BASE_URL=...`
/// or overridden dynamically at runtime (e.g. for physical device LAN testing).
class ApiConfig {
  static const String defaultBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const Duration timeout = Duration(seconds: 15);
}
