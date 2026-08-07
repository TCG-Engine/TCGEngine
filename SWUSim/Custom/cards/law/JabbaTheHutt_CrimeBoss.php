<?php
// LAW_015
// Cost 6 - Jabba the Hutt - Crime Boss - [Cunning,Villainy] - Power 3 - HP 9
// Text: Action [1 resource, Exhaust, return a friendly Underworld unit to its owner's hand]: Create a Credit token.
// DeployText: Action: Play an Underworld unit from your hand. If you defeated a Credit while paying its cost, that unit gains Ambush for this phase.
// Epic Action: If you control 6 or more resources, deploy this leader.

$unitActionCostKind["LAW_015"] = 'none';

// no exhaust, no base resource cost — the played unit pays its own cost
$unitAbilities["LAW_015"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = _SWULaw015PlayableUnderworldUnits(intval($player));
    if (empty($targets)) { SWUAfterAction(intval($player)); return; } // defensive (affordability requires one)
    SWUQueueChooseTarget(intval($player), $targets, "Play_an_Underworld_unit_from_your_hand", "LAW_015#0");
};

$customDQHandlers["LAW_015#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    // Mark this as a Jabba play: if a Credit is defeated paying its cost, CREDIT_PAY sets SWU_JABBA015_AMBUSH
    // and ActivateCard grants Ambush this phase at entry (before entry triggers, so it can attack). The
    // play's own terminal (FINISH_PLAY_CARD → SWUAfterAction) ends Jabba's action — do NOT close it here.
    AddGlobalEffects(intval($player), 'SWU_JABBA015_PENDING');
    SWUBeginPlayCard(intval($player), $lastDecision);
};

$leaderAbilities["LAW_015"] = function(int $player): void {
    global $playerID; $playerID = $player;
    // The [1 resource] cost is settled before this runs: SWULeaderAction pays every leader Action's
    // resource cost through the alt-pay funnel, so a Credit token may pay it (CR 3.13). This used to be
    // Jabba's own bespoke funnel call — now it is the engine-wide default for all leader Actions.
    _SWULaw015AfterPay($player, true);
};

$leaderActionResourceCosts["LAW_015"] = 1;

$customDQHandlers["LAW_015#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) SWUBounceUnit(intval($player), $lastDecision); // return-to-hand cost
    }
    SWUCreateCreditToken(intval($player), 1);
    SWUAfterAction(intval($player));
};
