import '../model/referral_model.dart';

abstract class ReferralRepository {
  Future<ReferralModel> getReferral();
}
