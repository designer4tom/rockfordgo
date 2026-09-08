/// The driver's saved emergency contact from GET /driver/emergency-contact.
/// All fields are nullable — the backend returns nulls when none is set yet.
class EmergencyContactModel {
  final String? name;
  final String? phone;
  final String? relationship;

  EmergencyContactModel({this.name, this.phone, this.relationship});

  factory EmergencyContactModel.fromJson(Map<String, dynamic> json) =>
      EmergencyContactModel(
        name: json['name']?.toString(),
        phone: json['phone']?.toString(),
        relationship: json['relationship']?.toString(),
      );
}
