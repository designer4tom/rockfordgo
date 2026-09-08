/// Normalized API error used across repositories.
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final dynamic errors; // validation errors map (optional)

  ApiException(this.message, {this.statusCode, this.errors});

  bool get isUnauthorized => statusCode == 401;
  bool get isValidation => statusCode == 422;
  bool get isNotFound => statusCode == 404;

  @override
  String toString() => 'ApiException($statusCode): $message';
}
