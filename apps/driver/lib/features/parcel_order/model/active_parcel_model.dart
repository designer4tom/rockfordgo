import '../../../core/models/place_info.dart';

class PersonInfo {
  final String name;
  final String phone;

  PersonInfo({required this.name, required this.phone});

  factory PersonInfo.fromJson(Map<String, dynamic> json) => PersonInfo(
        name: (json['name'] ?? '').toString(),
        phone: (json['phone'] ?? '').toString(),
      );
}

class ParcelDetails {
  final String type;
  final String? weight;
  final String? size;
  final String? note;
  final String? photo;

  ParcelDetails({
    required this.type,
    this.weight,
    this.size,
    this.note,
    this.photo,
  });

  factory ParcelDetails.fromJson(Map<String, dynamic> json) => ParcelDetails(
        type: (json['type'] ?? json['category'] ?? 'Parcel').toString(),
        weight: json['weight']?.toString(),
        size: json['size']?.toString(),
        note: json['note'] ?? json['instructions'],
        photo: json['photo']?.toString(),
      );
}

class ActiveParcelModel {
  final int orderId;
  final String orderNumber;
  final String status;
  final PersonInfo sender;
  final PersonInfo receiver;
  final PlaceInfo pickup;
  final PlaceInfo drop;
  final ParcelDetails parcel;
  final bool isCod;
  final String? codAmount;
  final String deliveryCharge;
  final String paymentTiming; // before | after
  final String? proofType; // otp | photo | signature
  final String? receiverOtp;

  ActiveParcelModel({
    required this.orderId,
    required this.orderNumber,
    required this.status,
    required this.sender,
    required this.receiver,
    required this.pickup,
    required this.drop,
    required this.parcel,
    required this.isCod,
    this.codAmount,
    required this.deliveryCharge,
    required this.paymentTiming,
    this.proofType,
    this.receiverOtp,
  });

  bool get payBefore => paymentTiming == 'before';

  /// Total cash the driver must collect from the receiver: the product's
  /// COD value only. The delivery charge is billed to the sender's wallet
  /// at completion and must never be collected from the receiver.
  double get codCollectible => double.tryParse(codAmount ?? '0') ?? 0;

  factory ActiveParcelModel.fromJson(Map<String, dynamic> json) {
    final m = (json['data'] is Map ? json['data'] : json) as Map;
    Map<String, dynamic> sub(dynamic v) =>
        v is Map ? v.cast<String, dynamic>() : <String, dynamic>{};

    return ActiveParcelModel(
      orderId: _toInt(m['order_id'] ?? m['id']),
      orderNumber: (m['order_number'] ?? m['number'] ?? '').toString(),
      status: (m['status'] ?? 'accepted').toString(),
      sender: PersonInfo.fromJson(sub(m['sender'])),
      receiver: PersonInfo.fromJson(sub(m['receiver'])),
      pickup: PlaceInfo.fromJson(sub(m['pickup'])),
      drop: PlaceInfo.fromJson(sub(m['drop'] ?? m['dropoff'])),
      parcel: ParcelDetails.fromJson(sub(m['parcel'] ?? m)),
      isCod: m['is_cod'] == true || m['is_cod'] == 1,
      codAmount: m['cod_amount']?.toString(),
      deliveryCharge:
          (m['delivery_charge'] ?? m['charge'] ?? '0').toString(),
      paymentTiming: (m['payment_timing'] ?? 'after').toString(),
      proofType: m['proof_type']?.toString(),
      receiverOtp: m['receiver_otp']?.toString() ?? m['otp']?.toString(),
    );
  }

  ActiveParcelModel copyWith({String? status}) => ActiveParcelModel(
        orderId: orderId,
        orderNumber: orderNumber,
        status: status ?? this.status,
        sender: sender,
        receiver: receiver,
        pickup: pickup,
        drop: drop,
        parcel: parcel,
        isCod: isCod,
        codAmount: codAmount,
        deliveryCharge: deliveryCharge,
        paymentTiming: paymentTiming,
        proofType: proofType,
        receiverOtp: receiverOtp,
      );

  static int _toInt(dynamic v) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? 0;
}
