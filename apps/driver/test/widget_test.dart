import 'package:flutter_test/flutter_test.dart';

import 'package:readyride_driver/core/constants/app_constants.dart';

void main() {
  test('App name constant is set', () {
    expect(AppConstants.appName, 'ReadyRide Driver');
    expect(AppConstants.baseUrl.startsWith('https://'), isTrue);
  });
}
