# Script kiểm tra và khởi động Backend API
# Chạy script này để test API mobile/home

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "  KIỂM TRA VÀ KHỞI ĐỘNG BACKEND" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

$backendPath = "d:\USER\Downloads\laragon\www\DATN\backend"
$apiUrl = "http://127.0.0.1:8000/api/mobile/home"

# Bước 1: Kiểm tra xem backend server có đang chạy không
Write-Host "[1/5] Kiểm tra backend server..." -ForegroundColor Yellow

try {
    $response = Invoke-WebRequest -Uri $apiUrl -Method GET -TimeoutSec 5 -ErrorAction Stop
    
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Backend đang chạy và API hoạt động tốt!" -ForegroundColor Green
        Write-Host "📊 Status Code: $($response.StatusCode)" -ForegroundColor Green
        
        # Parse JSON response
        $data = $response.Content | ConvertFrom-Json
        $productCount = $data.products.Count
        $categoryCount = $data.categories.Count
        
        Write-Host "📦 Số sản phẩm: $productCount" -ForegroundColor Green
        Write-Host "📁 Số danh mục: $categoryCount" -ForegroundColor Green
        Write-Host ""
        Write-Host "✅ MOBILE APP SẼ HOẠT ĐỘNG BÌNH THƯỜNG!" -ForegroundColor Green
        
        # Hiển thị mẫu dữ liệu
        Write-Host ""
        Write-Host "📄 Mẫu dữ liệu trả về:" -ForegroundColor Cyan
        Write-Host $response.Content.Substring(0, [Math]::Min(500, $response.Content.Length)) -ForegroundColor Gray
        
        exit 0
    }
    elseif ($response.StatusCode -eq 204) {
        Write-Host "⚠️  API trả về 204 No Content - Database có thể trống!" -ForegroundColor Yellow
        Write-Host "💡 Hướng dẫn: Kiểm tra database có dữ liệu không" -ForegroundColor Yellow
    }
    else {
        Write-Host "⚠️  API trả về status code: $($response.StatusCode)" -ForegroundColor Yellow
    }
}
catch {
    Write-Host "❌ Backend KHÔNG chạy hoặc không kết nối được!" -ForegroundColor Red
    Write-Host "📝 Lỗi: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "[2/5] Kiểm tra thư mục backend..." -ForegroundColor Yellow

if (-Not (Test-Path $backendPath)) {
    Write-Host "❌ Không tìm thấy thư mục backend: $backendPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Tìm thấy thư mục backend" -ForegroundColor Green

# Bước 2: Clear cache
Write-Host ""
Write-Host "[3/5] Clear cache Laravel..." -ForegroundColor Yellow

Set-Location $backendPath

try {
    Write-Host "  - Clearing cache..." -ForegroundColor Gray
    php artisan cache:clear 2>&1 | Out-Null
    
    Write-Host "  - Clearing config..." -ForegroundColor Gray
    php artisan config:clear 2>&1 | Out-Null
    
    Write-Host "  - Clearing route cache..." -ForegroundColor Gray
    php artisan route:clear 2>&1 | Out-Null
    
    Write-Host "✅ Cache đã được xóa" -ForegroundColor Green
}
catch {
    Write-Host "⚠️  Không thể clear cache: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Bước 3: Kiểm tra database connection
Write-Host ""
Write-Host "[4/5] Kiểm tra kết nối database..." -ForegroundColor Yellow

try {
    $dbTest = php artisan db:show 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Database kết nối thành công" -ForegroundColor Green
    }
    else {
        Write-Host "❌ Không kết nối được database!" -ForegroundColor Red
        Write-Host "💡 Hướng dẫn: Kiểm tra MySQL có đang chạy không (Laragon)" -ForegroundColor Yellow
    }
}
catch {
    Write-Host "⚠️  Không thể kiểm tra database" -ForegroundColor Yellow
}

# Bước 4: Khởi động backend server
Write-Host ""
Write-Host "[5/5] Khởi động Laravel development server..." -ForegroundColor Yellow
Write-Host "📍 Server sẽ chạy tại: http://127.0.0.1:8000" -ForegroundColor Cyan
Write-Host "🔄 Sau khi server khởi động, refresh lại mobile app" -ForegroundColor Cyan
Write-Host ""
Write-Host "⚠️  Để DỪNG server: Nhấn Ctrl+C" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  BACKEND SERVER ĐANG KHỞI ĐỘNG..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Start Laravel server
php artisan serve --host=127.0.0.1 --port=8000
