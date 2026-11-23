# Script PowerShell untuk membuat arsip ZIP siap kumpul (KELOMPOK5.zip)
# Menyingkirkan folder 'vendor' dan 'node_modules' agar ukuran ringan,
# namun tetap menyertakan '.env', '.env.example', 'INFO_KELOMPOK5.txt',
# 'composer.json', 'routes/api.php', 'app/', 'database/migrations/', 'database/seeders/', dll.

$sourceDir = $PSScriptRoot
$parentDir = Split-Path $sourceDir -Parent
$zipOutput = Join-Path $parentDir "KELOMPOK5.zip"

Write-Host "Sedang menyiapkan arsip pengumpulan KELOMPOK5.zip..." -ForegroundColor Cyan

# Hapus zip lama jika ada
if (Test-Path $zipOutput) {
    Remove-Item $zipOutput -Force
}

# Daftar folder/file yang dikecualikan (.env TETAP DISERTAKAN sesuai ketentuan)
$exclude = @("vendor", "node_modules", ".git", ".phpunit.cache", ".phpunit.result.cache")

# Copy ke folder temporary untuk dizip
$tempDir = Join-Path $env:TEMP "washpoint_kelompok5_$(Get-Random)"
$projectDest = Join-Path $tempDir "washpoint"
New-Item -ItemType Directory -Path $projectDest -Force | Out-Null

Get-ChildItem -Path $sourceDir -Force | Where-Object {
    $name = $_.Name
    $exclude -notcontains $name
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $projectDest -Recurse -Force
}

# Salin juga INFO_KELOMPOK5.txt di level root arsip ZIP agar mudah langsung dibaca
if (Test-Path (Join-Path $sourceDir "INFO_KELOMPOK5.txt")) {
    Copy-Item -Path (Join-Path $sourceDir "INFO_KELOMPOK5.txt") -Destination $tempDir -Force
}

# Compress dengan path slash '/' (cross-platform Windows/macOS/Linux) dan tetap menyertakan dotfile (.env, .env.example)
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($zipOutput, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -Path $tempDir -Recurse -Force -File | ForEach-Object {
        $relativePath = $_.FullName.Substring($tempDir.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $relativePath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $zip.Dispose()
}

# Bersihkan temp
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "BERHASIL! File ZIP siap kumpul telah dibuat di:" -ForegroundColor Green
Write-Host $zipOutput -ForegroundColor Yellow
