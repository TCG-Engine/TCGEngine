<?php
// TS26_31
// Cost 1 - Chaotic Diversion - [Aggression,Cunning]
// Text: Ready an enemy unit. If you do, it can't attack your base or units you control for this phase. / Give a Shield token to a friendly unit.

// TS26_31 Chaotic Diversion — ready the chosen enemy + mark it CANT_ATTACK this phase, then shield a
// friendly unit.
$customDQHandlers["TS26_31#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) {
        OnReadyCard(intval($player), $lastDecision);
        AddTurnEffect($lastDecision, 'CANT_ATTACK');
    }
    GiveTokenUpgrade($player, '', ['token'=>'SHIELD','prompt'=>"Give_a_Shield_to_a_friendly_unit"]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_31:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $enemy = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (!empty($enemy)) {
        SWUQueueChooseTarget(intval($player), $enemy, "Ready_an_enemy_unit_(it_can't_attack_you)", "TS26_31#0");
    } else {
        GiveTokenUpgrade($player, '', ['token'=>'SHIELD','prompt'=>"Give_a_Shield_to_a_friendly_unit"]);
    }
};
