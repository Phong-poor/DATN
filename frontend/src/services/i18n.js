import { nextTick } from 'vue'

const APP_LOCALE_KEY = 'app-locale'
const LEGACY_ADMIN_LOCALE_KEY = 'admin-locale'
const supportedLocales = ['vi', 'en']

const textMap = {
  'Trang chủ': 'Home',
  'Tổng quan': 'Overview',
  'Tổng quan hệ thống': 'System Overview',
  'Quản lý nội dung và điều hành hệ thống': 'Manage content and operate the system',
  'Sản phẩm': 'Products',
  'Danh mục': 'Categories',
  'Thương hiệu': 'Brands',
  'Màu & biến thể': 'Colors & Variants',
  'Bán hàng': 'Sales',
  'Đơn hàng': 'Orders',
  'Khuyến mãi': 'Promotions',
  'Quản lý Combo': 'Combo Management',
  'Nội dung': 'Content',
  'Bài viết': 'Articles',
  'Bình luận': 'Reviews',
  'Người dùng': 'Users',
  'Liên hệ': 'Contact',
  'Banner': 'Banners',
  'Nhật ký hệ thống': 'Activity Log',
  'Quản trị viên': 'Administrator',
  'Quản trị hệ thống': 'Admin Console',
  'Đăng xuất': 'Logout',
  'Tiếng Việt': 'Vietnamese',
  'English': 'English',
  'Xem tất cả': 'View all',
  'Chi tiết': 'Details',
  'Thêm Combo mới': 'Add New Combo',
  'Tạo Ưu Đãi Mới': 'Create New Offer',
  'Quản lý Combo khuyến mãi': 'Promotional Combo Management',
  'Tạo và quản lý các combo ghép phụ kiện giá tốt nhất': 'Create and manage best-value accessory bundles',
  'Quản lý Combo Bán Lẻ': 'Retail Combo Management',
  'Cấu hình Ưu đãi Biến thể': 'Variant Offer Setup',
  'Tổng số Combo': 'Total Combos',
  'Ưu đãi đang chạy': 'Active Offers',
  'Giá trị TB Combo': 'Average Combo Value',
  'Ảnh': 'Image',
  'Thông tin combo': 'Combo Info',
  'Sản phẩm trong combo': 'Products In Combo',
  'Giá combo': 'Combo Price',
  'Trạng thái': 'Status',
  'Thao tác': 'Actions',
  'Hoạt động': 'Active',
  'Ngừng chạy': 'Inactive',
  'Thiếu hàng': 'Low Stock',
  'Hiển thị': 'Showing',
  'combo': 'combos',
  'Không tìm thấy combo nào': 'No combos found',
  'Hãy thử từ khóa khác hoặc tạo combo phụ kiện mới.': 'Try another keyword or create a new accessory combo.',
  'Tìm kiếm tên combo...': 'Search combo name...',
  'Mã đơn': 'Order ID',
  'Khách hàng': 'Customer',
  'Tổng cộng': 'Total',
  'Đơn hàng mới nhất': 'Latest Orders',
  'Sản phẩm bán chạy': 'Best-selling Products',
  'Đã bán': 'Sold',
  'Chưa có dữ liệu trong kỳ này': 'No data for this period',
  'Chờ xác nhận': 'Pending',
  'Đang giao': 'Shipping',
  'Hoàn thành': 'Completed',
  'Hủy đơn': 'Cancelled',
  'Đã hoàn tiền': 'Refunded',
  'Yêu cầu hoàn trả': 'Return Requested',
  'Tất cả sản phẩm': 'All Products',
  'Tìm kiếm tên sản phẩm, SKU...': 'Search product name, SKU...',
  'Tất cả trạng thái': 'All Statuses',
  'Tất cả danh mục': 'All Categories',
  'Không tìm thấy sản phẩm nào.': 'No products found.',
  'Sửa': 'Edit',
  'Xóa': 'Delete',
  'Xóa đã chọn': 'Delete Selected',
  'Bỏ chọn': 'Clear Selection',
  'Xóa toàn bộ': 'Delete All',
  'Tạo Combo mới': 'Create New Combo',
  'Chỉnh sửa Combo': 'Edit Combo',
  'Quay lại danh sách': 'Back to list',
  'Lưu thay đổi': 'Save Changes',
  'Đang lưu...': 'Saving...',
  'Tạo Combo': 'Create Combo',
  'Tên Combo': 'Combo Name',
  'Mô tả combo': 'Combo Description',
  'Giá khuyến mãi Combo': 'Promotional Combo Price',
  'Ảnh đại diện Combo': 'Combo Cover Image',
  'Sản phẩm nổi bật': 'Featured Products',
  'Bán chạy': 'Best Sellers',
  'Mới nhất': 'Newest',
  'Giá tốt': 'Best Price',
  'Được đánh giá cao': 'Top Rated',
  'Phụ Kiện Theo Bộ - Siêu Tiết Kiệm': 'Accessory Bundles - Big Savings',
  'Cấu hình Combo': 'Configure Combo',
  'Giá combo chỉ từ': 'Combo price from',
  'Mua ngay': 'Buy Now',
  'Mua': 'Buy',
  'Xem cấu hình': 'View Config',
  'Khám phá ngay': 'Explore Now',
  'Tư vấn cấu hình': 'Config Consultation',
  'Sản phẩm bán chạy': 'Best-selling Products',
  'Máy flagship đắt tiền nhất': 'Most Premium Flagship Machines',
  'Khám phá Thiết kế & Góc Nhìn Chi Tiết': 'Explore Design & Detailed Angles',
  'Góc nhìn thực tế từ tương lai': 'Real-world View From The Future',
  'MAIN MENU': 'MAIN MENU',
  'Doanh thu & Đơn hàng': 'Revenue & Orders',
  'Doanh thu': 'Revenue',
  'Đơn hàng': 'Orders',
  'Khuyến mãi mới': 'New Promotions',
  'Lượng sản phẩm bán': 'Products Sold',
  'Tất cả thời gian': 'All time',
  '(Tất cả thời gian)': '(All time)',
  'Revenue': 'Revenue',
  'Orders': 'Orders',
  'MÃ ĐƠN': 'ORDER ID',
  'KHÁCH HÀNG': 'CUSTOMER',
  'TỔNG CỘNG': 'TOTAL',
  'TRẠNG THÁI': 'STATUS',
  'HOÀN THÀNH': 'COMPLETED',
  'Đã xác nhận': 'Confirmed',
  'Chờ lấy hàng hoàn': 'Awaiting Return Pickup',
  'Đang giao hoàn': 'Return Shipping',
  'Đã nhận hoàn': 'Return Received',
  'Đã giao': 'Delivered',
  'Đang xử lý': 'Processing',
  'Chờ xử lý': 'Pending',
  'Đã trả lời': 'Replied',
  'Đã phản hồi': 'Responded',
  'Chờ sử lý': 'Pending',
  'Chờ xử lý': 'Pending',
  'Đã thanh toán': 'Paid',
  'Đã duyệt': 'Approved',
  'Chờ duyệt': 'Pending Review',
  'Đã chuyển tiền': 'Transferred',
  'Hoàn trả': 'Return',
  'Hủy': 'Cancel',
  'Lưu': 'Save',
  'Lọc': 'Filter',
  'Áp dụng': 'Apply',
  'Làm mới': 'Refresh',
  'Tìm kiếm': 'Search',
  'Không có dữ liệu': 'No data',
  'Đang tải dữ liệu...': 'Loading data...',
  'Đang tải trang': 'Loading page',
  'Đang tải...': 'Loading...',
  'Vừa xong': 'Just now',
  '5 phút trước': '5 minutes ago',
  'Có đơn hàng mới cần xử lý': 'New order needs processing',
  'Có liên hệ mới từ khách hàng': 'New customer contact',
  'Đã lưu': 'Saved',
  'Xem': 'View',
  'Xem chi tiết': 'View Details',
  'Xem sản phẩm': 'View Product',
  'Không tìm thấy sản phẩm': 'No products found',
  'Không tìm thấy sản phẩm nào': 'No products found',
  'Danh Sách Ưu Đãi': 'Offer List',
  'Flash Sale giờ vàng': 'Golden Hour Flash Sale',
  'Tổng doanh thu': 'Total Revenue',
  'Tổng giảm giá': 'Total Discount',
  'Tổng đơn hàng': 'Total Orders',
  'Đang tải dữ liệu billing...': 'Loading billing data...',
  'Tải lại': 'Reload',
  'Tổng số Publishers': 'Total Publishers',
  'Hoa hồng chờ duyệt': 'Pending Commissions',
  'Rút tiền chờ duyệt': 'Pending Withdrawals',
  'Trị giá': 'Value',
  'Tổng tiền': 'Total Amount',
  'Danh sách Nhà tiếp thị (Publishers)': 'Publisher List',
  'Họ tên User': 'User Full Name',
  'Địa chỉ Email': 'Email Address',
  'Mã Affiliate': 'Affiliate Code',
  'Tỉ lệ chia sẻ': 'Share Rate',
  'Tổng kiếm được': 'Total Earned',
  'Tổng đã thanh toán': 'Total Paid',
  'Lịch sử & Phê duyệt hoa hồng': 'Commission History & Approval',
  'Mã đơn hàng': 'Order Code',
  'Nhà tiếp thị': 'Publisher',
  'Khách hàng mua': 'Buyer',
  'Số tiền hoa hồng': 'Commission Amount',
  'Hành động': 'Action',
  'Yêu cầu thanh toán rút tiền': 'Withdrawal Requests',
  'Người yêu cầu': 'Requester',
  'Số tiền đề xuất': 'Requested Amount',
  'Ngân hàng': 'Bank',
  'Số tài khoản': 'Account Number',
  'Phê duyệt nhanh': 'Quick Approval',
  'Chưa có nhà tiếp thị liên kết nào đăng ký': 'No affiliate publishers have registered yet',
  'Chưa phát sinh giao dịch chia sẻ hoa hồng nào': 'No commission transactions yet',
  'Chưa có yêu cầu thanh toán rút tiền nào gửi lên': 'No withdrawal requests submitted yet',
  'Thông tin cá nhân': 'Personal Information',
  'Hồ sơ cá nhân': 'My Profile',
  'Cài đặt': 'Settings',
  'Quản lý thông tin hồ sơ của bạn': 'Manage your profile information',
  'Chỉnh sửa': 'Edit',
  'Đổi ảnh': 'Change Photo',
  'Họ và tên': 'Full Name',
  'Số điện thoại': 'Phone Number',
  'Ngày sinh': 'Date of Birth',
  'Giới tính': 'Gender',
  'Chưa cập nhật': 'Not Updated',
  'Chưa chọn': 'Not Selected',
  'Nam': 'Male',
  'Nữ': 'Female',
  'Khác': 'Other',
  'Thành viên': 'Member',
  'Quản Trị Viên Hệ Thống': 'System Administrators',
  'Cập nhật trạng thái': 'Update Status',
  'Đang quét hệ thống...': 'Scanning system...',
  'Đang hoạt động': 'Active',
  'Ngoại tuyến': 'Offline',
  'Nhật Ký Thao Tác Hệ Thống': 'System Activity Log',
  'Tổng số bản ghi': 'Total Records',
  'Thao tác': 'Action',
  'Phân hệ': 'Module',
  'Chi tiết nội dung thay đổi': 'Change Details',
  'Mạng (IP / Trình duyệt)': 'Network (IP / Browser)',
  'Thời gian': 'Time',
  'Hệ thống': 'System',
  'Trang trước': 'Previous Page',
  'Trang sau': 'Next Page',
  '-- Tất cả thao tác --': '-- All actions --',
  '-- Tất cả phân hệ --': '-- All modules --',
  'Không có dữ liệu thao tác nào khớp với bộ lọc.': 'No activity log records match the filters.',
  'Đang tải nhật ký thao tác...': 'Loading activity logs...',
  'Thêm mới': 'Create',
  'Cập nhật': 'Update',
  'Thành viên': 'Members',
  'Tư vấn trực tuyến': 'Online Consultation',
  'Mia - Chuyên viên hỗ trợ VinaTech': 'Mia - VinaTech Support Specialist',
  'Nhắn Admin': 'Message Admin',
  'Đóng chat': 'Close Chat',
  'Nhân viên tư vấn': 'Support Agent',
  'Sản phẩm': 'Product',
  'Xem bản đồ': 'View Map',
  'Lấy địa chỉ từ vị trí này': 'Use Address From This Location',
  'Xác nhận vị trí': 'Confirm Location',
  'Chọn vị trí giao hàng': 'Choose Delivery Location',
  'Click bản đồ hoặc kéo ghim để chọn đúng vị trí': 'Click the map or drag the pin to choose the correct location',
  'Vui lòng ghim địa chỉ chính xác để giao hàng thuận tiện hơn.': 'Please pin the exact address for smoother delivery.',
  'Trở lại': 'Back',
  'Tóm tắt đơn hàng': 'Order Summary',
  'Kiểm tra sản phẩm trước khi thanh toán': 'Review your products before checkout',
  'Đổi trả 7 ngày': '7-day Return',
  'Miễn phí hoàn toàn - Kèm theo đơn hàng của bạn': 'Completely free - Included with your order',
  'Hoàn tất đơn đặt hàng của bạn với sự chính xác tuyệt đối.': 'Complete your order with absolute accuracy.',
  'Đang tải địa chỉ...': 'Loading addresses...',
  'Giảm giá đơn hàng': 'Order Discount',
  'Địa chỉ chi tiết': 'Detailed Address',
  'Đang tìm vị trí khu vực...': 'Finding area location...',
  'Hoàn thành': 'Complete',
  'Đã xảy ra sự cố': 'Something went wrong',
  'Kích hoạt tài khoản ngay': 'Activate Account Now',
  'Đang kích hoạt...': 'Activating...',
  'Sao chép link': 'Copy Link',
  'Đã sao chép': 'Copied',
  'Đường dẫn tiếp thị của bạn (Đã tích hợp mã CTV):': 'Your affiliate link (Referral code included):',
  'Gửi yêu cầu rút tiền': 'Submit Withdrawal Request',
  'Đang gửi yêu cầu...': 'Submitting Request...',
  'Bạn chưa gửi yêu cầu rút tiền nào.': 'You have not submitted any withdrawal requests.',
  'Chi tiết đơn hàng': 'Order Details',
  'Lý do': 'Reason',
  'Bằng chứng': 'Evidence',
  'Xem file đính kèm': 'View Attachment',
  'Quá trình hoàn trả': 'Return Process',
  'Số lượng': 'Quantity',
  'Đánh giá': 'Review',
  'Đã đánh giá': 'Reviewed',
  'Mua lại': 'Buy Again',
  'Lý do hủy đơn': 'Cancellation Reason',
  'Xác nhận hủy': 'Confirm Cancellation',
  'Yêu cầu hoàn trả': 'Return Request',
  'Chọn sản phẩm hoàn trả': 'Select Return Products',
  'Hình ảnh / Video bằng chứng': 'Evidence Image / Video',
  'Hỗ trợ ảnh hoặc video (tối đa 20MB)': 'Supports images or videos (max 20MB)',
  'Gửi yêu cầu': 'Submit Request',
  'Đánh giá sản phẩm': 'Product Review',
  'Chất lượng sản phẩm': 'Product Quality',
  'Bình luận': 'Comment',
  'Gửi đánh giá': 'Submit Review',
  'Chỉnh sửa địa chỉ': 'Edit Address',
  'Thêm địa chỉ mới': 'Add New Address',
  'Tỉnh/Thành phố': 'Province/City',
  'Phường/Xã': 'Ward/Commune',
  'Đang tìm kiếm gợi ý...': 'Searching suggestions...',
  'Vị trí giao hàng': 'Delivery Location',
  'Loại địa chỉ': 'Address Type',
  'Nhà riêng': 'Home',
  'Công ty': 'Company',
  'Đặt làm địa chỉ mặc định': 'Set as Default Address',
  'Lưu địa chỉ': 'Save Address',
  'Tải ảnh lên': 'Upload Photo',
  'Lịch Sử Đơn Hàng': 'Order History',
  'Đơn mua hàng': 'Purchase Orders',
  'Đơn hoàn trả': 'Return Orders',
  'Ngày đặt': 'Order Date',
  'Bạn chưa có đơn hàng nào': 'You do not have any orders yet',
  'Tiếp tục mua sắm': 'Continue Shopping',
  'Địa chỉ của tôi': 'My Addresses',
  'Quản lý địa chỉ giao hàng': 'Manage delivery addresses',
  'Thêm địa chỉ': 'Add Address',
  'Chưa có địa chỉ nào': 'No addresses yet',
  'Mặc định': 'Default',
  'Đặt mặc định': 'Set Default',
  'Khuyến Mãi': 'Promotions',
  'Danh sách mã và chương trình khuyến mãi hiện có': 'Available promotion codes and campaigns',
  'TÊN': 'NAME',
  'MÃ': 'CODE',
  'LOẠI': 'TYPE',
  'GIÁ TRỊ': 'VALUE',
  'THỜI GIAN HẾT HẠN': 'EXPIRY TIME',
  'Không có khuyến mãi nào': 'No promotions available',
  'Phần trăm': 'Percent',
  'Cố định': 'Fixed',
  'Không giới hạn': 'Unlimited',
  'Đổi mật khẩu': 'Change Password',
  'Cập nhật mật khẩu để bảo mật tài khoản': 'Update your password to secure your account',
  'Mật khẩu hiện tại': 'Current Password',
  'Mật khẩu mới': 'New Password',
  'Xác nhận mật khẩu mới': 'Confirm New Password',
  'Không tải được captcha': 'Could not load captcha',
  'Cập nhật mật khẩu': 'Update Password',
  'Đang cập nhật...': 'Updating...',
  'Yêu cầu mật khẩu': 'Password Requirements',
  'Mẹo bảo mật': 'Security Tips',
  'Không dùng thông tin cá nhân': 'Do not use personal information',
  'Dùng mật khẩu riêng cho mỗi trang': 'Use a unique password for each site',
  'Thay đổi định kỳ 3–6 tháng': 'Change it every 3-6 months',
  'Tệ': 'Bad',
  'Không hài lòng': 'Unsatisfied',
  'Bình thường': 'Normal',
  'Hài lòng': 'Satisfied',
  'Tuyệt vời': 'Excellent',
}

