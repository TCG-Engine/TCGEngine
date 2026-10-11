<?php
// HMW_244 Separatist Harbinger — Unit (Space) 1/4, cost 3.
// "When Played/On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it."
// Twin Suns: the caster picks WHICH opponent (every opponent has a base, so none is filtered). That player
// picks among their own units and base; then the caster decides. A lone base resolves without a
// cross-player auto-pick. Every step carries the caster and the chosen target's identity in its Param.

$whenPlayedAbilities["HMW_244:0"] = $onAttackAbilities["HMW_244:0"] = function($player, $mzID = '') {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_244#0", 1);
};

$customDQHandlers["HMW_244#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    SWUQueueChooseOpponent(intval($player), 'HMW_244#1', "Which_opponent_chooses_a_unit_or_base?");
};

$customDQHandlers["HMW_244#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $playerID = $opp;
    $targets = array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter));
    if (empty($targets)) {
        $playerID = $caster;
        _SWUHmw244AskCaster($caster, $opp, 0);
        return;
    }
    $targets[] = 'myBase-0';
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $targets), 1,
        tooltip: "Choose_a_unit_or_base_you_control_(the_opponent_may_deal_2_damage_to_it)");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "HMW_244#2|{$caster}", 1);
};

if (!function_exists('_SWUHmw244AskCaster')) {
    function _SWUHmw244AskCaster(int $caster, int $opp, int $uid): void {
        global $playerID; $playerID = $caster;
        DecisionQueueController::AddDecision($caster, "YESNO", "-", 1, tooltip: "Deal_2_damage_to_the_chosen_" . ($uid > 0 ? "unit" : "base") . "?");
        DecisionQueueController::AddDecision($caster, "CUSTOM", "HMW_244#3|{$opp}|{$uid}", 1);
    }
}

$customDQHandlers["HMW_244#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = intval($player);
    $caster = intval($parts[0] ?? 0);
    if ($caster <= 0) return;
    $playerID = $opp;
    $uid = 0;
    if (strpos((string)$lastDecision, 'Base') === false) {
        $o = GetZoneObject((string)$lastDecision);
        if (SWUObjGone($o)) { $playerID = $caster; return; }
        $uid = intval($o->UniqueID ?? 0);
    }
    _SWUHmw244AskCaster($caster, $opp, $uid);
};

$customDQHandlers["HMW_244#3"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $opp = intval($parts[0] ?? 0);
    $uid = intval($parts[1] ?? 0);
    if ($uid > 0) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player));
    } elseif ($opp > 0) {
        SWUDealDamageToBase(2, $opp);
    }
};
