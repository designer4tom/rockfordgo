import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/models/vehicle_category_model.dart';
import '../../../core/network/api_exception.dart';
import '../model/auth_response_model.dart';
import '../repository/auth_repository.dart';

/// Multi-step driver registration (personal → documents → vehicle).
/// Payout/withdrawal info is collected later from Profile.
class RegistrationProvider extends ChangeNotifier {
  final AuthRepository _repo;
  final ImagePicker _picker = ImagePicker();

  RegistrationProvider(this._repo);

  // ---- Personal ----
  String name = '';
  String email = '';

  // ---- Documents (field key -> File) ----
  final Map<String, File> documents = {};
  DateTime? licenseExpiry;
  DateTime? vehicleRegExpiry;
  DateTime? insuranceExpiry;

  // Document field keys (must match backend multipart fields).
  static const docNidFront = 'nid_front';
  static const docNidBack = 'nid_back';
  static const docLicenseFront = 'license_front';
  static const docVehicleReg = 'vehicle_registration_doc';
  static const docInsurance = 'insurance_doc';
  static const docVehicleFront = 'vehicle_front_photo';
  static const docVehicleBack = 'vehicle_back_photo';

  static const requiredDocs = [
    docNidFront,
    docNidBack,
    docLicenseFront,
    docVehicleReg,
    docVehicleFront,
    docVehicleBack,
  ];

  // ---- Vehicle ----
  int? vehicleCategoryId;
  String vehicleMake = '';
  String vehicleModel = '';
  String vehicleYear = '';
  String vehicleColor = '';
  String vehicleRegNumber = '';

  void setVehicleCategory(int? id) {
    vehicleCategoryId = id;
    notifyListeners();
  }

  List<VehicleCategoryModel> categories = [];

  int currentStep = 0; // 0:personal 1:documents 2:vehicle
  static const lastStep = 2;

  bool _loading = false;
  bool get loading => _loading;

  bool _submitting = false;
  bool get submitting => _submitting;

  String? _error;
  String? get error => _error;

  Future<void> loadVehicleCategories() async {
    _loading = true;
    notifyListeners();
    try {
      categories = await _repo.getVehicleCategories();
    } on ApiException catch (e) {
      _error = e.message;
    }
    _loading = false;
    notifyListeners();
  }

  /// Pick + compress an image (image_picker quality/size limits keep it small).
  Future<void> pickImage(String field, ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      imageQuality: 70,
      maxWidth: 1280,
      maxHeight: 1280,
    );
    if (picked != null) {
      documents[field] = File(picked.path);
      notifyListeners();
    }
  }

  void removeImage(String field) {
    documents.remove(field);
    notifyListeners();
  }

  void setExpiry(String field, DateTime date) {
    switch (field) {
      case docLicenseFront:
        licenseExpiry = date;
        break;
      case docVehicleReg:
        vehicleRegExpiry = date;
        break;
      case docInsurance:
        insuranceExpiry = date;
        break;
    }
    notifyListeners();
  }

  bool validateStep(int step) {
    _error = null;
    switch (step) {
      case 0:
        if (name.trim().isEmpty) {
          _error = 'Name is required';
          return false;
        }
        return true;
      case 1:
        for (final d in requiredDocs) {
          if (!documents.containsKey(d)) {
            _error = 'Please upload all required documents';
            notifyListeners();
            return false;
          }
        }
        if (licenseExpiry == null) {
          _error = 'License expiry date is required';
          notifyListeners();
          return false;
        }
        if (_isPast(licenseExpiry) ||
            _isPast(vehicleRegExpiry) ||
            _isPast(insuranceExpiry)) {
          _error = 'Expiry dates must be in the future';
          notifyListeners();
          return false;
        }
        return true;
      case 2:
        if (vehicleCategoryId == null) {
          _error = 'Select a vehicle category';
          notifyListeners();
          return false;
        }
        if (vehicleMake.trim().isEmpty ||
            vehicleModel.trim().isEmpty ||
            vehicleRegNumber.trim().isEmpty) {
          _error = 'Fill in all vehicle details';
          notifyListeners();
          return false;
        }
        return true;
      default:
        return true;
    }
  }

  bool _isPast(DateTime? d) =>
      d != null && d.isBefore(DateTime.now());

  void nextStep() {
    if (!validateStep(currentStep)) return;
    if (currentStep < lastStep) {
      currentStep++;
      notifyListeners();
    }
  }

  void prevStep() {
    if (currentStep > 0) {
      currentStep--;
      notifyListeners();
    }
  }

  void goToStep(int step) {
    currentStep = step;
    notifyListeners();
  }

  Future<AuthResult?> submitRegistration() async {
    for (var s = 0; s <= lastStep; s++) {
      if (!validateStep(s)) {
        currentStep = s;
        notifyListeners();
        return null;
      }
    }

    _submitting = true;
    _error = null;
    notifyListeners();
    try {
      final auth = await _repo.register(
        name: name.trim(),
        email: email.trim().isEmpty ? null : email.trim(),
        vehicleCategoryId: vehicleCategoryId!,
        vehicleMake: vehicleMake.trim(),
        vehicleModel: vehicleModel.trim(),
        vehicleYear: vehicleYear.trim(),
        vehicleColor: vehicleColor.trim(),
        vehicleRegNumber: vehicleRegNumber.trim(),
        licenseExpiry: licenseExpiry,
        vehicleRegExpiry: vehicleRegExpiry,
        insuranceExpiry: insuranceExpiry,
        documents: documents,
      );
      _submitting = false;
      notifyListeners();
      return auth.toResult();
    } on ApiException catch (e) {
      _error = e.message;
      _submitting = false;
      notifyListeners();
      return null;
    }
  }
}
