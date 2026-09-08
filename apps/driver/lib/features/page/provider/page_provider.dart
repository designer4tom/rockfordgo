import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/page_model.dart';
import '../repository/page_repository.dart';

class PageProvider extends ChangeNotifier {
  final PageRepository _repository;
  PageProvider(this._repository);

  // Cache per slug so revisiting a page doesn't refetch.
  final Map<String, PageModel> _pages = {};
  final Set<String> _loading = {};
  String? error;

  PageModel? pageFor(String slug) => _pages[slug];
  bool isLoading(String slug) => _loading.contains(slug);

  Future<void> loadPage(String slug) async {
    _loading.add(slug);
    error = null;
    notifyListeners();
    try {
      _pages[slug] = await _repository.getPage(slug);
    } on ApiException catch (e) {
      error = e.message;
    }
    _loading.remove(slug);
    notifyListeners();
  }
}
