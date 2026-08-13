<?php
// HMW_188
// Cost 7 - Giant Gorax - [Aggression] - Unit (Ground) 7/7 - Traits: Creature - Legendary - non-unique
// Text: Overwhelm
// On Attack/When Defeated: If you control an Endor base, each opponent chooses one:
//   <bullet>You deal 3 damage to a unit or base they control.
//   They discard a card from their hand and defeat a resource they control.</bullet>
//
// Overwhelm is auto-wired from $Overwhelm_Cards — nothing to do here.
//
// Both trigger windows share ONE entry closure; the Endor-base gate is evaluated against the RESOLVER
// (a control change moves the whole ability — gate, chooser and target pool — with the controller).
//
// ⚠ The closure only queues an intermediate CUSTOM. DispatchTrigger / OnAttackTrigger restore $playerID
// right after the closure returns, so any decision carrying relative mzIDs (and every cross-player
// decision) must be queued from a continuation instead — the LAW_080 / SOR_040 shape. It is also what
// makes the Deal3 pick safe as a MANDATORY MZCHOOSE inside an On Attack (a mandatory choose queued
// directly in an OnAttack closure auto-resolves to nothing).
$onAttackAbilities["HMW_188:0"] = $whenDefeatedAbilities["HMW_188:0"] = function ($player, $mzID = '') {
    $p = intval($player);
    if (!_SWUControlsBaseWithTrait($p, 'Endor')) return;
    DecisionQueueController::AddDecision($p, "CUSTOM", "HMW_188#0", 1);
};

// Step 0 — hand the mode choice to the opponent. Two-player: "each opponent" is the single opponent.
// Labels are single-token (the DecisionQueue param is space-delimited); the prompt lives in the tooltip.
$customDQHandlers["HMW_188#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $opp    = OtherPlayer($caster);
    $playerID = $opp;
    DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "Deal3&DiscardAndDefeat", 1,
        tooltip: "Giant_Gorax:_choose_one");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "HMW_188#1|{$caster}", 1);
};

// Step 1 — resolve the chosen mode. $player is the OPPONENT (the chooser); the caster rides the param.
$customDQHandlers["HMW_188#1"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $opp    = intval($player);
    $caster = intval($parts[0] ?? OtherPlayer($opp));

    if ($lastDecision === 'DiscardAndDefeat') {
        // "They discard a card from their hand and defeat a resource they control."
        // Joined by AND, not "if you do" — an empty hand must not swallow the resource defeat, and no
        // resources must not swallow the discard. Each half simply does as much as it can.
        SWUDiscardCards($caster, 1);   // makes OtherPlayer($caster) == $opp discard 1
        $playerID = $opp;              // left set: the MZCHOOSE below carries a relative zone name
        // Mirrors SOR_017 Han Solo's pending resource defeat — a bare 'myResources' zone param
        // auto-PASSes harmlessly when they control none.
        DecisionQueueController::AddDecision($opp, "MZCHOOSE", "myResources", 1,
            "Choose_a_resource_to_defeat_(Giant_Gorax)");
        DecisionQueueController::AddDecision($opp, "CUSTOM", "HAN_DEFEAT_RESOURCE", 1);
        return;
    }

    // "You deal 3 damage to a unit or base they control." — the CASTER picks, among everything the
    // opponent CONTROLS: both arenas (the clause is arena-unqualified) plus their base. Built from the
    // caster's frame, so the pool is controller-scoped and can never include the caster's own board.
    $playerID = $caster;
    $targets  = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    $targets[] = 'theirBase-0';   // always present, so this mode never has "no valid target"
    SWUQueueChooseTarget($caster, $targets, "Deal_3_damage_to_a_unit_or_base_they_control",
        "DEAL_TARGET|3");
};
