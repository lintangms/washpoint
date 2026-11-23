# Script PowerShell untuk membuat arsip ZIP siap kumpul di BRONE
# Menyingkirkan folder 'vendor' dan file '.env' sesuai aturan tugas kuliah

$sourceDir = $PSScriptRoot
$parentDir = Split-Path $sourceDir -Parent
$zipOutput = Join-Path $parentDir "WashPoint-API_Kelompok.zip"

Write-Host "Sedang menyiapkan arsip pengumpulan BRONE..." -ForegroundColor Cyan

# Hapus zip lama jika ada
if (Test-Path $zipOutput) {
    Remove-Item $zipOutput -Force
}

# Daftar yang dikecualikan
$exclude = @("vendor", ".env", "node_modules", ".git", ".phpunit.cache")

# Copy ke folder temporary untuk dizip
$tempDir = Join-Path $env:TEMP "washpoint_clean_$(Get-Random)"
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null

Get-ChildItem -Path $sourceDir -Force | Where-Object {
    $name = $_.Name
    $exclude -notcontains $name
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $tempDir -Recurse -Force
}

# Compress
Compress-Archive -Path "$tempDir\*" -DestinationPath $zipOutput -Force

# Bersihkan temp
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "BERHASIL! File ZIP siap kumpul telah dibuat di:" -ForegroundColor Green
Write-Host $zipOutput -ForegroundColor Yellow
