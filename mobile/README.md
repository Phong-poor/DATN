# 📱 Ứng dụng di động DATN (React Native - Expo)

Dự án này là phần ứng dụng di động được xây dựng bằng **React Native** sử dụng **Expo SDK** và **React Navigation**.

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Framework | React Native (Expo) |
| Navigation | React Navigation (Bottom Tabs) |
| Language | JavaScript (ES6+) |
| Target Platforms | Android, iOS, Web (Chrome) |

## 📁 Cấu trúc thư mục (src/)

```text
mobile/src/
├── components/   # Các component dùng chung
├── screens/      # Các màn hình chính (Trang chủ, Danh mục, Giỏ hàng, Tài khoản)
├── navigation/   # Cấu hình định tuyến (AppNavigator)
├── services/     # Các hàm gọi API (Laravel backend)
├── store/        # Quản lý state (Redux / Context API)
└── utils/        # Các hàm tiện ích dùng chung
```

## 🚀 Hướng dẫn chạy dự án

Di chuyển vào thư mục `mobile`:
```bash
cd mobile
```

### 1. Chạy trên trình duyệt Web (Chrome)
Để xem và phát triển giao diện nhanh chóng trên trình duyệt Web:
```bash
npm run web
```
Trình duyệt sẽ tự động mở trang `http://localhost:8081`.

### 2. Chạy trên thiết bị Android / Android Emulator
Khởi chạy Expo CLI:
```bash
npm run android
```
- Nếu sử dụng thiết bị thật: Cài đặt app **Expo Go** trên điện thoại từ Google Play, quét mã QR hiển thị trong terminal để chạy app.
- Nếu sử dụng máy ảo: Đảm bảo máy ảo Android đã được khởi chạy trước khi chạy lệnh.

### 3. Chạy trên iOS (Expo Go)
```bash
npm run ios
```
- Sử dụng ứng dụng **Expo Go** trên iPhone để quét mã QR từ camera.

## 🔗 Liên kết API
Ứng dụng sử dụng cấu trúc `React Navigation` chuẩn được bọc trong `NavigationContainer` tại file [App.js](file:///d:/USER/Downloads/laragon/www/DATN/mobile/App.js).
Cấu hình router nằm trong [AppNavigator.jsx](file:///d:/USER/Downloads/laragon/www/DATN/mobile/src/navigation/AppNavigator.jsx).
Các màn hình nằm trong [screens/](file:///d:/USER/Downloads/laragon/www/DATN/mobile/src/screens).
