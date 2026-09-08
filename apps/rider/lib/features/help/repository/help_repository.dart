import '../model/faq_model.dart';

abstract class HelpRepository {
  Future<List<FaqModel>> getFaqs();
  Future<List<SafetyTipModel>> getSafetyTips();
  Future<PageModel> getPage(String slug);
}
