param(
    [switch] $Debug,
    [string] $AvdName = 'DATN_Lite'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$mobile = Join-Path $root 'mobile'
$gradle = Join-Path $mobile 'android\gradlew.bat'
$adb = Join-Path $env:LOCALAPPDATA 'Android\Sdk\platform-tools\adb.exe'
$emulator = Join-Path $env:LOCALAPPDATA 'Android\Sdk\emulator\emulator.exe'

& (Join-Path $root 'start-mobile-local.ps1')

Push-Location (Join-Path $mobile 'android')
try {
    & $gradle --stop | Out-Null
} finally {
    Pop-Location
}

& $adb start-server | Out-Null

function Get-EmulatorDevice {
    $devices = & $adb devices
    foreach ($line in $devices) {
        if ($line -match '^(emulator-\d+)\s+device$') {
            return $Matches[1]
        }
    }
    return $null
}

$device = Get-EmulatorDevice
$startedEmulator = $false
if (-not $device) {
    Start-Process `
        -FilePath $emulator `
        -ArgumentList '-avd', $AvdName, '-memory', '1536', '-cores', '1', '-no-snapshot-load', '-no-snapshot-save', '-no-boot-anim', '-no-audio', '-camera-back', 'none', '-camera-front', 'none'
    $startedEmulator = $true
}

foreach ($attempt in 1..180) {
    Start-Sleep -Seconds 2
    if (-not $device) {
        $device = Get-EmulatorDevice
    }
    if (-not $device) {
        continue
    }
    $booted = & $adb -s $device shell getprop sys.boot_completed 2>$null
    if ($booted -eq '1') {
        break
    }
    if ($attempt -eq 180) {
        throw 'Emulator did not finish booting.'
    }
}

$disabledPackages = @(
    'com.android.chrome',
    'com.google.android.gm',
    'com.google.android.apps.photos',
    'com.google.android.youtube',
    'com.google.android.apps.messaging',
    'com.google.android.apps.wellbeing',
    'com.android.vending',
    'com.google.android.googlequicksearchbox',
    'com.google.android.as',
    'com.google.android.apps.wallpaper',
    'com.google.android.tts',
    'com.google.android.partnersetup',
    'com.google.android.settings.intelligence',
    'com.google.android.photopicker',
    'com.google.android.dialer',
    'com.google.android.cellbroadcastreceiver',
    'com.google.android.configupdater'
)

foreach ($package in $disabledPackages) {
    & $adb -s $device shell pm disable-user --user 0 $package 2>$null | Out-Null
}

& $adb -s $device shell wm size 720x1520 | Out-Null
& $adb -s $device shell wm density 320 | Out-Null
& $adb -s $device shell settings put global window_animation_scale 0.5 | Out-Null
& $adb -s $device shell settings put global transition_animation_scale 0.5 | Out-Null
& $adb -s $device shell settings put global animator_duration_scale 0.5 | Out-Null

if ($startedEmulator) {
    Write-Host 'Waiting 30s for Android System UI to settle...' -ForegroundColor Yellow
    Start-Sleep -Seconds 30
}

Write-Host ''
Write-Host "Emulator $device and local services are ready. Starting Flutter..." -ForegroundColor Green
Set-Location $mobile
$mode = if ($Debug) { '--debug' } else { '--profile' }
& flutter run -d $device --no-pub $mode
