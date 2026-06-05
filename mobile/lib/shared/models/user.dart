import '../../core/config/api_config.dart';
import '../../core/utils/parsers.dart';

class User {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.role,
    required this.status,
    required this.avatarUrl,
    this.dateOfBirth,
    this.gender,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: toInt(json['id']),
    name: toText(json['name']),
    email: toText(json['email']),
    phone: toText(json['phone']),
    role: toText(json['role']),
    status: toText(json['status']),
    avatarUrl: ApiConfig.assetUrl(toText(json['avatar'])),
    dateOfBirth: json['date_of_birth'] == null
        ? null
        : toText(json['date_of_birth']),
    gender: json['gender'] == null ? null : toText(json['gender']),
  );

  final int id;
  final String name;
  final String email;
  final String phone;
  final String role;
  final String status;
  final String avatarUrl;
  final String? dateOfBirth;
  final String? gender;

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'phone': phone,
    'role': role,
    'status': status,
    'avatar': avatarUrl,
    'date_of_birth': dateOfBirth,
    'gender': gender,
  };
}
