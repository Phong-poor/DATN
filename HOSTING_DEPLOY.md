# Triển khai hosting

## 1. Backend Laravel

Sao chép `backend/.env.hosting.example` thành `backend/.env`, sau đó điền domain, database, email và khóa dịch vụ thật.

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Document root của domain API phải trỏ vào thư mục `backend/public`.

Nếu hosting không chạy queue worker liên tục, thêm cron mỗi phút:

```cron
* * * * * cd /duong-dan/backend && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /duong-dan/backend && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

Cron `schedule:run` là bắt buộc để hệ thống gửi email sinh nhật tự động.

## 2. Frontend Vue

Sao chép `frontend/.env.production.example` thành `frontend/.env.production`, thay `https://tenmien.com` bằng domain thật rồi chạy:

```bash
cd frontend
npm ci
npm run build
```

Đưa toàn bộ nội dung trong `frontend/dist` lên document root của frontend. Web server cần fallback các URL không tồn tại về `index.html` để Vue Router hoạt động khi tải lại trang.

## 3. Hai mô hình domain

Khuyên dùng cùng domain:

- Website: `https://tenmien.com`
- API: `https://tenmien.com/api`
- Storage: `https://tenmien.com/storage`

Nếu backend dùng subdomain riêng:

- Website: `https://tenmien.com`
- API: `https://api.tenmien.com/api`
- `APP_URL=https://api.tenmien.com`
- `FRONTEND_URL=https://tenmien.com`
- `CORS_ALLOWED_ORIGINS=https://tenmien.com`

## 4. Sau mỗi lần cập nhật

```bash
cd backend
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

cd ../frontend
npm ci
npm run build
```

Không đưa `.env` thật lên Git và không đặt mật khẩu hoặc secret vào biến frontend bắt đầu bằng `VITE_`.
