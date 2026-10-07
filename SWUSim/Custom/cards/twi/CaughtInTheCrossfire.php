<?php
// TWI_176
// Cost 6 - Caught in the Crossfire - [Aggression]
// Text: Choose 2 enemy units in the same arena. Each of those units deals damage equal to its power to the other.

// TWI_176 Caught in the Crossfire — first enemy chosen; offer a second enemy in the SAME arena.
$customDQHandlers["TWI_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $first = GetZoneObject($lastDecision);
    if (SWUObjGone($first)) return;
    $fuid = intval($first->UniqueID ?? 0);
    $zone = (strpos((string)$lastDecision, 'SpaceArena') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $second = [];
    foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $fuid) $second[] = $mz;
    }
    if (empty($second)) return; // no second unit in the same arena
    // Both units deal their CURRENT power to each other, simultaneously. Naming the first unit and its
    // power is the only way the player can judge which second unit actually trades — "choose the second
    // enemy unit" told them nothing about the exchange they were setting up.
    $fName  = str_replace(' ', '_', SWUObjectTitle($first));
    $fPower = intval(ObjectCurrentPower($first));
    SWUQueueChooseTarget(intval($player), $second,
        "Choose_a_second_enemy_unit_-_it_and_{$fName}_deal_their_power_to_each_other_({$fName}_deals_{$fPower})",
        "TWI_176#1|" . $fuid);
};

$customDQHandlers["TWI_176#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $fmz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $fo = $fmz !== null ? GetZoneObject($fmz) : null;
    $so = GetZoneObject($lastDecision);
    if ($fo === null || $so === null || !empty($fo->removed) || !empty($so->removed)) return;
    $fp = intval(ObjectCurrentPower($fo)); // each deals its power to the other (simultaneous)
    $sp = intval(ObjectCurrentPower($so));
    $assign = [];
    // Each hit carries its OWN dealer as the source (3rd field — see SWUDealSplitDamage): "each of those units deals
    // damage equal to its power to the other" names two different dealers (CR 18.2a), so ASH_196 Gorian's
    // unpreventable applies to an Underworld dealer's hit only (2026-10-07).
    if ($fp > 0) $assign[] = "{$lastDecision}:{$fp}:" . _SWUEncodeDamageSource($fmz);   // first deals to second
    if ($sp > 0) $assign[] = "{$fmz}:{$sp}:" . _SWUEncodeDamageSource($lastDecision);   // second deals to first
    if (empty($assign)) return;
    // HMW_185 Ty Yorrick — the divided-damage funnel never offers him, so ask here. Owner ruling 2026-10-07: ONE question;
    // accepted, EACH hit is +1. Nothing changes the board before the answer, so the hits' mzIDs stay valid.
    $ty = _SWUHmw185Decider(intval($player));
    if ($ty > 0) { _SWUHmw185Defer($ty, "TWI_176#2|" . intval($player) . "|" . implode(',', $assign)); return; }
    SWUDealSplitDamage(intval($player), implode(',', $assign));
};

// Resume after Ty's question. parts: caster seat | hits ("mz:amount:sourceToken", ,-joined).
$customDQHandlers["TWI_176#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    if ($caster <= 0) return;
    $plus = _SWUHmw185Accepted($lastDecision) ? 1 : 0;
    $hits = [];
    foreach (array_filter(explode(',', (string)($parts[1] ?? ''))) as $h) {
        $f = explode(':', $h);
        if (count($f) < 2) continue;
        $f[1] = intval($f[1]) + $plus;
        $hits[] = implode(':', $f);
    }
    $playerID = $caster;
    if (!empty($hits)) SWUDealSplitDamage($caster, implode(',', $hits));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_176:0"] = function($player, $mzID = '') {
// Caught in the Crossfire — "Choose 2 enemy units in the same arena. Each of
                          // those units deals damage equal to its power to the other."
            global $playerID; $playerID = intval($player);
            $enemies = array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            if (count($enemies) < 2) return;
            SWUQueueChooseTarget(intval($player), $enemies, "Choose_the_first_enemy_unit", "TWI_176#0");
            return;
};