const placeholderMap = {
  'Tìm kiếm': 'Search',
  'Tìm kiếm tên combo...': 'Search combo name...',
  'Tìm kiếm tên sản phẩm, SKU...': 'Search product name, SKU...',
  'Tìm kiếm theo sản phẩm, biến thể hoặc combo...': 'Search by product, variant, or combo...',
  'Nhập địa chỉ email của bạn': 'Enter your email address',
  'Tìm kiếm sản phẩm...': 'Search products...',
  'VD: [VinaTech] Phản hồi yêu cầu hỗ trợ của bạn': 'E.g. [VinaTech] Response to your support request',
  'Trò chuyện với Mia (ví dụ: tư vấn laptop văn phòng)...': 'Chat with Mia (e.g. office laptop advice)...',
  'Số nhà, tên đường...': 'House number, street name...',
  'Nhập lý do hủy tại đây...': 'Enter cancellation reason here...',
  'Nhập lý do hoàn trả tại đây...': 'Enter return reason here...',
  'Hãy chia sẻ trải nghiệm của bạn về sản phẩm nhé...': 'Share your product experience...',
  'Nhập kết quả': 'Enter result',
  'Tìm theo mô tả, tên admin, địa chỉ IP...': 'Search by description, admin name, IP address...',
}

const originalTextNodes = new WeakMap()
const originalAttrs = new WeakMap()
const compact = (value = '') => value.replace(/\s+/g, ' ').trim()
const hasVietnamese = (value = '') => /[À-ỹĐđ]/.test(value)
const stripVietnameseMarks = (value = '') => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/Đ/g, 'D')

