import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/address.dart';
import '../../../shared/models/user.dart';

class ProfileService {
  const ProfileService(this._apiClient);

  final ApiClient _apiClient;

  Future<User> getProfile() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.profile, authenticated: true),
    );
    return User.fromJson(response);
  }

  Future<User> updateProfile({
    required String name,
    required String email,
    String? phone,
    String? dateOfBirth,
    String? gender,
  }) async {
    final response = toMap(
      await _apiClient.put(
        ApiEndpoints.profile,
        authenticated: true,
        body: {
          'name': name.trim(),
          'email': email.trim(),
          'phone': phone?.trim(),
          'date_of_birth': dateOfBirth?.trim(),
          'gender': gender,
        },
      ),
    );
    return User.fromJson(toMap(response['user']));
  }

  Future<List<Address>> getAddresses() async {
    final response = toMap(
      await _apiClient.get(ApiEndpoints.addresses, authenticated: true),
    );
    return toMapList(response['data']).map(Address.fromJson).toList();
  }

  Future<Address> saveAddress({
    int? id,
    required String city,
    required String district,
    required String ward,
    required String detail,
    String type = 'home',
    bool isDefault = false,
  }) async {
    final body = {
      'tinh_thanhpho': city.trim(),
      'quan_huyen': district.trim(),
      'phuong_xa': ward.trim(),
      'diachi_cuthe': detail.trim(),
      'loai_diachi': type,
      'mac_dinh': isDefault,
    };
    final response = toMap(
      id == null
          ? await _apiClient.post(
              ApiEndpoints.addresses,
              authenticated: true,
              body: body,
            )
          : await _apiClient.put(
              ApiEndpoints.address(id),
              authenticated: true,
              body: body,
            ),
    );
    return Address.fromJson(toMap(response['data']));
  }

  Future<void> deleteAddress(int id) async {
    await _apiClient.delete(ApiEndpoints.address(id), authenticated: true);
  }

  Future<void> setDefaultAddress(int id) async {
    await _apiClient.patch(
      ApiEndpoints.addressDefault(id),
      authenticated: true,
    );
  }
}
