import 'package:intl/intl.dart';

/// Currency formatting driven by /config (symbol + position).
/// Call [init] once config is loaded; all amount formatting flows through here.
class CurrencyHelper {
  CurrencyHelper._();

  static String _symbol = '৳';
  static String _position = 'before'; // before | after
  static final NumberFormat _nf = NumberFormat('#,##0.00');

  static String get symbol => _symbol;

  static void init(String symbol, String position) {
    if (symbol.isNotEmpty) _symbol = symbol;
    if (position.isNotEmpty) _position = position;
  }

  static String format(num? amount) {
    final n = _nf.format(amount ?? 0);
    return _position == 'after' ? '$n$_symbol' : '$_symbol$n';
  }
}
