<#
organize_root.ps1

Moves loose files from repository root into appropriate folders and updates references.
Mapping rules (conservative):
  - .html -> public/
  - .php  -> php/
  - .sql  -> database/
  - .md   -> docs/
  - .css  -> css/
  - .js   -> js/
Other files will be left untouched.

The script will:
  - Move files from repo root (not inside folders) into destination folder.
  - If destination file exists, append a timestamp to the moved filename.
  - Scan project files (.html,.php,.js,.css) and replace occurrences of the old filename with the new location.
  - Create .bak backups of any file it modifies.

USAGE:
  cd "C:\Users\domin\Cooperativa La Pintada"
  powershell -ExecutionPolicy Bypass -File .\scripts\organize_root.ps1

Be conservative: review 'moved_files_report.txt' and '.bak' files before permanent deletion.
#>

$repoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path | Split-Path -Parent
if (-not $repoRoot) { $repoRoot = Get-Location }

Write-Host "Repository root:" $repoRoot

$excludeDirs = @('.git','scripts','tests','public','php','backend','controllers','css','js','database','docs','models','temp','tests','public','scripts')

# Dest mappings
$mapping = @{
    '.html' = 'public'
    '.php'  = 'php'
    '.sql'  = 'database'
    '.md'   = 'docs'
    '.css'  = 'css'
    '.js'   = 'js'
}

# Find files in root (non-directories)
$rootFiles = Get-ChildItem -Path $repoRoot -File | Where-Object { $excludeDirs -notcontains $_.Name }

# Filter files to move by extension
$filesToMove = @()
foreach ($f in $rootFiles) {
    $ext = $f.Extension.ToLower()
    if ($mapping.ContainsKey($ext)) { $filesToMove += $f }
}

if ($filesToMove.Count -eq 0) {
    Write-Host "No movibles detectados en la raíz según reglas. Will attempt to fix references from previous report if present."
}

$report = @()

foreach ($file in $filesToMove) {
    $ext = $file.Extension.ToLower()
    $destFolderName = $mapping[$ext]
    $destFolder = Join-Path $repoRoot $destFolderName
    if (-not (Test-Path $destFolder)) { New-Item -ItemType Directory -Path $destFolder | Out-Null }

    $destPath = Join-Path $destFolder $file.Name
    if (Test-Path $destPath) {
        $timestamp = (Get-Date).ToString('yyyyMMdd_HHmmss')
        $base = [IO.Path]::GetFileNameWithoutExtension($file.Name)
        $newName = "${base}_moved_${timestamp}${file.Extension}"
        $destPath = Join-Path $destFolder $newName
    }

    Move-Item -Path $file.FullName -Destination $destPath
    Write-Host "Moved: $($file.Name) -> $($destPath)"
    $report += "Moved: $($file.FullName) -> $destPath"
}

# Update references across repo: replace occurrences of moved filenames with new relative path 'folder/filename'
$extsToScan = '*.html','*.php','*.js','*.css'

# If no moves happened in this run, try to read previous report (conservative fix)
if ($report.Count -eq 0) {
    $reportPath = Join-Path $repoRoot 'scripts\moved_files_report.txt'
    if (Test-Path $reportPath) {
        $report = Get-Content -LiteralPath $reportPath -ErrorAction SilentlyContinue
    }
}

foreach ($m in $report) {
    if ($m -match 'Moved: (.+) -> (.+)') {
        $oldFull = $matches[1]
        $newFull = $matches[2]
        $old = [IO.Path]::GetFileName($oldFull)
        # Compute a path relative to repo root for replacement
        $newPath = $newFull
        if ($newPath.StartsWith($repoRoot)) {
            $relative = $newPath.Substring($repoRoot.Length + 1) -replace '\\','/'
        } else {
            # fallback: use filename only
            $relative = [IO.Path]::GetFileName($newPath)
        }

        foreach ($ext in $extsToScan) {
            $files = Get-ChildItem -Path $repoRoot -Recurse -Include $ext -File -ErrorAction SilentlyContinue
            foreach ($f in $files) {
                $content = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction SilentlyContinue
                if ($null -eq $content) { continue }
                $original = $content
                $content = $content -replace [regex]::Escape($old), $relative
                if ($content -ne $original) {
                    Copy-Item -Path $f.FullName -Destination "$($f.FullName).bak" -Force
                    Set-Content -LiteralPath $f.FullName -Value $content -Force
                    Write-Host "Updated references in: $($f.FullName) (replaced $old -> $relative)"
                }
            }
        }
    }
}

# Write report
$reportPath = Join-Path $repoRoot 'scripts\moved_files_report.txt'
$report | Out-File -FilePath $reportPath -Encoding utf8
Write-Host "\nReport saved to: $reportPath"
Write-Host "Done. Please review moved files and .bak backups before deleting anything permanently."