enum ApiErrorType {
  validation,
  unauthorized,
  forbidden,
  notFound,
  server,
  timeout,
  network,
  unknown,
}

class ApiError implements Exception {
  const ApiError({
    required this.message,
    required this.type,
    this.statusCode,
    this.fieldErrors = const {},
  });

  final String message;
  final ApiErrorType type;
  final int? statusCode;
  final Map<String, List<String>> fieldErrors;

  String? fieldMessage(String field) {
    final messages = fieldErrors[field];
    return messages == null || messages.isEmpty ? null : messages.first;
  }

  @override
  String toString() => message;
}
