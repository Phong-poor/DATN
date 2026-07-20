$response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/mobile/home" -Method GET -UseBasicParsing
Write-Host "Status Code: $($response.StatusCode)"
$data = $response.Content | ConvertFrom-Json
Write-Host "Products: $($data.products.Count)"
Write-Host "Categories: $($data.categories.Count)"
if ($data.products.Count -gt 0) {
    Write-Host "First product: $($data.products[0].tenSP)"
}
