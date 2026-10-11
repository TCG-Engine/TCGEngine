<?php
// HMW_266 Familiar Strategem — Event, cost 1, [Heroism], Tactic.
// "Attack with a unit. If it shares a Trait with another friendly unit, it gets +2/+0 for this attack."
// A READY friendly unit attacks (SOR_220's shape). The trait check is made when the attack begins, against
// OTHER friendly units (team-wide, object-aware); the bonus is a one-attack power bonus.

$whenPlayedAbilities["HMW_266:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (!SWUObjGone($o) && intval($o->Status ?? 0) === 1) $ready[] = $mz;
    }
    if (empty($ready)) return;
    SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit", "HMW_266#0");
};

$customDQHandlers["HMW_266#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $atk = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($atk)) return;
    $uid = intval($atk->UniqueID ?? 0);
    $traits = array_filter(array_map('trim', explode(',', (string)(CardTrait($atk->CardID ?? '') ?? ''))));
    $shares = false;
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? -1) === $uid) continue;
        $ut = array_filter(array_map('trim', explode(',', (string)(CardTrait($u->CardID ?? '') ?? ''))));
        foreach (array_unique(array_merge($traits, $ut)) as $t) {
            if (TraitContains($atk, $t) && TraitContains($u, $t)) { $shares = true; break 2; }
        }
    }
    if ($shares) SWUAddAttackPowerBonus((string)$lastDecision, 2);
    BeginSWUAttack(intval($player), (string)$lastDecision);
};
