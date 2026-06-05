import '../../core/utils/parsers.dart';

class Address {
  const Address({
    required this.id,
    required this.city,
    required this.district,
    required this.ward,
    required this.detail,
    required this.type,
    required this.isDefault,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
    id: toInt(json['id_diachi']),
    city: toText(json['tinh_thanhpho']),
    district: toText(json['quan_huyen']),
    ward: toText(json['phuong_xa']),
    detail: toText(json['diachi_cuthe']),
    type: toText(json['loai_diachi']),
    isDefault: json['mac_dinh'] == true || toInt(json['mac_dinh']) == 1,
  );

  final int id;
  final String city;
  final String district;
  final String ward;
  final String detail;
  final String type;
  final bool isDefault;

  String get fullAddress => [
    detail,
    ward,
    district,
    city,
  ].where((item) => item.trim().isNotEmpty).join(', ');
}
