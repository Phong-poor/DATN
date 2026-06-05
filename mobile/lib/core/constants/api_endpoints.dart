class ApiEndpoints {
  const ApiEndpoints._();

  static const login = 'login';
  static const register = 'register';
  static const logout = 'logout';

  static const profile = 'user/profile';
  static const changePassword = 'user/change-password';
  static const addresses = 'user/dia-chi';
  static String address(int id) => 'user/dia-chi/$id';
  static String addressDefault(int id) => 'user/dia-chi/$id/mac-dinh';

  static const categories = 'danhmuc';
  static String categoryDetail(int id) => 'danhmuc/$id';

  static const products = 'sanpham';
  static const mobileHome = 'mobile/home';
  static const productSearch = 'sanpham/search';
  static String productDetail(int id) => 'sanpham/$id';

  static const news = 'news';
  static String newsDetail(int id) => 'news/$id';
  static const promotions = 'promotions';
  static const chatbot = 'chat';
  static const contact = 'lien-he';

  static const wishlist = 'yeu-thich';
  static const wishlistAdd = 'yeu-thich/them';
  static String wishlistUpdate(int id) => 'yeu-thich/cap-nhat/$id';
  static String wishlistRemove(int id) => 'yeu-thich/xoa/$id';

  static const cart = 'gio-hang';
  static const cartAdd = 'gio-hang/them';
  static String cartUpdate(int id) => 'gio-hang/cap-nhat/$id';
  static String cartRemove(int id) => 'gio-hang/xoa/$id';
  static const cartClear = 'gio-hang/xoa-tat';

  static const checkout = 'checkout';
  static const orders = 'orders';
  static String orderCancel(int id) => 'orders/$id/cancel';
  static String orderReorder(int id) => 'orders/$id/reorder';
}
