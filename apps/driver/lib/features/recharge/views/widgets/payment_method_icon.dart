import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

/// Renders a payment method's logo: the backend's `icon` URL when present,
/// otherwise a recognisable brand-colored badge keyed off `code`/`name` —
/// the /config payment_methods list currently returns `icon: null` for every
/// gateway, so without this every row showed the same generic wallet icon.
class PaymentMethodIcon extends StatelessWidget {
  final String code;
  final String? iconUrl;
  final double size;

  const PaymentMethodIcon({
    super.key,
    required this.code,
    this.iconUrl,
    this.size = 36,
  });

  static const Map<String, ({IconData icon, Color color})> _brands = {
    'stripe': (icon: Icons.credit_card, color: Color(0xFF635BFF)),
    'razorpay': (icon: Icons.credit_card, color: Color(0xFF0C2451)),
    'paystack': (icon: Icons.credit_card, color: Color(0xFF00C3F7)),
    'paytabs': (icon: Icons.credit_card, color: Color(0xFF1E4B8F)),
    'adyen': (icon: Icons.credit_card, color: Color(0xFF0ABF53)),
    'square': (icon: Icons.credit_card, color: Color(0xFF1A1A1A)),
    'braintree': (icon: Icons.credit_card, color: Color(0xFF00B5DB)),
    'flutterwave': (icon: Icons.credit_card, color: Color(0xFFF5A623)),
    'hesabe': (icon: Icons.credit_card, color: Color(0xFF7B2FF7)),
    'mollie': (icon: Icons.credit_card, color: Color(0xFFC5162E)),
    'payu': (icon: Icons.credit_card, color: Color(0xFF5CB92D)),
    'bkash': (icon: Icons.phone_android, color: Color(0xFFE2136E)),
    'nagad': (icon: Icons.phone_android, color: Color(0xFFF6921E)),
    'rocket': (icon: Icons.phone_android, color: Color(0xFF8B3A9E)),
    'card': (icon: Icons.credit_card, color: AppColors.primary),
    'cash': (icon: Icons.payments_outlined, color: AppColors.success),
    'wallet': (icon: Icons.account_balance_wallet, color: AppColors.primary),
    'bank': (icon: Icons.account_balance, color: Color(0xFF2563EB)),
  };

  ({IconData icon, Color color}) get _brand {
    final key = code.toLowerCase();
    if (_brands.containsKey(key)) return _brands[key]!;
    // Loose match (e.g. "bank_transfer" → "bank").
    for (final entry in _brands.entries) {
      if (key.contains(entry.key)) return entry.value;
    }
    return (icon: Icons.account_balance_wallet_outlined, color: AppColors.primary);
  }

  @override
  Widget build(BuildContext context) {
    final url = Helpers.imageUrl(iconUrl);
    if (url != null) {
      return ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Image.network(
          url,
          width: size,
          height: size,
          fit: BoxFit.contain,
          errorBuilder: (_, _, _) => _badge(),
        ),
      );
    }
    return _badge();
  }

  Widget _badge() {
    final b = _brand;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: b.color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(b.icon, color: b.color, size: size * 0.55),
    );
  }
}
