#Requires -Version 5.1
<#
.SYNOPSIS
  Monitor externo de espaço em disco para o SGP (Windows).

.DESCRIPTION
  Não apaga arquivos. Apenas mede espaço livre e retorna exit code:
    0 = OK
    1 = AVISO
    2 = CRITICO / erro de leitura

  Integração sugerida: Agendador de Tarefas do Windows.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\windows\check-disk.ps1

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\windows\check-disk.ps1 -Drives C:,D: -WarnPercent 20 -CritGb 10
#>
[CmdletBinding()]
param(
    [string[]]$Drives = @(),
    [double]$WarnPercent = 15,
    [double]$WarnGb = 15,
    [double]$CritPercent = 8,
    [double]$CritGb = 8,
    [switch]$Json
)

$ErrorActionPreference = 'Stop'

function Get-DriveLetterFromPath {
    param([string]$Path)
    if ($Path -match '^[A-Za-z]:') {
        return $Matches[0].Substring(0, 1).ToUpperInvariant()
    }
    return $null
}

function Resolve-Targets {
    $raw = @()
    if ($Drives -and $Drives.Count -gt 0) {
        $raw = $Drives
    }
    else {
        $envDisks = [Environment]::GetEnvironmentVariable('SGP_HEALTH_DISKS')
        if (-not [string]::IsNullOrWhiteSpace($envDisks)) {
            $raw = $envDisks.Split(',')
        }
        else {
            $repoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
            $letter = Get-DriveLetterFromPath $repoRoot
            if ($letter) { $raw += ($letter + ':') }
            if ($letter -ne 'C') { $raw += 'C:' }
        }
    }

    return $raw |
        ForEach-Object { $_ -split ',' } |
        ForEach-Object { $_.Trim().TrimEnd('\') } |
        Where-Object { $_ } |
        Select-Object -Unique
}

function Get-DiskStats {
    param([string]$Target)

    $letter = Get-DriveLetterFromPath $Target
    if (-not $letter) {
        throw "Alvo inválido: $Target (use letras como C: ou D:)"
    }

    $drive = Get-PSDrive -Name $letter -PSProvider FileSystem -ErrorAction SilentlyContinue
    if (-not $drive) {
        throw "Unidade ${letter}: não encontrada."
    }

    $free = [double]$drive.Free
    $used = [double]$drive.Used
    $total = $free + $used
    if ($total -le 0) {
        throw "Não foi possível calcular o tamanho da unidade ${letter}:"
    }

    return [pscustomobject]@{
        path         = ($letter + ':')
        free_bytes   = [int64]$free
        total_bytes  = [int64]$total
        free_gb      = [math]::Round($free / 1GB, 2)
        total_gb     = [math]::Round($total / 1GB, 2)
        free_percent = [math]::Round(100.0 * $free / $total, 1)
    }
}

$targets = @(Resolve-Targets)
$results = @()
$exitCode = 0

foreach ($target in $targets) {
    try {
        $stats = Get-DiskStats -Target $target
        $status = 'ok'
        $code = 0
        $message = ("{0} - {1} GB livres ({2}% de {3} GB)." -f $stats.path, $stats.free_gb, $stats.free_percent, $stats.total_gb)

        if ($stats.free_percent -lt $CritPercent -or $stats.free_gb -lt $CritGb) {
            $status = 'critical'
            $code = 2
            $message = "[CRITICO] $message"
        }
        elseif ($stats.free_percent -lt $WarnPercent -or $stats.free_gb -lt $WarnGb) {
            $status = 'warning'
            $code = 1
            $message = "[AVISO] $message"
        }

        $results += [pscustomobject]@{
            path         = $stats.path
            ok           = ($code -eq 0)
            status       = $status
            message      = $message
            free_gb      = $stats.free_gb
            total_gb     = $stats.total_gb
            free_percent = $stats.free_percent
        }
        if ($code -gt $exitCode) { $exitCode = $code }
    }
    catch {
        $results += [pscustomobject]@{
            path    = $target
            ok      = $false
            status  = 'critical'
            message = "[CRITICO] $($_.Exception.Message)"
        }
        $exitCode = 2
    }
}

if ($Json) {
    $payload = [pscustomobject]@{
        status    = $(switch ($exitCode) { 0 { 'ok' } 1 { 'warning' } default { 'critical' } })
        exit_code = $exitCode
        disks     = $results
        thresholds = @{
            warning  = @{ free_percent = $WarnPercent; free_gb = $WarnGb }
            critical = @{ free_percent = $CritPercent; free_gb = $CritGb }
        }
    }
    $payload | ConvertTo-Json -Depth 6
}
else {
    foreach ($item in $results) {
        Write-Host $item.message
    }
}

exit $exitCode
