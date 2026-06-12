param(
  [string]$Root = "AzukiSim",
  [string]$Set,
  [string]$CardName,
  [int]$MaxCards = 0,
  [string]$Model,
  [string]$Sandbox = "workspace-write",
  [string]$Approval = "never",
  [string]$CodexPath,
  [string]$OutputDir,
  [switch]$DryRun
)

$scriptPath = Join-Path $PSScriptRoot "codex-implement-unimplemented.mjs"
$args = @($scriptPath, "--root", $Root, "--sandbox", $Sandbox, "--approval", $Approval)

if ($Set) {
  $args += @("--set", $Set)
}

if ($CardName) {
  $args += @("--card-name", $CardName)
}

if ($MaxCards -gt 0) {
  $args += @("--max-cards", $MaxCards)
}

if ($Model) {
  $args += @("--model", $Model)
}

if ($CodexPath) {
  $args += @("--codex-path", $CodexPath)
}

if ($OutputDir) {
  $args += @("--output-dir", $OutputDir)
}

if ($DryRun) {
  $args += "--dry-run"
}

& node @args
exit $LASTEXITCODE
