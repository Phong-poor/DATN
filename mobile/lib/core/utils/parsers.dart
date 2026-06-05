int toInt(dynamic value) => int.tryParse('$value') ?? 0;
double toDouble(dynamic value) => double.tryParse('$value') ?? 0;
String toText(dynamic value) => value == null ? '' : '$value';

Map<String, dynamic> toMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<Map<String, dynamic>> toMapList(dynamic value) => value is List
    ? value
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList()
    : <Map<String, dynamic>>[];

String formatMoney(num value) {
  final raw = value.round().toString();
  final output = StringBuffer();
  for (var index = 0; index < raw.length; index++) {
    if (index > 0 && (raw.length - index) % 3 == 0) output.write('.');
    output.write(raw[index]);
  }
  return '$output VND';
}