const normalizedTextMap = Object.fromEntries(
  Object.entries(textMap).map(([key, value]) => [compact(key).toLocaleLowerCase('vi-VN'), value])
)
const reverseTextMap = Object.fromEntries(
  Object.entries(textMap).map(([key, value]) => [compact(value).toLocaleLowerCase('en-US'), key])
)
const normalizedPlaceholderMap = Object.fromEntries(
  Object.entries(placeholderMap).map(([key, value]) => [compact(key).toLocaleLowerCase('vi-VN'), value])
)
const reversePlaceholderMap = Object.fromEntries(
  Object.entries(placeholderMap).map(([key, value]) => [compact(value).toLocaleLowerCase('en-US'), key])
)

const dynamicTextRules = [
  [/^(.+?)\s*\((\d+)\)$/u, (match, label, count) => {
    const translatedLabel = translateExact(label)
    return translatedLabel === label ? null : `${translatedLabel} (${count})`
  }],
  [/^Thành viên từ\s+(.+)$/iu, (_match, date) => `Member since ${date}`],
  [/^Mã đơn:\s*(.+)$/iu, (_match, code) => `Order code: ${code}`],
  [/^Tổng số bản ghi:\s*(\d+)$/iu, (_match, count) => `Total records: ${count}`],
  [/^Đã bán:\s*(\d+)\s*đơn vị$/iu, (_match, count) => `Sold: ${count} unit${Number(count) === 1 ? '' : 's'}`],
  [/^Đã bán:\s*(\d+)$/iu, (_match, count) => `Sold: ${count}`],
  [/^Đã bán\s*(\d+)%$/iu, (_match, count) => `Sold ${count}%`],
  [/^(\d+)\s*đơn$/iu, (_match, count) => `${count} orders`],
  [/^(\d+)\s*yêu cầu$/iu, (_match, count) => `${count} request${Number(count) === 1 ? '' : 's'}`],
  [/^(\d+)\s*giao dịch$/iu, (_match, count) => `${count} transaction${Number(count) === 1 ? '' : 's'}`],
  [/^(\d+)\s*tháng$/iu, (_match, count) => `${count} months`],
  [/^(\d+)\s*phút trước$/iu, (_match, count) => `${count} minutes ago`],
  [/^(\d+)\s*giờ trước$/iu, (_match, count) => `${count} hours ago`],
  [/^(\d+(?:[.,]\d+)?)\s*tr$/iu, (_match, count) => `${count}M`],
  [/^Hiển thị\s+(\d+)\s*-\s*(\d+)\s+của\s+(\d+)$/iu, (_match, start, end, total) => `Showing ${start}-${end} of ${total}`],
  [/^Hiển thị\s+(\d+)\s*–\s*(\d+)\s+của\s+(\d+)\s+đơn hàng$/iu, (_match, start, end, total) => `Showing ${start}-${end} of ${total} orders`],
  [/^Hiển thị\s+(\d+)\s*–\s*(\d+)\s+của\s+(\d+)\s+khuyến mãi$/iu, (_match, start, end, total) => `Showing ${start}-${end} of ${total} promotions`],
  [/^Hiển thị\s+(\d+)\s+đến\s+(\d+)\s+của\s+(\d+)$/iu, (_match, start, end, total) => `Showing ${start} to ${end} of ${total}`],
  [/^(\d+)\s+sản phẩm$/iu, (_match, count) => `${count} product${Number(count) === 1 ? '' : 's'}`],
  [/^Số lượng:\s*(\d+)$/iu, (_match, count) => `Quantity: ${count}`],
  [/^Trị giá:\s*(.+)$/iu, (_match, amount) => `Value: ${amount}`],
  [/^Tổng tiền:\s*(.+)$/iu, (_match, amount) => `Total amount: ${amount}`],
  [/^Kho chỉ còn\s+(\d+)\s+sản phẩm\.$/iu, (_match, count) => `Only ${count} product${Number(count) === 1 ? '' : 's'} left in stock.`],
]

