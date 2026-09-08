import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/faq_model.dart';
import '../repository/help_repository.dart';

class HelpProvider extends ChangeNotifier {
  final HelpRepository _repository;

  HelpProvider(this._repository);

  List<FaqModel> faqs = [];
  bool isLoadingFaqs = false;

  List<SafetyTipModel> safetyTips = [];
  bool isLoadingSafetyTips = false;

  // CMS pages cached by slug (privacy-policy, terms-conditions, safety-tips…).
  final Map<String, PageModel> _pages = {};
  final Set<String> _loadingPages = {};
  String? error;

  PageModel? pageFor(String slug) => _pages[slug];
  bool isLoadingPage(String slug) => _loadingPages.contains(slug);

  Future<void> loadFaqs() async {
    isLoadingFaqs = true;
    notifyListeners();
    try {
      faqs = await _repository.getFaqs();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoadingFaqs = false;
      notifyListeners();
    }
  }

  Future<void> loadSafetyTips() async {
    isLoadingSafetyTips = true;
    notifyListeners();
    try {
      safetyTips = await _repository.getSafetyTips();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoadingSafetyTips = false;
      notifyListeners();
    }
  }

  Future<void> loadPage(String slug) async {
    _loadingPages.add(slug);
    notifyListeners();
    try {
      _pages[slug] = await _repository.getPage(slug);
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      _loadingPages.remove(slug);
      notifyListeners();
    }
  }
}
