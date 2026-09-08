/// CMS page (Privacy Policy, Terms & Conditions, …) — title + HTML content.
class PageModel {
  final String title;
  final String content; // HTML

  PageModel({this.title = '', this.content = ''});

  factory PageModel.fromJson(Map<String, dynamic> json) {
    final d = (json['data'] is Map ? json['data'] : json) as Map;
    return PageModel(
      title: (d['title'] ?? '').toString(),
      content: (d['content'] ?? d['body'] ?? '').toString(),
    );
  }
}