const phraseMap = {
  'Quản lý': 'Manage',
  'quản lý': 'manage',
  'hệ thống': 'system',
  'Hệ thống': 'System',
  'nội dung': 'content',
  'Nội dung': 'Content',
  'điều hành': 'operate',
  'cấu hình': 'configuration',
  'Cấu hình': 'Configuration',
  'thông tin': 'information',
  'Thông tin': 'Information',
  'danh sách': 'list',
  'Danh sách': 'List',
  'danh mục': 'category',
  'Danh mục': 'Category',
  'thương hiệu': 'brand',
  'Thương hiệu': 'Brand',
  'biến thể': 'variant',
  'Biến thể': 'Variant',
  'màu sắc': 'color',
  'Màu sắc': 'Color',
  'hình ảnh': 'image',
  'Hình ảnh': 'Image',
  'ảnh': 'image',
  'Ảnh': 'Image',
  'tiêu đề': 'title',
  'Tiêu đề': 'Title',
  'phụ đề': 'subtitle',
  'Phụ đề': 'Subtitle',
  'mô tả': 'description',
  'Mô tả': 'Description',
  'vị trí': 'position',
  'Vị trí': 'Position',
  'hiển thị': 'display',
  'Hiển thị': 'Display',
  'ẩn': 'hidden',
  'Ẩn': 'Hidden',
  'kích hoạt': 'activate',
  'Kích hoạt': 'Activate',
  'tài khoản': 'account',
  'Tài khoản': 'Account',
  'khách hàng': 'customer',
  'Khách hàng': 'Customer',
  'người dùng': 'user',
  'Người dùng': 'User',
  'quản trị viên': 'administrator',
  'Quản trị viên': 'Administrator',
  'liên hệ': 'contact',
  'Liên hệ': 'Contact',
  'phản hồi': 'response',
  'Phản hồi': 'Response',
  'yêu cầu': 'request',
  'Yêu cầu': 'Request',
  'hỗ trợ': 'support',
  'Hỗ trợ': 'Support',
  'kỹ thuật': 'technical',
  'Kỹ thuật': 'Technical',
  'bảo hành': 'warranty',
  'Bảo hành': 'Warranty',
  'sửa chữa': 'repair',
  'Sửa chữa': 'Repair',
  'hợp tác': 'partnership',
  'Hợp tác': 'Partnership',
  'kinh doanh': 'business',
  'Kinh doanh': 'Business',
  'bài viết': 'article',
  'Bài viết': 'Article',
  'tin tức': 'news',
  'Tin tức': 'News',
  'công nghệ': 'technology',
  'Công nghệ': 'Technology',
  'sự kiện': 'event',
  'Sự kiện': 'Event',
  'nội bộ': 'internal',
  'Nội bộ': 'Internal',
  'đánh giá': 'review',
  'Đánh giá': 'Review',
  'bình luận': 'comment',
  'Bình luận': 'Comment',
  'duyệt': 'approve',
  'Duyệt': 'Approve',
  'chờ': 'pending',
  'Chờ': 'Pending',
  'đã': '',
  'Đã': '',
  'đang': 'processing',
  'Đang': 'Processing',
  'hoạt động': 'active',
  'Hoạt động': 'Active',
  'bị khóa': 'locked',
  'Bị khóa': 'Locked',
  'nháp': 'draft',
  'Nháp': 'Draft',
  'xuất bản': 'published',
  'Xuất bản': 'Published',
  'sắp xuất bản': 'scheduled',
  'Sắp xuất bản': 'Scheduled',
  'hết hạn': 'expired',
  'Hết hạn': 'Expired',
  'khả dụng': 'available',
  'Khả dụng': 'Available',
  'không khả dụng': 'unavailable',
  'Không khả dụng': 'Unavailable',
  'giá': 'price',
  'Giá': 'Price',
  'giảm giá': 'discount',
  'Giảm giá': 'Discount',
  'doanh thu': 'revenue',
  'Doanh thu': 'Revenue',
  'ngân sách': 'budget',
  'Ngân sách': 'Budget',
  'hoa hồng': 'commission',
  'Hoa hồng': 'Commission',
  'rút tiền': 'withdrawal',
  'Rút tiền': 'Withdrawal',
  'thanh toán': 'payment',
  'Thanh toán': 'Payment',
  'vận chuyển': 'shipping',
  'Vận chuyển': 'Shipping',
  'giao hàng': 'delivery',
  'Giao hàng': 'Delivery',
  'địa chỉ': 'address',
  'Địa chỉ': 'Address',
  'tỉnh': 'province',
  'Tỉnh': 'Province',
  'thành phố': 'city',
  'Thành phố': 'City',
  'phường': 'ward',
  'Phường': 'Ward',
  'xã': 'commune',
  'Xã': 'Commune',
  'mã': 'code',
  'Mã': 'Code',
  'tên': 'name',
  'Tên': 'Name',
  'loại': 'type',
  'Loại': 'Type',
  'giá trị': 'value',
  'Giá trị': 'Value',
  'thời gian': 'time',
  'Thời gian': 'Time',
  'ngày': 'day',
  'Ngày': 'Day',
  'tháng': 'month',
  'Tháng': 'Month',
  'năm': 'year',
  'Năm': 'Year',
  'tuần': 'week',
  'Tuần': 'Week',
  'mới': 'new',
  'Mới': 'New',
  'cũ': 'old',
  'Cũ': 'Old',
  'gốc': 'root',
  'Gốc': 'Root',
  'con': 'child',
  'Con': 'Child',
  'mục': 'item',
  'Mục': 'Item',
  'chọn': 'select',
  'Chọn': 'Select',
  'bỏ chọn': 'clear selection',
  'Bỏ chọn': 'Clear selection',
  'tải lên': 'upload',
  'Tải lên': 'Upload',
  'tải lại': 'reload',
  'Tải lại': 'Reload',
  'nhập': 'enter',
  'Nhập': 'Enter',
  'xuất': 'export',
  'Xuất': 'Export',
  'lưu': 'save',
  'Lưu': 'Save',
  'xóa': 'delete',
  'Xóa': 'Delete',
  'sửa': 'edit',
  'Sửa': 'Edit',
  'thêm': 'add',
  'Thêm': 'Add',
  'Vui lòng': 'Please',
  'Đang tải': 'Loading',
  'Đang lưu': 'Saving',
  'Đang gửi': 'Sending',
  'Đang xử lý': 'Processing',
  'Không thể': 'Cannot',
  'Không tìm thấy': 'Not found',
  'Chưa có': 'No',
  'Tất cả': 'All',
  'Chi tiết': 'Details',
  'Sản phẩm': 'Products',
  'Đơn hàng': 'Orders',
  'Khuyến mãi': 'Promotions',
  'Liên hệ': 'Contact',
  'Người dùng': 'Users',
  'Trạng thái': 'Status',
  'Hành động': 'Action',
  'Thao tác': 'Actions',
  'Ngày đặt': 'Order Date',
  'Tổng cộng': 'Total',
  'Tổng tiền': 'Total Amount',
  'Quay lại': 'Back',
  'Trước': 'Previous',
  'Sau': 'Next',
  'Tiếp tục': 'Continue',
  'Hủy đơn': 'Cancel Order',
  'Hoàn trả': 'Return',
  'Mua lại': 'Buy Again',
  'Đánh giá': 'Review',
  'Đã đánh giá': 'Reviewed',
  'Đã hoàn trả': 'Returned',
  'Địa chỉ': 'Address',
  'Mật khẩu': 'Password',
  'Bảo mật': 'Security',
  'Thông tin': 'Information',
  'Cập nhật': 'Update',
  'Thêm': 'Add',
  'Xóa': 'Delete',
  'Sửa': 'Edit',
}

