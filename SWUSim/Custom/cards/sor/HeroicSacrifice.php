<?php
// SOR_150
// Cost 1 - Heroic Sacrifice - [Aggression,Heroism]
// Text: Draw a card, then attack with a unit. For this attack, it gets +2/+0 and gains: "When this unit deals combat damage: Defeat it."

// SOR_150 Heroic Sacrifice — +2/+0 for this attack, mark the attacker so that "when it deals combat
// damage" it is defeated (checked in SWUCollectCombatHitTriggers), then attack.
$customDQHandlers["SOR_150#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $savedPID = $playerID;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $obj = (!empty($mz) && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($obj)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    SWUAddAttackPowerBonus($mz, 2);
    AddTurnEffect($mz, "SOR_150");                                  // self-defeat-on-combat-damage marker
    BeginSWUAttack(intval($player), $mz);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_150:0"] = function($player, $mzID = '') {
// Heroic Sacrifice — "Draw a card, then attack with a unit. For this attack,
            // it gets +2/+0 and gains: 'When this unit deals combat damage: Defeat it.'"
            global $playerID;
            $playerID = intval($player);
            DoDrawCard(intval($player), 1);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;   // drew the card, but no unit able to attack
            SWUQueueChooseTarget($player, $readyUnits, "Choose_a_unit_to_attack_with", "SOR_150#0");
            return;
};
