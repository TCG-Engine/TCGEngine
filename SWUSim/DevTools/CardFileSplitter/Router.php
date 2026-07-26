<?php
// Dev-time router: decides whether a scanned statement moves to a per-card file
// (and which one) or stays in the monolith. Requires CardIDOverride() and
// SWUCardSet() to be loaded by the caller.
//
// Ownership is the CardID embedded in the LHS assignment key, e.g.
//   $onAttackEndAbilities["LOF_038:0"]  →  owner LOF_038
// NOT every CardID mentioned in the body. A registration whose closure references
// other cards by ID in its logic is still a single-owner statement and moves by
// its owner.
//
// LOAD-ORDER SAFETY: card files load AFTER the monoliths, so a statement that
// reads another registration's VALUE at include time (a value-copy like
//   $unitAbilities["TWI_120"] = $unitAbilities["SOR_093"];
// ) must keep its target's definition in the monolith. We therefore (a) LEAVE any
// statement whose LHS key is read-by-value elsewhere ($pinned), and (b) LEAVE
// value-copy readers themselves (RHS is a bare/array-element alias).

// The registration arrays whose (array,key) entries are relocated.
function splitter_registration_array_names(): array {
    return [
        'whenPlayedUsingSmuggleAbilities','whenPlayedAsUpgradeAbilities',
        'whenPlayedAbilities','whenDefeatedAbilities',
        'onAttackAbilities','onDefenseAbilities','onAttackEndAbilities',
        'onAttackEndFromUpgradeAbilities','onAttackedFromUpgradeAbilities',
        'onDefenseFromUpgradeAbilities','onAttachedAbilities',
        'unitAbilities','unitActionResourceCosts','unitActionCostKind',
        'customDQHandlers',
    ];
}

// "arrayName::key" of a statement's LHS assignment target, or null.
function splitter_stmt_key(array $stmt): ?string {
    if (preg_match('/\$([a-zA-Z_]\w*)\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]/', $stmt['lhs'], $m)) {
        return $m[1] . '::' . $m[2];
    }
    return null;
}

// Set (as ['arr::key'=>true]) of registration entries that are READ BY VALUE in
// some statement's body at include time — excluding each statement's own LHS key.
// Moving the DEFINITION of such a key would change load order and break the copy
// (e.g. $unitAbilities["LOF_018"] = $leaderAbilities["LOF_018"];).
//
// Matches ANY $var["<CardID>"] read, not a fixed array-name list — so it can't be
// blindsided by an array we forgot (leaderAbilities was the one that bit us). Over-
// pinning a dictionary read (e.g. $titleData["SOR_x"]) is harmless: no MOVED
// statement has such an LHS key, so nothing extra gets left behind.
function splitter_pinned_keys(array $stmts): array {
    $pinned = [];
    foreach ($stmts as $s) {
        $own = splitter_stmt_key($s);
        if (!preg_match_all('/\$([a-zA-Z_]\w*)\s*\[\s*[\'"]([A-Z0-9]{2,4}_\d+[^\'"]*)[\'"]\s*\]/', $s['text'], $m, PREG_SET_ORDER)) continue;
        foreach ($m as $mm) {
            $k = $mm[1] . '::' . $mm[2];
            if ($k === $own) continue; // its own LHS reference, not a cross-read
            $pinned[$k] = true;
        }
    }
    return $pinned;
}

function splitter_route(array $stmt, string $targetSet, array $pinned = []): array {
    if ($stmt['kind'] !== 'assign') {
        return ['action'=>'leave','baseCardID'=>null,'set'=>null,
                'reason'=> $stmt['kind']==='function' ? 'function def' : 'not an assignment'];
    }

    // Owner = the (single) CardID in the LHS assignment key.
    preg_match_all('/\b[A-Z0-9]{2,4}_\d+\b/', $stmt['lhs'], $lm);
    $ownerIDs = array_values(array_unique($lm[0]));
    if (count($ownerIDs) !== 1) {
        return ['action'=>'leave','baseCardID'=>null,'set'=>null,
                'reason'=> $ownerIDs ? 'multiple cards in LHS: '.implode(',',$ownerIDs) : 'no card key in LHS'];
    }
    $owner = $ownerIDs[0];

    // This definition is read by value elsewhere → moving it changes load order.
    $key = splitter_stmt_key($stmt);
    if ($key !== null && isset($pinned[$key])) {
        return ['action'=>'leave','baseCardID'=>null,'set'=>null,
                'reason'=>'read by value elsewhere ('.$key.')'];
    }

    // Top-level closure captures an outer local → the local lives in the monolith.
    if (!empty($stmt['topLevelUses'])) {
        return ['action'=>'leave','baseCardID'=>null,'set'=>null,
                'reason'=>'captures local: '.implode(',',$stmt['topLevelUses'])];
    }

    // Bare-variable or array-element alias RHS (e.g. `= $sharedThing;` or
    // `= $unitAbilities["SOR_093"];`) — a value-copy reader whose source lives
    // elsewhere; keep it with the original load order.
    if (preg_match('/=\s*(\$[A-Za-z_]\w*(?:\[[^\]]*\])?)\s*;\s*$/', trim($stmt['text']), $am)) {
        return ['action'=>'leave','baseCardID'=>null,'set'=>null,
                'reason'=>'alias of '.$am[1]];
    }

    $base = CardIDOverride($owner);
    $set  = SWUCardSet($base);
    if (strtoupper($set) !== strtoupper($targetSet)) {
        return ['action'=>'leave','baseCardID'=>$base,'set'=>strtolower($set),
                'reason'=>'other set'];
    }
    return ['action'=>'move','baseCardID'=>$base,'set'=>strtolower($set),'reason'=>'single card'];
}
