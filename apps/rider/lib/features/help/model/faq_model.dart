class FaqModel {
  final String question;
  final String answer;
  final String category;

  FaqModel({
    this.question = '',
    this.answer = '',
    this.category = 'general',
  });

  factory FaqModel.fromJson(Map<String, dynamic> json) {
    return FaqModel(
      question: json['question'] ?? '',
      answer: json['answer'] ?? '',
      category: json['category'] ?? 'general',
    );
  }
}

/// A single safety tip from `/safety-tips`.
class SafetyTipModel {
  final int id;
  final String title;
  final String description;
  final String? icon;

  SafetyTipModel({
    this.id = 0,
    this.title = '',
    this.description = '',
    this.icon,
  });

  factory SafetyTipModel.fromJson(Map<String, dynamic> json) {
    return SafetyTipModel(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      icon: json['icon'],
    );
  }
}

/// A CMS page (`/pages/{slug}`) — used for About, Privacy, Terms, etc.
class PageModel {
  final String title;
  final String content;

  PageModel({this.title = '', this.content = ''});

  factory PageModel.fromJson(Map<String, dynamic> json) {
    return PageModel(
      title: json['title'] ?? '',
      content: json['content'] ?? '',
    );
  }

  /// Plain-text version of the (possibly HTML) content for simple rendering.
  String get plainText => content
      .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</(p|div|li|h[1-6])>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'<li[^>]*>', caseSensitive: false), '• ')
      .replaceAll(RegExp(r'<[^>]+>'), '')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&amp;', '&')
      .split('\n')
      .map((l) => l.trim())
      .where((l) => l.isNotEmpty)
      .join('\n\n');
}
