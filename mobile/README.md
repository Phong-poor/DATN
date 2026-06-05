# Predator Group Mobile

Flutter app dung chung Laravel API va MySQL cua project. App khong dung SQLite va
khong can chay Vite/frontend.

## Chay local

1. Bat MySQL trong XAMPP.
2. Tu thu muc goc `DATN`, chay mot lenh:

```powershell
powershell -ExecutionPolicy Bypass -File .\run-mobile-local.ps1
```

Lenh tren se:

- khoi dong Laravel API o `http://127.0.0.1:8000/api`
- khoi dong media server o `http://127.0.0.1:8001/storage`
- dung emulator `DATN_Lite` neu chua co emulator dang chay
- chay Flutter bang `flutter run --profile` de app muot hon tren may RAM thap

Khi can hot reload:

```powershell
powershell -ExecutionPolicy Bypass -File .\run-mobile-local.ps1 -Debug
```

Neu muon chay thu cong:

```powershell
powershell -ExecutionPolicy Bypass -File .\start-mobile-local.ps1
cd .\mobile
flutter emulators --launch DATN_Lite
flutter devices
flutter run -d emulator-5554
```

Android emulator goi API qua `http://10.0.2.2:8000/api`. Chrome, Windows va iOS
simulator goi API qua `http://127.0.0.1:8000/api`. Anh san pham di qua port
`8001` de khong chan API.

Tai khoan test local:

```text
mobile@local.test
Password@123
```

Voi dien thoai Android that, truyen IP LAN cua may dang chay Laravel:

```powershell
flutter run -d <device-id> --dart-define=API_BASE_URL=http://<LAN-IP>:8000/api --dart-define=ASSET_BASE_URL=http://<LAN-IP>:8001/storage
```

Nguoi dung co the xem trang chu, danh muc, san pham, khuyen mai va tin tuc khi
chua dang nhap. App chi yeu cau dang nhap khi mo gio hang, thanh toan, don hang,
yeu thich hoac tai khoan.

## Kiem tra

```powershell
flutter analyze
flutter test
```

## Khi emulator cham hoac bao System UI not responding

May hien RAM khong du rong. Tat bot Chrome, Android Studio, Java/Gradle cu hoac
app nang, sau do chay lai `run-mobile-local.ps1`. Script da giam RAM Gradle
xuong 768MB va mac dinh dung `DATN_Lite` thay vi `Pixel_4` vi Pixel_4 Google
Play image qua nang cho may 8GB RAM.

Neu lan dau cold boot hien popup `System UI isn't responding`, bam `Wait`. Day
la loi emulator bi thieu RAM host, khong phai crash cua app.
