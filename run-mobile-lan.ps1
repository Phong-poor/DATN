param(
    [string] $LanIp
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$mobile = Join-Path $root 'mobile'

function Get-ActiveLanIp {
    $adapters = [System.Net.NetworkInformation.NetworkInterface]::GetAllNetworkInterfaces() |
        Where-Object {
            $_.OperationalStatus -eq [System.Net.NetworkInformation.OperationalStatus]::Up -and
            $_.NetworkInterfaceType -ne [System.Net.NetworkInformation.NetworkInterfaceType]::Loopback
        }

    foreach ($adapter in $adapters) {
        $properties = $adapter.GetIPProperties()
        $hasIpv4Gateway = $properties.GatewayAddresses | Where-Object {
            $_.Address.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork
        }

        if (-not $hasIpv4Gateway) {
            continue
        }

        $address = $properties.UnicastAddresses | Where-Object {
            $_.Address.AddressFamily -eq [System.Net.Sockets.AddressFamily]::InterNetwork -and
            -not $_.Address.IPAddressToString.StartsWith('169.254.')
        } | Select-Object -First 1

        if ($address) {
            return $address.Address.IPAddressToString
        }
    }

    throw 'Khong tim thay IPv4 LAN. Hay ket noi Wi-Fi hoac truyen -LanIp.'
}

if (-not $LanIp) {
    $LanIp = Get-ActiveLanIp
}

$serverOrigin = "http://$LanIp/DATN/backend/public"
$envFile = Join-Path $mobile '.env.local'
$envContent = "EXPO_PUBLIC_SERVER_ORIGIN=$serverOrigin`r`n"
[System.IO.File]::WriteAllText($envFile, $envContent, [System.Text.UTF8Encoding]::new($false))

$env:EXPO_PUBLIC_SERVER_ORIGIN = $serverOrigin

try {
    $apiResponse = Invoke-WebRequest `
        -Uri "$serverOrigin/api/mobile/home" `
        -UseBasicParsing `
        -TimeoutSec 20
} catch {
    throw "Khong truy cap duoc API LAN tai $serverOrigin. Hay bat Apache va MySQL trong Laragon. $($_.Exception.Message)"
}

Write-Host ''
Write-Host 'Mobile LAN is ready:' -ForegroundColor Green
Write-Host "  Server: $serverOrigin"
Write-Host "  API:    $serverOrigin/api/mobile/home ($($apiResponse.StatusCode))"

try {
    $runningExpo = Invoke-WebRequest `
        -Uri 'http://127.0.0.1:8081' `
        -UseBasicParsing `
        -TimeoutSec 5
} catch {
    $runningExpo = $null
}

if ($runningExpo.StatusCode -eq 200) {
    Write-Host "  Expo:   exp://${LanIp}:8081" -ForegroundColor Cyan
    Write-Host ''
    Write-Host 'Expo dang chay. Hay Reload trong Expo Go de nhan cau hinh LAN moi.' -ForegroundColor Yellow
    exit 0
}

Write-Host ''
Write-Host 'Starting Expo Go in LAN mode...' -ForegroundColor Cyan
Set-Location $mobile
& npm.cmd run lan
