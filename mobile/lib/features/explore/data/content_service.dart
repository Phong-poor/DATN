import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_client.dart';
import '../../../core/utils/parsers.dart';
import '../../../shared/models/content.dart';

class ContentService {
  const ContentService(this._apiClient);

  final ApiClient _apiClient;

  Future<List<NewsArticle>> getNews() async {
    final response = toMap(
      await _apiClient.get(
        ApiEndpoints.news,
        query: {'scope': 'public', 'per_page': 20},
      ),
    );
    return toMapList(response['data']).map(NewsArticle.fromJson).toList();
  }

  Future<NewsArticle> getNewsDetail(int id) async {
    final response = toMap(await _apiClient.get(ApiEndpoints.newsDetail(id)));
    return NewsArticle.fromJson(response);
  }

  Future<List<Promotion>> getPromotions() async {
    final response = await _apiClient.get(ApiEndpoints.promotions);
    return toMapList(response).map(Promotion.fromJson).toList();
  }

  Future<ChatReply> sendChat(String message) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.chatbot,
        body: {'message': message.trim()},
      ),
    );
    return ChatReply.fromJson(response);
  }

  Future<String> sendContact({
    required String name,
    required String email,
    required String phone,
    required String category,
    required String message,
  }) async {
    final response = toMap(
      await _apiClient.post(
        ApiEndpoints.contact,
        body: {
          'name': name.trim(),
          'email': email.trim(),
          'phone': phone.trim(),
          'category': category,
          'message': message.trim(),
        },
      ),
    );
    return toText(response['message']);
  }
}
