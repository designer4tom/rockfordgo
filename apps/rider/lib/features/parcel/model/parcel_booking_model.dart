class PersonInfo {
  final String name;
  final String phone;
  final String address;

  PersonInfo({this.name = '', this.phone = '', this.address = ''});

  factory PersonInfo.fromJson(Map<String, dynamic> json) {
    return PersonInfo(
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      address: json['address'] ?? '',
    );
  }
}

class ParcelBookingModel {
  final int orderId;
  final String orderNumber;
  final String status;
  final String deliveryCharge;
  final String? codAmount;
  final String? totalReceiverPays;
  final String? deliveryChargePayer; // 'sender' for COD, else null
  final String paymentTiming;
  final String paymentMethod;
  final PersonInfo sender;
  final PersonInfo receiver;

  ParcelBookingModel({
    required this.orderId,
    this.orderNumber = '',
    this.status = 'pending',
    this.deliveryCharge = '0.00',
    this.codAmount,
    this.totalReceiverPays,
    this.deliveryChargePayer,
    this.paymentTiming = 'before',
    this.paymentMethod = 'wallet',
    required this.sender,
    required this.receiver,
  });

  factory ParcelBookingModel.fromJson(Map<String, dynamic> json) {
    return ParcelBookingModel(
      orderId: json['order_id'] ?? json['id'] ?? 0,
      orderNumber: json['order_number'] ?? '',
      status: json['status'] ?? 'pending',
      deliveryCharge: (json['delivery_charge'] ?? '0.00').toString(),
      codAmount: json['cod_amount']?.toString(),
      totalReceiverPays: json['total_receiver_pays']?.toString(),
      deliveryChargePayer: json['delivery_charge_payer']?.toString(),
      paymentTiming: json['payment_timing'] ?? 'before',
      paymentMethod: json['payment_method'] ?? 'wallet',
      sender: json['sender'] != null
          ? PersonInfo.fromJson(Map<String, dynamic>.from(json['sender']))
          : PersonInfo(),
      receiver: json['receiver'] != null
          ? PersonInfo.fromJson(Map<String, dynamic>.from(json['receiver']))
          : PersonInfo(),
    );
  }
}
