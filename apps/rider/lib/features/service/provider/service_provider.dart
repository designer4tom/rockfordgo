import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/service_model.dart';
import '../model/vehicle_category_model.dart';
import '../repository/service_repository.dart';

class ServiceProvider extends ChangeNotifier {
  final ServiceRepository _repository;

  ServiceProvider(this._repository);

  List<ServiceModel> _services = [];
  List<VehicleCategoryModel> _vehicleCategories = [];
  bool _isLoading = false;
  String? _error;

  List<ServiceModel> get services => _services;
  List<VehicleCategoryModel> get vehicleCategories => _vehicleCategories;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadServices() async {
    _setLoading(true);
    try {
      _services = await _repository.getServices();
      _setLoading(false);
    } on ApiException catch (e) {
      _setError(e.message);
    }
  }

  Future<void> loadVehicleCategories(double lat, double lng) async {
    _setLoading(true);
    try {
      _vehicleCategories = await _repository.getVehicleCategories(lat, lng);
      _setLoading(false);
    } on ApiException catch (e) {
      _setError(e.message);
    }
  }

  void _setLoading(bool value) {
    _isLoading = value;
    if (value) _error = null;
    notifyListeners();
  }

  void _setError(String message) {
    _error = message;
    _isLoading = false;
    notifyListeners();
  }
}