const reversePhraseMap = {
  Home: 'Trang chủ',
  Overview: 'Tổng quan',
  Products: 'Sản phẩm',
  Product: 'Sản phẩm',
  Categories: 'Danh mục',
  Category: 'Danh mục',
  Brands: 'Thương hiệu',
  Brand: 'Thương hiệu',
  Sales: 'Bán hàng',
  Orders: 'Đơn hàng',
  Order: 'Đơn hàng',
  Content: 'Nội dung',
  Users: 'Người dùng',
  User: 'Người dùng',
  Banners: 'Banner',
  Details: 'Chi tiết',
  Detail: 'Chi tiết',
  Search: 'Tìm kiếm',
  Status: 'Trạng thái',
  Action: 'Hành động',
  Actions: 'Thao tác',
  Edit: 'Sửa',
  Delete: 'Xóa',
  Save: 'Lưu',
  Cancel: 'Hủy',
  Back: 'Quay lại',
  Next: 'Tiếp',
  Previous: 'Trước',
  Apply: 'Áp dụng',
  Filter: 'Lọc',
  Reload: 'Tải lại',
  Refresh: 'Làm mới',
  Active: 'Hoạt động',
  Inactive: 'Ngừng hoạt động',
  Pending: 'Chờ xử lý',
  Completed: 'Hoàn thành',
  Cancelled: 'Đã hủy',
  Refunded: 'Đã hoàn tiền',
  Administrator: 'Quản trị viên',
  'MAIN MENU': 'MENU CHÍNH',
  CORE: 'CỐT LÕI',
  CONFIG: 'CẤU HÌNH',
  SALES: 'BÁN HÀNG',
  MARKETING: 'TIẾP THỊ',
}

