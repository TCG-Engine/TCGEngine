<?php
/**
 * Shared semantic-coverage-contract helpers for GrandArchiveSim fixture tooling.
 *
 * DevTools/lint-fixture-coverage.php, DevTools/audit-ga-semantic-coverage.php, and
 * DevTools/build-ga-semantic-backlog.php each need to resolve a fixture's
 * `meta.json` -> testedCards / semanticCoverage contract the same way; this file
 * is the single place that logic lives so the three tools can't drift out of
 * sync on what "tested cards" or "complete contract" mean.
 */

function GaSemanticContract($meta) {
    $contract = is_array($meta) ? ($meta['semanticCoverage'] ?? null) : null;
    return is_array($contract) ? $contract : null;
}

// Prefers the new semanticCoverage.testedCards field over the legacy top-level
// meta.json.testedCards field, falling back to the legacy field when the new
// one is absent (an author may not have migrated yet).
function GaResolveTestedCards($meta) {
    $contract = GaSemanticContract($meta);
    if (is_array($contract) && is_array($contract['testedCards'] ?? null) && !empty($contract['testedCards'])) {
        return $contract['testedCards'];
    }
    return (is_array($meta) && is_array($meta['testedCards'] ?? null)) ? $meta['testedCards'] : [];
}

function GaSemanticAssertions($assertions) {
    return array_values(array_filter((array)$assertions, fn($a) => is_array($a) && !empty($a['semantic'])));
}

// A fixture's semanticCoverage contract is complete only when it names the
// tested cards, the mechanics under test, the printed rule clauses being
// proved, and has at least one assertion marked as the proof.
function GaSemanticContractIsComplete($meta, $assertions) {
    $contract = GaSemanticContract($meta);
    if ($contract === null) return false;

    $testedCards = $contract['testedCards'] ?? GaResolveTestedCards($meta);
    $mechanics = $contract['mechanics'] ?? [];
    $clauses = $contract['rulesClauses'] ?? [];

    return is_array($testedCards) && !empty($testedCards)
        && is_array($mechanics) && !empty($mechanics)
        && is_array($clauses) && !empty($clauses)
        && !empty(GaSemanticAssertions($assertions));
}
