class ServiceModel {
  final int id;
  final String name;
  final String slug;
  final String type;
  final String? icon;
  final String? description;

  ServiceModel({
    required this.id,
    required this.name,
    required this.slug,
    required this.type,
    this.icon,
    this.description,
  });

  factory ServiceModel.fromJson(Map<String, dynamic> json) {
    return ServiceModel(
      id: json['id'],
      name: json['name'] ?? '',
      slug: json['slug'] ?? '',
      type: json['type'] ?? '',
      icon: json['icon'],
      description: json['description'],
    );
  }
}
