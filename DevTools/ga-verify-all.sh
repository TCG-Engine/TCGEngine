#!/usr/bin/env bash
# Guard rails for the GrandArchiveSim fixtures: run before a PR / in CI. Exit status is non-zero when any stage fails (every stage still runs).
#   DevTools/ga-verify-all.sh          suite, GABot, strict semantic audit, truncation + inline-definition audit, coverage baseline
#   DevTools/ga-verify-all.sh --full   also regenerates every scenario and compares it with HEAD (slow)
# Run `php zzGameCodeGenerator.php rootName=GrandArchiveSim` first on a fresh checkout (the generated engine files are gitignored).
set -u
cd "$(dirname "$0")/.."
fail=0
stage() {
  local label="$1"; shift
  echo; echo "=== $label"
  "$@" || { echo "!! FAILED: $label"; fail=1; }
}
stage "Integration suite" php DevTools/RunIntegrationTests.php --root=GrandArchiveSim
stage "Phase C payment and identity boundaries" php DevTools/test-ga-phase-c-boundaries.php
stage "Phase D material departure boundaries" php DevTools/test-ga-phase-d-boundaries.php
stage "Phase D element membership batch" php DevTools/test-ga-phase-d-element-batch.php
stage "Phase D combat reader batch" php DevTools/test-ga-phase-d-combat-batch.php
stage "Phase E wind batch" php DevTools/test-ga-phase-e-wind.php
stage "Phase E norm batch" php DevTools/test-ga-phase-e-norm.php
stage "Phase E crux batch" php DevTools/test-ga-phase-e-crux.php
stage "Phase E tera batch" php DevTools/test-ga-phase-e-tera.php
stage "GABot self-play" php -d xdebug.mode=off DevTools/GABotSelfPlayTest.php
stage "Semantic contracts are complete (strict)" php DevTools/audit-ga-semantic-coverage.php --strict
stage "No truncated fixtures, no inline fixture definitions" php DevTools/create-prd-ability-fixtures.php --audit-actions
stage "Cards without a semantic fixture match the documented baseline" php DevTools/check-ga-coverage-baseline.php
if [ "${1:-}" = "--full" ]; then
  stage "Scenarios regenerate to what is committed" php DevTools/verify-ga-scenarios.php
fi
echo
if [ "$fail" -eq 0 ]; then echo "All GrandArchiveSim guard rails passed."; else echo "Some GrandArchiveSim guard rails FAILED."; fi
exit "$fail"
