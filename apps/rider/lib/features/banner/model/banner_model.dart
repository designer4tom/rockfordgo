class BannerModel {
  final int id;
  final String? title;
  final String? subtitle;
  final String? image;
  final String? buttonText;
  final String actionType; // none | url | service | screen
  final String? actionValue;

  BannerModel({
    required this.id,
    this.title,
    this.subtitle,
    this.image,
    this.buttonText,
    this.actionType = 'none',
    this.actionValue,
  });

  factory BannerModel.fromJson(Map<String, dynamic> json) {
    return BannerModel(
      id: json['id'] ?? 0,
      title: json['title'],
      subtitle: json['subtitle'],
      image: json['image'],
      buttonText: json['button_text'] ?? json['buttonText'],
      actionType: json['action_type'] ?? json['actionType'] ?? 'none',
      actionValue: json['action_value'] ?? json['actionValue'],
    );
  }
}
