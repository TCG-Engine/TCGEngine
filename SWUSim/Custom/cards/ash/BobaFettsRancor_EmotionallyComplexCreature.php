<?php
// ASH_179
// Cost 8 - Boba Fett's Rancor - Emotionally Complex Creature - [Aggression] - Power 8 - HP 9
// Text: When Played: Deal 5 damage to your base. Then, deal 5 damage to an enemy ground unit. Then, deal 5 damage to the same unit. / On Attack: You may deal 1 damage to a base for every 5 damage on your base.

// ASH_179 Boba Fett's Rancor — When Played: deal 5 to your base; then deal 5 to an enemy ground unit;
// then deal 5 to the same unit. (On Attack ability below.)
$whenPlayedAbilities["ASH_179:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUDealDamageToBase(5, intval($player));   // 5 to your own base
    $tg = ZoneSearch("theirGroundArena", AnyUnitFilter);
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Deal_5_then_5_to_an_enemy_ground_unit", "ASH_179#0");
};

$customDQHandlers["ASH_179#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 5, intval($player));   // first 5
    $mz2 = SWUFindMzByUID($uid);
    if ($mz2 !== null) SWUDealDamageToUnit($mz2, 5, intval($player));   // "then deal 5 to the same unit"
};

// ASH_179 On Attack: you may deal 1 damage to a base for every 5 damage on your base.
$onAttackAbilities["ASH_179:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $base = GetBase(intval($player));
    $dmg = (!empty($base) && !empty($base[0])) ? intval($base[0]->Damage ?? 0) : 0;
    $n = intdiv($dmg, 5);
    if ($n <= 0) return;
    SWUOfferBaseTarget(intval($player), ['continuation'=>'DEAL_BASE_DAMAGE','amount'=>$n,'may'=>true,'question'=>"Deal_{$n}_damage_to_a_base?",'prompt'=>"Choose_a_base"]);
};
