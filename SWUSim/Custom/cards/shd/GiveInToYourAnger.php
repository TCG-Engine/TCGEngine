<?php
// SHD_144
// Cost 1 - Give In to Your Anger - [Villainy,Aggression]
// Text: Deal 1 damage to an enemy unit. Its controller's next action this phase must be an attack action with that unit, if able. It must attack a unit, if able.

// SHD_144 Give In to Your Anger — deal 1 damage to the chosen enemy unit, then arm SWU_SHD144_FORCE
// on its controller (keyed by the unit's UID). _SWUCheckForcedAttack (GameLogic, after the turn swap)
// forces that unit to attack on the controller's next action. UID is captured BEFORE the damage in case
// the 1 damage defeats the unit — then the forced attack simply doesn't happen ("if able").
$customDQHandlers["SHD_144#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid        = intval($obj->UniqueID ?? 0);
    $controller = OtherPlayer(intval($player));   // target was chosen from the enemy arena
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    if ($uid > 0) AddGlobalEffects($controller, 'SWU_SHD144_FORCE|' . $uid);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_144:0"] = function($player, $mzID = '') {
// Give In to Your Anger — "Deal 1 damage to an enemy unit. Its controller's next
                          // action this phase must be an attack action with that unit, if able. It must
                          // attack a unit, if able." Choose the enemy unit → SHD_144#0 deals the damage and
                          // arms SWU_SHD144_FORCE|{uid} on that unit's controller; _SWUCheckForcedAttack
                          // (run after the turn passes to that controller) forces the attack.
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (array_merge(ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_1_damage_to_an_enemy_unit", "SHD_144#0");
            return;
};