export const getLocale = () => {
  const saved = localStorage.getItem(APP_LOCALE_KEY) || localStorage.getItem(LEGACY_ADMIN_LOCALE_KEY) || 'vi'
  return supportedLocales.includes(saved) ? saved : 'vi'
}

export const setLocale = (locale) => {
  const nextLocale = supportedLocales.includes(locale) ? locale : 'vi'
  localStorage.setItem(APP_LOCALE_KEY, nextLocale)
  localStorage.setItem(LEGACY_ADMIN_LOCALE_KEY, nextLocale)
  document.documentElement.lang = nextLocale
  window.dispatchEvent(new CustomEvent('app-locale-changed', { detail: { locale: nextLocale } }))
  applyTranslations()
}

const translateExact = (value) => {
  const key = compact(value)
  return textMap[key] || normalizedTextMap[key.toLocaleLowerCase('vi-VN')] || value
}

const translateExactToVi = (value) => {
  const key = compact(value)
  return reverseTextMap[key.toLocaleLowerCase('en-US')] || reversePlaceholderMap[key.toLocaleLowerCase('en-US')] || reversePhraseMap[key] || value
}

const translatePhrases = (value) => {
  let result = value
  Object.entries(phraseMap).forEach(([source, target]) => {
    result = result.replaceAll(source, target)
  })
  return result
}

