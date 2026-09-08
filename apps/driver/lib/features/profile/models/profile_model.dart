// To parse this JSON data, do
//
//     final profileModel = profileModelFromJson(jsonString);

import 'dart:convert';

ProfileModel profileModelFromJson(String str) => ProfileModel.fromJson(json.decode(str));

String profileModelToJson(ProfileModel data) => json.encode(data.toJson());

class ProfileModel {
  bool? success;
  String? message;
  Data? data;

  ProfileModel({
    this.success,
    this.message,
    this.data,
  });

  ProfileModel copyWith({
    bool? success,
    String? message,
    Data? data,
  }) =>
      ProfileModel(
        success: success ?? this.success,
        message: message ?? this.message,
        data: data ?? this.data,
      );

  factory ProfileModel.fromJson(Map<String, dynamic> json) => ProfileModel(
    success: json["success"],
    message: json["message"],
    data: json["data"] == null ? null : Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "success": success,
    "message": message,
    "data": data?.toJson(),
  };
}

class Data {
  int? id;
  String? name;
  String? phone;
  String? email;
  String? avatar;
  String? status;
  bool? isOnline;
  String? walletBalance;
  String? dueAmount;
  int? averageRating;
  int? totalTrips;
  Zone? currentZone;
  Zone? zone;
  Vehicle? vehicle;

  Data({
    this.id,
    this.name,
    this.phone,
    this.email,
    this.avatar,
    this.status,
    this.isOnline,
    this.walletBalance,
    this.dueAmount,
    this.averageRating,
    this.totalTrips,
    this.currentZone,
    this.zone,
    this.vehicle,
  });

  Data copyWith({
    int? id,
    String? name,
    String? phone,
    String? email,
    String? avatar,
    String? status,
    bool? isOnline,
    String? walletBalance,
    String? dueAmount,
    int? averageRating,
    int? totalTrips,
    Zone? currentZone,
    Zone? zone,
    Vehicle? vehicle,
  }) =>
      Data(
        id: id ?? this.id,
        name: name ?? this.name,
        phone: phone ?? this.phone,
        email: email ?? this.email,
        avatar: avatar ?? this.avatar,
        status: status ?? this.status,
        isOnline: isOnline ?? this.isOnline,
        walletBalance: walletBalance ?? this.walletBalance,
        dueAmount: dueAmount ?? this.dueAmount,
        averageRating: averageRating ?? this.averageRating,
        totalTrips: totalTrips ?? this.totalTrips,
        currentZone: currentZone ?? this.currentZone,
        zone: zone ?? this.zone,
        vehicle: vehicle ?? this.vehicle,
      );

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    id: json["id"],
    name: json["name"],
    phone: json["phone"],
    email: json["email"],
    avatar: json["avatar"],
    status: json["status"],
    isOnline: json["is_online"],
    walletBalance: json["wallet_balance"],
    dueAmount: json["due_amount"],
    averageRating: json["average_rating"],
    totalTrips: json["total_trips"],
    currentZone: json["current_zone"] == null ? null : Zone.fromJson(json["current_zone"]),
    zone: json["zone"] == null ? null : Zone.fromJson(json["zone"]),
    vehicle: json["vehicle"] == null ? null : Vehicle.fromJson(json["vehicle"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "name": name,
    "phone": phone,
    "email": email,
    "avatar": avatar,
    "status": status,
    "is_online": isOnline,
    "wallet_balance": walletBalance,
    "due_amount": dueAmount,
    "average_rating": averageRating,
    "total_trips": totalTrips,
    "current_zone": currentZone?.toJson(),
    "zone": zone?.toJson(),
    "vehicle": vehicle?.toJson(),
  };
}

class Zone {
  int? id;
  String? name;

  Zone({
    this.id,
    this.name,
  });

  Zone copyWith({
    int? id,
    String? name,
  }) =>
      Zone(
        id: id ?? this.id,
        name: name ?? this.name,
      );

  factory Zone.fromJson(Map<String, dynamic> json) => Zone(
    id: json["id"],
    name: json["name"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "name": name,
  };
}

class Vehicle {
  int? id;
  String? category;
  int? categoryId;
  String? make;
  String? model;
  String? registrationNumber;

  Vehicle({
    this.id,
    this.category,
    this.categoryId,
    this.make,
    this.model,
    this.registrationNumber,
  });

  Vehicle copyWith({
    int? id,
    String? category,
    int? categoryId,
    String? make,
    String? model,
    String? registrationNumber,
  }) =>
      Vehicle(
        id: id ?? this.id,
        category: category ?? this.category,
        categoryId: categoryId ?? this.categoryId,
        make: make ?? this.make,
        model: model ?? this.model,
        registrationNumber: registrationNumber ?? this.registrationNumber,
      );

  factory Vehicle.fromJson(Map<String, dynamic> json) => Vehicle(
    id: json["id"],
    category: json["category"],
    categoryId: json["category_id"],
    make: json["make"],
    model: json["model"],
    registrationNumber: json["registration_number"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "category": category,
    "category_id": categoryId,
    "make": make,
    "model": model,
    "registration_number": registrationNumber,
  };
}
