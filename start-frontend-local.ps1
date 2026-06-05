param(
    [int] $Port = 5173
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$frontend = Join-Path $root 'frontend'

function Stop-FrontendDevServer {
    $nodeProcesses = Get-CimInstance Win32_Process -Filter "Name = 'node.exe'"
    $frontendProcesses = $nodeProcesses |
        Where-Object { $_.CommandLine -match [regex]::Escape($frontend) }

    $ids = New-Object System.Collections.Generic.HashSet[int]
    foreach ($process in $frontendProcesses) {
        [void] $ids.Add([int] $process.ProcessId)
        if ($process.ParentProcessId) {
            $parent = $nodeProcesses |
                Where-Object { $_.ProcessId -eq $process.ParentProcessId } |
                Select-Object -First 1
            if ($parent -and $parent.CommandLine -match 'npm-cli\.js') {
                [void] $ids.Add([int] $parent.ProcessId)
            }
        }
    }

    foreach ($id in $ids) {
        Stop-Process -Id $id -Force -ErrorAction SilentlyContinue
    }
}

Stop-FrontendDevServer
Start-Sleep -Seconds 1

$owner = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
    Select-Object -First 1

if ($owner) {
    $process = Get-Process -Id $owner.OwningProcess -ErrorAction SilentlyContinue
    throw "Port $Port is still in use by PID $($owner.OwningProcess) ($($process.ProcessName))."
}

Push-Location $frontend
try {
    & npm.cmd run dev -- --host 0.0.0.0 --port $Port
} finally {
    Pop-Location
}
