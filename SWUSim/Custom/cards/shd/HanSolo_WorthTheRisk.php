<?php
// SHD_013
// Cost 5 - Han Solo - Worth the Risk - [Heroism,Aggression] - Power 3 - HP 6
// Text: Action [Exhaust]: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it.
// DeployText: Action: Play a unit from your hand. It costs 1 resource less. Deal 2 damage to it.
// Epic Action: If you control 5 or more resources, deploy this leader.

$customDQHandlers["SHD_013#play"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $handMz = $lastDecision ?? '';
    if ($handMz === '' || !str_contains($handMz, '-')) { SWUAfterAction(intval($player)); return; }
    $gPlayGrantTurnEffect = 'SHD_013';
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, false, 1);   // −1 discount; inner after-action neutralised
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('SHD_013', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    if ($newMz !== null) SWUDealDamageToUnit($newMz, 2, intval($player));
    SWUAfterAction(intval($player));
};

$leaderAbilities["SHD_013"] = function(int $player): void {
    if (!HanSoloWorththeRiskOffer($player)) SWUAfterAction($player);
};

$unitActionCostKind["SHD_013"] = 'exhaust';

$unitAbilities["SHD_013"] = function($player, $mzID) {
    if (!HanSoloWorththeRiskOffer(intval($player))) SWUAfterAction(intval($player));
};

// ── SHD_013 Han Solo ───────────────────────────────────────────────────────────
// Front Action [Exhaust] / deployed Action: Play a unit from your hand. It costs 1 resource less. Deal 2
// damage to it. (Play-then-act-on-the-played-unit via the SEC_018 findable-marker pattern.)
function HanSoloWorththeRiskOffer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready = SWUTotalPaymentCapacity($player); // Credits/Droids can pay a play cost (CR 3.13)
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (max(0, SWUComputePlayCost($player, $o) - 1) > $ready) continue;
        $units[] = $mz;
    }
    if (empty($units)) return false;
    SWUQueueChooseTarget($player, $units, "Play_a_unit_from_hand_(costs_1_less;_deal_2_to_it)", "SHD_013#play");
    return true;
}