const translatePhrasesToVi = (value) => {
  let result = value
  Object.entries(reversePhraseMap).forEach(([source, target]) => {
    result = result.replace(new RegExp(`\\b${source.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'g'), target)
  })
  return result
}

const translate = (value, locale) => {
  const key = compact(value)

  if (locale === 'vi') {
    const exactVi = translateExactToVi(key)
    if (exactVi !== key) return exactVi
    const phraseVi = translatePhrasesToVi(key)
    return phraseVi !== key ? phraseVi : value
  }

  const exact = translateExact(key)
  if (exact !== key) return exact

  for (const [regex, replacer] of dynamicTextRules) {
    const match = key.match(regex)
    if (!match) continue
    const translated = replacer(...match)
    if (translated) return translated
  }

  const phraseTranslated = translatePhrases(key)
  if (phraseTranslated !== key) return phraseTranslated

  if (hasVietnamese(key)) return stripVietnameseMarks(key)

  return value
}

const translateAttribute = (value, locale) => {
  const key = compact(value)
  if (locale === 'vi') return translateExactToVi(key)
  return placeholderMap[key] ||
    normalizedPlaceholderMap[key.toLocaleLowerCase('vi-VN')] ||
    translate(key, locale)
}

const shouldSkip = (node) => {
  const element = node.nodeType === Node.TEXT_NODE ? node.parentElement : node
  if (!element) return true
  return Boolean(element.closest('script, style, noscript, textarea, code, pre, [data-i18n-ignore]'))
}

const setTextPreserveSpace = (node, original, translated) => {
  const leading = original.match(/^\s*/)?.[0] || ''
  const trailing = original.match(/\s*$/)?.[0] || ''
  const nextValue = `${leading}${translated}${trailing}`
  if (node.nodeValue !== nextValue) node.nodeValue = nextValue
}

export const applyTranslations = (root = document.body) => {
  if (!root) return
  const locale = getLocale()
  document.documentElement.lang = locale

  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode(node) {
      if (shouldSkip(node)) return NodeFilter.FILTER_REJECT
      if (!compact(node.nodeValue)) return NodeFilter.FILTER_REJECT
      return NodeFilter.FILTER_ACCEPT
    }
  })

  const textNodes = []
  while (walker.nextNode()) textNodes.push(walker.currentNode)

  textNodes.forEach((node) => {
    const original = originalTextNodes.get(node) || node.nodeValue
    if (!originalTextNodes.has(node)) originalTextNodes.set(node, original)
    const translated = translate(original, locale)
    setTextPreserveSpace(node, original, translated)
  })

  root.querySelectorAll?.('[placeholder], [title], [aria-label], [alt], input[value], button[value]').forEach((el) => {
    if (shouldSkip(el)) return
    const stored = originalAttrs.get(el) || {}
    const attrs = { ...stored }
    ;['placeholder', 'title', 'aria-label', 'alt', 'value'].forEach((attr) => {
      if (!el.hasAttribute(attr)) return
      if (attr === 'value' && !['button', 'submit', 'reset'].includes(el.getAttribute('type') || '')) return
      if (!attrs[attr]) attrs[attr] = el.getAttribute(attr)
      const nextValue = translateAttribute(attrs[attr], locale)
      if (el.getAttribute(attr) !== nextValue) el.setAttribute(attr, nextValue)
    })
    originalAttrs.set(el, attrs)
  })
}

export const installI18n = (router) => {
  document.documentElement.lang = getLocale()

  let pending = false
  const schedule = () => {
    if (pending) return
    pending = true
    requestAnimationFrame(() => {
      pending = false
      applyTranslations()
    })
  }

  router?.afterEach(() => {
    nextTick(() => setTimeout(schedule, 140))
  })

  window.addEventListener('app-locale-changed', schedule)
  window.addEventListener('pageshow', schedule)

  const appRoot = document.body || document.getElementById('app')
  if (appRoot) {
    const observer = new MutationObserver(schedule)
    observer.observe(appRoot, { childList: true, subtree: true, attributes: true, attributeFilter: ['placeholder', 'title', 'aria-label', 'alt', 'value'] })
  }

  schedule()
}
