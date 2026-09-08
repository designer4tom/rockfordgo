import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../constants/api_endpoints.dart';
import '../constants/app_constants.dart';
import '../storage/secure_storage.dart';
import 'api_exception.dart';

/// Central Dio instance with auth-token injection, error normalization
/// and (in debug) request/response logging.
class DioClient {
  DioClient._() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConstants.baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {'Accept': 'application/json'},
      ),
    );
    _dio.interceptors.add(_authInterceptor());
    if (kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: true,
          responseBody: true,
          requestHeader: false,
          responseHeader: false,
          error: true,
        ),
      );
    }
  }

  static final DioClient instance = DioClient._();
  late final Dio _dio;
  Dio get dio => _dio;

  /// Called when a 401 is received so the app can route to login.
  VoidCallback? onUnauthorized;

  Interceptor _authInterceptor() {
    return InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await SecureStorage.instance.getToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        // A 401 from the logout endpoint is expected when the token was
        // already revoked (e.g. right after account deletion) — ignore it so
        // it doesn't trigger a duplicate unauthorized redirect. Local cleanup
        // is handled by the caller.
        final isLogout =
            error.requestOptions.path.contains(ApiEndpoints.logout);
        if (error.response?.statusCode == 401 && !isLogout) {
          await SecureStorage.instance.clearAll();
          onUnauthorized?.call();
        }
        handler.next(error);
      },
    );
  }

  // ---- Convenience verbs (return raw Response) ----
  Future<Response> get(String path, {Map<String, dynamic>? query}) =>
      _dio.get(path, queryParameters: query);

  Future<Response> post(String path, {dynamic data}) =>
      _dio.post(path, data: data);

  Future<Response> put(String path, {dynamic data}) =>
      _dio.put(path, data: data);

  Future<Response> delete(String path, {dynamic data}) =>
      _dio.delete(path, data: data);

  /// Translate a [DioException] into a friendly [ApiException].
  static ApiException toApiException(Object error) {
    if (error is DioException) {
      final status = error.response?.statusCode;
      final body = error.response?.data;
      String message = 'Something went wrong';
      dynamic errors;

      if (body is Map) {
        message = (body['message'] ?? message).toString();
        errors = body['errors'];
      } else {
        switch (error.type) {
          case DioExceptionType.connectionTimeout:
          case DioExceptionType.receiveTimeout:
          case DioExceptionType.sendTimeout:
            message = 'Connection timed out. Please try again.';
            break;
          case DioExceptionType.connectionError:
            message = 'No internet connection.';
            break;
          default:
            message = status != null
                ? 'Server error ($status). Please try again.'
                : 'Something went wrong (${error.type.name}).';
        }
      }
      // Helpful for diagnosing failed requests in console / Sentry.
      // ignore: avoid_print
      print('[API] ${error.requestOptions.method} '
          '${error.requestOptions.path} → $status ${error.type.name}: '
          '${error.response?.data ?? error.message}');
      return ApiException(message, statusCode: status, errors: errors);
    }
    return ApiException(error.toString());
  }
}
