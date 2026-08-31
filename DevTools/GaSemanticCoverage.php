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

function GaSemanticAssertions($assertions, $actions = []) {
    $assertionEvidence = array_values(array_filter((array)$assertions, fn($a) => is_array($a) && !empty($a['semantic'])));
    // A rejected illegal action is semantic evidence too: its expected
    // failure proves a targeting, cost, or condition restriction even when
    // there is no post-action state to assert.
    $rejectionEvidence = array_values(array_filter((array)$actions, fn($a) => is_array($a) && !empty($a['expectFailure']) && !empty($a['semantic'])));
    return array_merge($assertionEvidence, $rejectionEvidence);
}

// A positive contract must prove an observable game-state result. A
// queue-empty check only proves that the runner drained decisions; it does not
// demonstrate that the card's printed effect resolved correctly. Negative
// contracts are handled separately through explicit expected rejections.
function GaHasObservableSemanticEvidence($assertions) {
    foreach ((array)$assertions as $assertion) {
        if (!is_array($assertion) || empty($assertion['semantic'])) continue;
        $type = strval($assertion['type'] ?? '');
        if ($type === '' || $type === 'decision_queue_empty') continue;
        if (in_array($type, [
            'card_property_equals',
            'zone_count',
            'zone_contains_card',
            'zone_card_ids',
            'global_effect_present',
            'global_effect_count',
            'flash_message_contains',
        ], true)) return true;
    }
    return false;
}

// A fixture's semanticCoverage contract is complete only when it names the
// tested cards, the mechanics under test, the printed rule clauses being
// proved, and has at least one assertion marked as the proof.
function GaSemanticContractIsComplete($meta, $assertions, $actions = []) {
    $contract = GaSemanticContract($meta);
    if ($contract === null) return false;

    $testedCards = $contract['testedCards'] ?? GaResolveTestedCards($meta);
    $mechanics = $contract['mechanics'] ?? [];
    $clauses = $contract['rulesClauses'] ?? [];

    return is_array($testedCards) && !empty($testedCards)
        && is_array($mechanics) && !empty($mechanics)
        && is_array($clauses) && !empty($clauses)
        && !empty(GaSemanticAssertions($assertions, $actions))
        && (GaHasObservableSemanticEvidence($assertions)
            // A negative contract is meaningful when it explicitly proves
            // that an illegal action is rejected.
            || !empty(array_filter((array)$actions, fn($a) => is_array($a)
                && !empty($a['expectFailure']) && !empty($a['semantic']))));
}
