/// Generic wrapper for a successful API response.
///
/// Laravel APIs typically return:
/// { "status": true, "message": "...", "data": {...} }
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final dynamic raw; // full decoded body when caller needs extra fields

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.raw,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json, {
    T Function(dynamic data)? parser,
  }) {
    final dynamic rawData = json['data'];
    return ApiResponse<T>(
      success: json['status'] == true || json['success'] == true,
      message: (json['message'] ?? '').toString(),
      data: parser != null ? parser(rawData) : rawData as T?,
      raw: json,
    );
  }
}
