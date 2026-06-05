$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root 'backend'
$mysql = 'C:\xampp\mysql\bin\mysqld.exe'
$mysqlConfig = 'C:\xampp\mysql\bin\my.ini'
$php = 'C:\xampp\php\php.exe'

function Test-LocalPort([int] $Port) {
    return [bool](Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

function Wait-LocalPort([int] $Port, [int] $Seconds = 20) {
    foreach ($attempt in 1..$Seconds) {
        if (Test-LocalPort $Port) {
            return
        }
        Start-Sleep -Seconds 1
    }
    throw "Port $Port did not start listening."
}

function Stop-PhpServers([int] $Port) {
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" |
        Where-Object {
            $_.CommandLine -match "artisan serve.*port=$Port" -or
            $_.CommandLine -match "\s-S\s+127\.0\.0\.1:$Port"
        } | ForEach-Object {
            Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
        }
}

if (-not (Test-LocalPort 3306)) {
    Start-Process `
        -FilePath $mysql `
        -ArgumentList "--defaults-file=$mysqlConfig", '--standalone' `
        -WindowStyle Hidden
    Wait-LocalPort 3306
}

Push-Location $backend
try {
    & $php artisan config:clear --no-ansi | Out-Null
    & $php artisan route:clear --no-ansi | Out-Null
    & $php artisan migrate --force --no-ansi
    $ensureMobileUser = @'
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\User::updateOrCreate(
    ['email' => 'mobile@local.test'],
    [
        'name' => 'Mobile Local',
        'phone' => '0901234567',
        'password' => Illuminate\Support\Facades\Hash::make('Password@123'),
        'role' => 'user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]
);
'@
    & $php -r $ensureMobileUser
    & $php artisan config:cache --no-ansi | Out-Null
} finally {
    Pop-Location
}

Stop-PhpServers 8000
Start-Process `
    -FilePath $php `
    -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', '--port=8000' `
    -WorkingDirectory $backend `
    -RedirectStandardOutput (Join-Path $backend 'storage\logs\artisan-serve.out.log') `
    -RedirectStandardError (Join-Path $backend 'storage\logs\artisan-serve.err.log') `
    -WindowStyle Hidden
Wait-LocalPort 8000

Stop-PhpServers 8001
Start-Process `
    -FilePath $php `
    -ArgumentList '-S', '127.0.0.1:8001', '-t', 'public' `
    -WorkingDirectory $backend `
    -RedirectStandardOutput (Join-Path $backend 'storage\logs\static-serve.out.log') `
    -RedirectStandardError (Join-Path $backend 'storage\logs\static-serve.err.log') `
    -WindowStyle Hidden
Wait-LocalPort 8001

$apiStatus = (Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8000/api/test').StatusCode

Write-Host ''
Write-Host 'Mobile local services are ready:' -ForegroundColor Green
Write-Host "  API:   http://127.0.0.1:8000/api/test ($apiStatus)"
Write-Host '  Media: http://127.0.0.1:8001/storage (ready)'
Write-Host '  MySQL: laravel @ 127.0.0.1:3306'
Write-Host '  Login: mobile@local.test / Password@123'
Write-Host ''
Write-Host 'Run Flutter from .\mobile:'
Write-Host '  flutter emulators --launch DATN_Lite'
Write-Host '  flutter run -d emulator-5554'
