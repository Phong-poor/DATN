param(
    [Parameter(Position=0)][string]$Mode,
    [Parameter(Position=1)][string]$Archive,
    [Parameter(Position=2)][string]$Entry
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($Archive)
try {
    if ($Mode -eq '-Z1') {
        foreach ($item in $zip.Entries) { [Console]::Out.WriteLine($item.FullName) }
        exit 0
    }

    if ($Mode -eq '-p') {
        $item = $zip.GetEntry($Entry)
        if ($null -eq $item) { exit 11 }
        $stream = $item.Open()
        try { $stream.CopyTo([Console]::OpenStandardOutput()) }
        finally { $stream.Dispose() }
        exit 0
    }

    exit 2
}
finally {
    $zip.Dispose()
}
