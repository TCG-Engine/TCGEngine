<?php
// SHD_016
// Cost 5 - Fennec Shand - Honoring the Deal - [Cunning,Heroism] - Power 4 - HP 4
// Text: Action [1 resource, Exhaust]: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for this phase. (After you play the unit, it may ready and attack an enemy unit.)
// DeployText: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / Action: Play a unit that costs 4 or less from your hand (paying its cost). Give it Ambush for this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["SHD_016#play"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SEC_007';   // reuse the "played unit gains Ambush this phase" marker
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);   // pays the unit's cost
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    SWUAfterAction(intval($player));
};

$leaderAbilities["SHD_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!FennecShandHonoringtheDealOffer($player)) SWUAfterAction($player);
};

$leaderActionResourceCosts["SHD_016"] = 1;

// The DEPLOYED Action is a bare "Action:" — no [Exhaust] and no resource cost (the front side's
// [1 resource, Exhaust] belongs to the undeployed side only, and is charged via
// $leaderActionResourceCosts above). Marking this 'exhaust' made the deployed side self-exhaust and so
// usable only once per turn, where it is meant to be repeatable.
$unitActionCostKind["SHD_016"] = 'none';

$unitAbilities["SHD_016"] = function($player, $mzID) {
    if (!FennecShandHonoringtheDealOffer(intval($player))) SWUAfterAction(intval($player));
};

// ── SHD_016 Fennec Shand ───────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust] / deployed Action: Play a unit that costs 4 or less from your hand
// (paying its cost). Give it Ambush for this phase. Deployed also: Saboteur (keyword, auto).
function FennecShandHonoringtheDealOffer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready = SWUTotalPaymentCapacity($player);
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (intval(CardCost($o->CardID ?? '')) > 4) continue;
        if (SWUComputePlayCost($player, $o) > $ready) continue;
        $units[] = $mz;
    }
    if (empty($units)) return false;
    // DECLINABLE (user ruling 2026-08-15): "play a unit from your HAND" can always be declined, because
    // the hand is a HIDDEN zone — a player is never forced to reveal that they held a playable unit.
    // Same reasoning for SHD_129 Timely Intervention and SOR_022 Energy Conversion Lab. The activation
    // cost ([1 resource, Exhaust] on the front side) is still paid either way.
    SWUQueueMayChooseTarget($player, $units, "Play_a_unit_costing_4_or_less_(it_gains_Ambush)?",
        "Play_a_unit_costing_4_or_less_(it_gains_Ambush)", "SHD_016#play");
    return true;
}
