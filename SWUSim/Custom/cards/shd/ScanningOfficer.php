<?php
// SHD_114
// Cost 2 - Scanning Officer - [Command] - Power 2 - HP 3
// Text: When Played: Reveal 3 enemy resources. Defeat each resource with the Smuggle keyword revealed this way. For each resource defeated this way, its controller puts the top card of their deck into play as a resource.

// SHD_114 Scanning Officer — When Played: reveal 3 enemy resources; defeat each REVEALED resource that
// has the Smuggle keyword; for each one defeated, its controller puts the top card of their deck into play
// as a resource. Reuses the generic SWURevealResources shuffler (no card-specific weighting needed — it
// just reveals 3 Ready-first). Unlike SEC_242 there's no player pick (every revealed Smuggle is defeated)
// and the replacement enters EXHAUSTED ("into play as a resource", not "ready it"). Because the reveal is
// Ready-first, a Smuggle resource kept EXHAUSTED behind ≥3 ready ones is never revealed → protected.
// ⚠ "3 ENEMY resources" names no single seat. At 3+ seats the caster must choose WHOSE — the Twin Suns
// sweep premise, and the same shape as SHD_184 Bazine Netal ("look at AN OPPONENT's hand"), which is the
// canonical analogue this follows. SWUQueueChooseOpponent auto-resolves to an invisible PASSPARAMETER at
// one eligible opponent, so Premier is byte-identical.
// ⚠ FILTER to opponents that actually HAVE a resource: with none there is nothing to reveal, nothing to
// defeat and no replacement — a choice among nothing (Bazine's precedent for an empty hand).
$whenPlayedAbilities["SHD_114:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $eligible = [];
    foreach (OpponentsOf(intval($player)) as $o) {
        foreach (GetResources($o) as $r) { if (empty($r->removed)) { $eligible[] = $o; break; } }
    }
    if (empty($eligible)) return;
    SWUQueueChooseOpponent(intval($player), 'SHD_114#0|' . intval($player),
        "Choose_an_opponent_whose_resources_to_scan", $eligible);
};

$customDQHandlers["SHD_114#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? $player);
    $playerID = $caster;
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    $player = $caster;
    $revealed = SWURevealResources(intval($player), $opp, 3);
    // The revealed resources carrying Smuggle, by DESCENDING index so a defeat (which compacts the zone)
    // never shifts a lower-index one we still have to process.
    $smuggle = [];
    foreach ($revealed as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (HasKeyword_Smuggle($o)) $smuggle[] = $mz;
    }
    usort($smuggle, fn($a, $b) => intval(substr(strrchr($b, '-'), 1)) <=> intval(substr(strrchr($a, '-'), 1)));
    $defeated = 0;
    foreach ($smuggle as $mz) {
        $oppMz = preg_replace('/^their/', 'my', (string)$mz);   // owner's frame → lands in their discard
        $playerID = $opp;
        if (SWUDefeatResource($opp, $oppMz)) $defeated++;
        $playerID = intval($player);
    }
    // Each defeated resource: its controller (the opponent) replaces it from the top of their deck, EXHAUSTED.
    // MZMove only marks the moved deck card `removed` (no compaction), so re-scan for the first NON-removed
    // card each iteration rather than blindly taking myDeck-0 (which would stay the spent card).
    $playerID = $opp;
    for ($i = 0; $i < $defeated; $i++) {
        $top = null;
        foreach (ZoneSearch("myDeck", null) as $dz) {
            $o = GetZoneObject($dz);
            if ($o !== null && empty($o->removed)) { $top = $dz; break; }
        }
        if ($top === null) break;   // deck empty → no replacement
        SWURampResourceExhausted($opp, $top);
    }
    $playerID = intval($player);
};
