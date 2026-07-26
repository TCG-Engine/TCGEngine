<?php
// Dev-time verification: snapshot the set of (ability-array, key) registrations,
// and diff two snapshots as MULTISETS. A dropped registration shows as `missing`;
// a double-registration shows as `added` — both are split failures.

// The registration arrays that card files populate. Keep in sync with the
// `global` declarations at the top of CardDQHandlers.php.
function splitter_registration_arrays(): array {
    return [
        'whenPlayedUsingSmuggleAbilities', 'whenPlayedAsUpgradeAbilities',
        'whenPlayedAbilities', 'whenDefeatedAbilities',
        'onAttackAbilities', 'onDefenseAbilities', 'onAttackEndAbilities',
        'onAttackEndFromUpgradeAbilities', 'onAttackedFromUpgradeAbilities',
        'onDefenseFromUpgradeAbilities', 'onAttachedAbilities',
        'unitAbilities', 'unitActionResourceCosts', 'unitActionCostKind',
        'customDQHandlers',
    ];
}

// Snapshot "<arrayName>::<key>" for every registration currently in scope.
// Call AFTER the engine (or a card-file loader) has populated the globals.
function splitter_snapshot_keys(): array {
    $out = [];
    foreach (splitter_registration_arrays() as $name) {
        if (!isset($GLOBALS[$name]) || !is_array($GLOBALS[$name])) continue;
        foreach (array_keys($GLOBALS[$name]) as $k) $out[] = "$name::$k";
    }
    sort($out);
    return $out;
}

// Multiset diff. `missing` = present in $before but under-represented in $after;
// `added` = present in $after but not accounted for in $before (incl. duplicates).
function splitter_diff_keys(array $before, array $after): array {
    $count = function(array $a): array {
        $c = [];
        foreach ($a as $k) $c[$k] = ($c[$k] ?? 0) + 1;
        return $c;
    };
    $cb = $count($before);
    $ca = $count($after);
    $missing = []; $added = [];
    foreach ($cb as $k => $n) {
        $delta = $n - ($ca[$k] ?? 0);
        for ($i = 0; $i < $delta; $i++) $missing[] = $k;
    }
    foreach ($ca as $k => $n) {
        $delta = $n - ($cb[$k] ?? 0);
        for ($i = 0; $i < $delta; $i++) $added[] = $k;
    }
    sort($missing); sort($added);
    return ['missing'=>$missing, 'added'=>$added];
}
