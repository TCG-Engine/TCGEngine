<?php
// TS26_32
// Cost 2 - Reckless Landing - [Aggression,Cunning]
// Text: Play a unit from your hand. It costs 4 resources less. Deal 4 damage to it.

// TS26_32 Reckless Landing — play the chosen hand unit at −4, then deal 4 to it. Runs inside the
// event's resolution, so the event's FINISH_PLAY_CARD owns the after-action (neutralise the inner
// ActivateCard's turn advance via save/restore — mirror SOR_219). The played unit is located by the
// findable TS26_32 marker ($gPlayGrantTurnEffect).
$customDQHandlers["TS26_32#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $handMz = $lastDecision ?? '';
    if ($handMz === '' || !str_contains($handMz, '-')) return;
    $gPlayGrantTurnEffect = 'TS26_32';
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $handMz, false, 4);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('TS26_32', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }
    if ($newMz !== null) SWUDealDamageToUnit($newMz, 4, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_32:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = SWUResourceCount(intval($player), readyOnly: true);
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        if (max(0, SWUComputePlayCost(intval($player), $o) - 4) > $ready) continue;
        $units[] = $mz;
    }
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Play_a_unit_(costs_4_less;_deal_4_to_it)", "TS26_32#0");
};
