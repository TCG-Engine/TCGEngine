<?php
// SEC_010
// Cost 4 - Dedra Meero - Not Wasting Time - [Aggression,Villainy] - Power 2 - HP 5
// Text: Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may deal 2 damage to it. If they don't, draw a card.
// DeployText: While you have more cards in hand than an opponent, this unit gains Raid 2. (She gets +2/+0 while attacking.)
// Epic Action: If you control 4 or more resources, deploy this leader.

// ── SEC_010 Dedra Meero ───────────────────────────────────────────────────────
// Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may deal 2 damage to it. If they
// don't, draw a card. (Cross-player YESNO for the opponent; the caster draws on a decline.)
$leaderAbilities["SEC_010"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) { SWUAfterAction($player); return; } // gate should prevent
    SWUQueueChooseTarget($player, $enemies, "Choose_an_enemy_unit", "SEC_010#0");
};

// Step 0 (caster frame): the chosen enemy unit → hand the opponent a YESNO from a CUSTOM (safe for the
// cross-player $playerID handoff).
$customDQHandlers["SEC_010#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $uid = intval($o->UniqueID ?? 0);
    // "ITS CONTROLLER" — determined by the unit the caster already chose, never a separate choice.
    // Read it off the chosen mzID (p{n}… above two seats; "their…" at two, where SWUMzOwner falls
    // through to the single opponent and is byte-identical).
    // ⚠ Adding a picker here would ask a question the card does not ask, and would let the caster route
    // the YES/NO to a player who does not control the unit. OtherPlayer() sent it to one fixed seat, so
    // above two seats the wrong player was asked to damage a unit that is not theirs.
    $opp = SWUMzOwner($mz, intval($player));
    if ($opp <= 0 || $opp === intval($player)) { SWUAfterAction(intval($player)); return; }
    $playerID = $opp;   // the unit's controller owns the next decision
    DecisionQueueController::AddDecision($opp, 'YESNO', '-', 1, tooltip: "Deal_2_damage_to_your_own_unit?");
    DecisionQueueController::AddDecision($opp, 'CUSTOM', "SEC_010#1|" . intval($player) . "|{$uid}", 1);
};

// Step 1 (opponent frame): YES → opponent deals 2 to the unit; NO → the caster draws. Caster closes.
$customDQHandlers["SEC_010#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $uid    = intval($parts[1] ?? 0);
    if (($lastDecision ?? '') === 'YES') {
        $playerID = intval($player);                 // opponent's frame
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    } else {
        DoDrawCard($caster, 1);                       // "If they don't, draw a card." (the caster draws)
    }
    SWUAfterAction($caster);
};
