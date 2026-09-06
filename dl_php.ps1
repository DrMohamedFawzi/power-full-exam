Add-Type -AssemblyName System.Net.Http
$url = "https://windows.php.net/downloads/releases/php-8.4.24-Win32-vs17-x64.zip"
$dest = "C:\Users\Pc\Downloads\php84_ok.zip"
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$client = New-Object System.Net.Http.HttpClient
$client.Timeout = [TimeSpan]::FromMinutes(10)
Write-Host "⬇️ جاري التنزيل المباشر الموثوق عبر HttpClient..." -ForegroundColor Cyan
$response = $client.GetAsync($url).Result
if ($response.IsSuccessStatusCode) {
    $fileStream = [System.IO.File]::Create($dest)
    $httpStream = $response.Content.ReadAsStreamAsync().Result
    $httpStream.CopyTo($fileStream)
    $fileStream.Close()
    $httpStream.Close()
    $mb = [math]::Round((Get-Item $dest).Length/1MB,1)
    Write-Host "🎉 اكتمل التنزيل بنجاح ($mb MB)!" -ForegroundColor Green
    if (!(Test-Path "C:\php84")) { New-Item -ItemType Directory -Path "C:\php84" -Force | Out-Null }
    Expand-Archive -Path $dest -DestinationPath "C:\php84" -Force
    Write-Host "✅ تم الاستخراج في C:\php84" -ForegroundColor Green
    & "C:\php84\php.exe" --version
} else {
    Write-Host "❌ HTTP Error: $($response.StatusCode)" -ForegroundColor Red
}
