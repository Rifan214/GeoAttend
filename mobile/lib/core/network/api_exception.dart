class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, List<String>>? errors;

  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isConflict => statusCode == 409;
  bool get isValidationError => statusCode == 422;

  String get firstErrorMessage {
    if (errors != null && errors!.isNotEmpty) {
      final firstKey = errors!.keys.first;
      final list = errors![firstKey];
      if (list != null && list.isNotEmpty) {
        return list.first;
      }
    }
    return message;
  }

  @override
  String toString() => message;
}
