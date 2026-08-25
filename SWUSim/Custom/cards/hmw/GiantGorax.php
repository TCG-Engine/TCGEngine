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

// Everything $opp CONTROLS that this card may hit, expressed in the CASTER's frame: both arenas (the
// clause is arena-unqualified) plus that seat's base. Filtered by CONTROLLER, not by zone name — in Twin
// Suns "their…" fans out across every opponent, and the clause is "a unit or base THEY control", meaning
// the one opponent who just made this choice, not all of them.
if (!function_exists('_SWUHmw188TargetsOf')) {
    function _SWUHmw188TargetsOf(int $caster, int $opp): array {
        global $playerID;
        $playerID = $caster;
        $out = [];
        foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o === null || !empty($o->removed)) continue;
                if (intval($o->Controller ?? 0) !== $opp) continue;
                $out[] = $mz;
            }
        }
        foreach (SWUAllBaseMzIDs($caster, 'their') as $bmz) {
            if (SWUMzOwner($bmz, $caster) === $opp) $out[] = $bmz;   // always exactly one — never "no target"
        }
        $playerID = $caster;
        return $out;
    }
}

// Step 0 — hand the mode choice to EACH opponent. Every seat chooses independently and resolves on its
// own queue, so one opponent taking the damage does not stop another taking the discard.
// ⚠ Was OtherPlayer($caster) — one opponent, so at four seats the other two were never asked and the
//   card did nothing to them.
// Labels are single-token (the DecisionQueue param is space-delimited); the prompt lives in the tooltip.
$customDQHandlers["HMW_188#0"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    foreach (OpponentsOf($caster) as $opp) {
        $playerID = $opp;
        DecisionQueueController::AddDecision($opp, "OPTIONCHOOSE", "Deal3&DiscardAndDefeat", 1,
            tooltip: "Giant_Gorax:_choose_one");
        DecisionQueueController::AddDecision($opp, "CUSTOM", "HMW_188#1|{$caster}", 1);
    }
    $playerID = $caster;
};

// Step 1 — resolve the chosen mode. $player is the OPPONENT (the chooser); the caster rides the param.
$customDQHandlers["HMW_188#1"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $opp    = intval($player);
    $caster = intval($parts[0] ?? 0);   // the caster ALWAYS rides the param — never guess a seat
    if ($caster <= 0) return;

    if ($lastDecision === 'DiscardAndDefeat') {
        // "They discard a card from their hand and defeat a resource they control."
        // Joined by AND, not "if you do" — an empty hand must not swallow the resource defeat, and no
        // resources must not swallow the discard. Each half simply does as much as it can.
        // The CHOOSER discards — targeted explicitly. Untargeted, this hit OtherPlayer($caster), which is
        // this seat only in a 2-player game; at four seats seat 3's choice made seat 2 discard.
        SWUDiscardCards($caster, 1, $opp);
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
    // Scoped to the seat that CHOSE this mode — see _SWUHmw188TargetsOf. Their base is always in the
    // pool, so this mode never has "no valid target".
    $targets = _SWUHmw188TargetsOf($caster, $opp);
    if (empty($targets)) return;
    SWUQueueChooseTarget($caster, $targets, "Deal_3_damage_to_a_unit_or_base_they_control",
        "DEAL_TARGET|3");
};
