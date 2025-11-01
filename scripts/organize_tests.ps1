<#
organize_tests.ps1

Script to collect 'test_*' files from repository root into tests/orphaned_root.
- Moves files test_*.* located at repo root into 'tests/orphaned_root/'.
- If a file with the same name exists in 'tests/' the script will append a timestamp to avoid overwrite.
- Scans repo files (.html, .php, .js) and replaces references to moved files (basic string replace).

USAGE (PowerShell):
  cd "C:\Users\domin\Cooperativa La Pintada"
  .\scripts\organize_tests.ps1

This script is conservative: it moves files but does NOT delete anything permanently.
Please review 'tests/orphaned_root' after running and confirm before removing files.
#>

$repoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path | Split-Path -Parent
if (-not $repoRoot) { $repoRoot = Get-Location }

Write-Host "Repository root detected:" $repoRoot

$destDir = Join-Path $repoRoot "tests\orphaned_root"
if (-not (Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir | Out-Null }

# Pattern: files that start with test_ at repo root (not in subfolders)
$rootFiles = Get-ChildItem -Path $repoRoot -File | Where-Object { $_.Name -like 'test_*' }

if ($rootFiles.Count -eq 0) {
    Write-Host "No test_* files found in repository root. Nothing to move."; exit 0
}

Write-Host "Found $($rootFiles.Count) test_* files in repo root. Moving to tests/orphaned_root...`n"

foreach ($file in $rootFiles) {
    $src = $file.FullName
    $dest = Join-Path $destDir $file.Name

    if (Test-Path $dest) {
        # Avoid overwriting: append timestamp
        $timestamp = (Get-Date).ToString('yyyyMMdd_HHmmss')
        $base = [IO.Path]::GetFileNameWithoutExtension($file.Name)
        $ext = $file.Extension
        $newName = "${base}_moved_${timestamp}${ext}"
        $dest = Join-Path $destDir $newName
        Write-Host "Destination exists, renaming to: $newName"
    }

    Move-Item -Path $src -Destination $dest
    Write-Host "Moved: $($file.Name) -> tests/orphaned_root/"
}

# Basic search+replace in repo to update references from '/test_foo.html' to '/tests/orphaned_root/test_foo.html'
# This is a simple textual replacement — review results manually.

$extsToScan = '*.html','*.php','*.js','*.css'

foreach ($ext in $extsToScan) {
    $files = Get-ChildItem -Path $repoRoot -Recurse -Include $ext -File -ErrorAction SilentlyContinue
    foreach ($f in $files) {
        $content = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction SilentlyContinue
        if ($null -eq $content) { continue }

        $original = $content
        foreach ($moved in $rootFiles) {
            $name = $moved.Name
            # Reemplazo textual básico: "test_xxx" -> "tests/orphaned_root/test_xxx"
            $content = $content -replace [regex]::Escape($name), "tests/orphaned_root/$name"
        }

        if ($content -ne $original) {
            # Backup original file
            Copy-Item -Path $f.FullName -Destination "$($f.FullName).bak" -Force
            Set-Content -LiteralPath $f.FullName -Value $content -Force
            Write-Host "Updated references in:" $f.FullName
        }
    }
}

Write-Host "\nDone. Review the files in 'tests/orphaned_root' and the .bak backups created for modified files."
Write-Host "If you want to permanently remove moved files later, verify and delete manually."