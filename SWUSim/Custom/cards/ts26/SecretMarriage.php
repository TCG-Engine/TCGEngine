<?php
// TS26_46
// Cost 2 - Secret Marriage - [Vigilance]
// Text: Give a Shield token to each of up to 2 non-Vehicle units. If you give a Shield to an enemy unit this way, draw a card. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// TS26_46 Secret Marriage — shield each chosen non-Vehicle unit; if any was an enemy, draw a card.
$customDQHandlers["TS26_46#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS') return;
    global $playerID; $playerID = intval($player);
    $shieldedEnemy = false;
    foreach (explode('&', $lastDecision) as $mz) {
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        DoGiveShieldToken(intval($player), $mz);
        if (intval($o->Controller ?? 0) !== intval($player)) $shieldedEnemy = true;
    }
    if ($shieldedEnemy) DoDrawCard(intval($player), 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_46:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    $max = min(2, count($tg));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Shield_up_to_2_non-Vehicle_units_(draw_if_you_shield_an_enemy)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_46#0", 1, dontSkipOnPass: 1);
};
