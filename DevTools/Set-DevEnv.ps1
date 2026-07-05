<#
.SYNOPSIS
    Sets the DEVENV environment variable to "true" for local (non-Docker) development.

.DESCRIPTION
    The app bypasses auth when PHP sees getenv('DEVENV') === 'true'. In Docker this is
    provided by docker-compose.override.yml; when running PHP directly on Windows you
    need the variable in the environment of whatever launches PHP.

    By default this sets the variable persistently at the USER level (survives reboots
    and new shells) AND in the current PowerShell session so you don't have to reopen
    the terminal. PHP's built-in server started from that same shell/user will inherit it.

    NOTE: A user-level variable is inherited by processes YOU launch (e.g. `php -S`).
    If PHP runs under a service (IIS / Apache as a service), restart that service — or
    re-run with -Machine (requires an elevated/admin PowerShell) so the service picks it up.

.PARAMETER Value
    The value to set. Defaults to "true" (the exact string the app checks for).

.PARAMETER Machine
    Set at the machine level instead of the user level. Requires an elevated shell.

.PARAMETER Clear
    Remove the variable instead of setting it (turns dev auth-bypass back off).

.EXAMPLE
    .\Set-DevEnv.ps1
    Sets DEVENV=true for the current user and current session.

.EXAMPLE
    .\Set-DevEnv.ps1 -Clear
    Removes DEVENV so auth is enforced again.
#>
[CmdletBinding()]
param(
    [string]$Value = "true",
    [switch]$Machine,
    [switch]$Clear
)

$scope   = if ($Machine) { [System.EnvironmentVariableTarget]::Machine } else { [System.EnvironmentVariableTarget]::User }
$scopeLabel = if ($Machine) { "machine" } else { "user" }

if ($Clear) {
    [System.Environment]::SetEnvironmentVariable("DEVENV", $null, $scope)
    Remove-Item Env:\DEVENV -ErrorAction SilentlyContinue
    Write-Host "Removed DEVENV ($scopeLabel scope + current session)." -ForegroundColor Yellow
    return
}

# Persist for future shells...
[System.Environment]::SetEnvironmentVariable("DEVENV", $Value, $scope)
# ...and make it active right now without reopening the terminal.
$env:DEVENV = $Value

Write-Host "Set DEVENV=$Value ($scopeLabel scope + current session)." -ForegroundColor Green
Write-Host "Verify with:  `$env:DEVENV" -ForegroundColor DarkGray
