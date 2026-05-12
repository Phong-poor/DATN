# PHP3 - Vue + Laravel Project

Dự án fullstack sử dụng **Laravel** (Backend API) + **Vue 3** (Frontend).

## 🛠 Tech Stack

| Layer    | Technology        |
|----------|-------------------|
| Frontend | Vue 3 + Vite      |
| Backend  | Laravel 11        |
| Database | MySQL             |
| Styling  | Tailwind CSS      |

## 📁 Cấu trúc thư mục
```
php3/
├── backend/     # Laravel API
└── frontend/    # Vue 3 + Vite
```

## 🚀 Cài đặt

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend (Vue)
```bash
cd frontend
npm install
npm run dev
```

## 🌐 URL

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api

## 📡 API Endpoints

| Method | Endpoint            | Mô tả               |
|--------|---------------------|---------------------|
| GET    | /api/products       | Lấy danh sách sản phẩm |
| GET    | /api/products/{id}  | Lấy chi tiết sản phẩm  |
| POST   | /api/products       | Thêm sản phẩm          |
| PUT    | /api/products/{id}  | Cập nhật sản phẩm      |
| DELETE | /api/products/{id}  | Xóa sản phẩm           |

## ⚙️ Yêu cầu hệ thống

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL

## ⚙️ Cách chạy dự án

+ Backend:
cd backend
php artisan serve

+ Frontend
cd frontend
npm run dev

* Lưu ý: Trước khi chạy frontend thì phải có bootstrap,
axios, router, vuex, nếu chx có thì cài như sau:
npm install bootstrap

npm install axios

npm install vue-router@4

npm install vuex@next --save