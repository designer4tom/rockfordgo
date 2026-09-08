import 'package:dio/dio.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  factory ApiException.fromDioError(DioException error) {
    String message = 'Something went wrong';
    int? statusCode = error.response?.statusCode;
    Map<String, dynamic>? errors;

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.sendTimeout) {
      message = 'Connection timeout. Please check your internet.';
    } else if (error.type == DioExceptionType.connectionError) {
      message = 'No internet connection.';
    } else if (error.type == DioExceptionType.cancel) {
      message = 'Request was cancelled.';
    } else if (error.response != null) {
      final data = error.response!.data;
      if (data is Map) {
        message = (data['message'] as String?) ?? message;
        final rawErrors = data['errors'];
        if (rawErrors is Map) {
          errors = Map<String, dynamic>.from(rawErrors);
        }
      }
    }

    return ApiException(
      message: message,
      statusCode: statusCode,
      errors: errors,
    );
  }

  /// First validation error message (e.g. for showing under a field).
  String? get firstError {
    if (errors != null && errors!.isNotEmpty) {
      final first = errors!.values.first;
      if (first is List && first.isNotEmpty) return first.first.toString();
      if (first is String) return first;
    }
    return null;
  }

  @override
  String toString() => message;
}
