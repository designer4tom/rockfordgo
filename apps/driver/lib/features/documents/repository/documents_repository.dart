import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/document_model.dart';

class DocumentsRepository {
  final DioClient _client;
  DocumentsRepository({DioClient? client})
      : _client = client ?? DioClient.instance;

  Future<List<DocumentModel>> getDocuments() async {
    try {
      final res = await _client.get(ApiEndpoints.profile);
      final data = (res.data['data'] ?? res.data) as Map;
      final list = (data['documents'] as List?) ?? const [];
      return list
          .map((e) => DocumentModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> updateDocument({
    required String type,
    required File file,
    File? backFile,
    DateTime? expiryDate,
  }) async {
    try {
      final form = <String, dynamic>{
        'type': type,
        'file': await MultipartFile.fromFile(file.path,
            filename: file.path.split('/').last),
        if (expiryDate != null) 'expiry_date': _fmt(expiryDate),
      };
      if (backFile != null) {
        form['back_file'] = await MultipartFile.fromFile(backFile.path,
            filename: backFile.path.split('/').last);
      }
      final res = await _client.post(
        ApiEndpoints.updateDocument,
        data: FormData.fromMap(form),
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}
