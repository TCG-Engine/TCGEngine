<?php
// LAW_202
// Cost 1 - Commence the Festivities - [Aggression]
// Text: Attack with a unit. It gains Saboteur for this attack. If you control fewer resources than an opponent, it gets +2/+0 for this attack.

// LAW_202 Commence the Festivities — attack with the chosen unit; it gains Saboteur (this attack) and,
// if you control fewer resources than the opponent, +2/+0 for this attack.
$customDQHandlers["LAW_202#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    AddTurnEffect($attackerMzID, SWUMakeTurnEffect('SABOTEUR', [], SWU_DUR_ATTACK, 'LAW_202'));
    if (SWUResourceCount(intval($player)) < SWUResourceCount($opp)) SWUAddAttackPowerBonus($attackerMzID, 2);
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_202:0"] = function($player, $mzID = '') {
// Commence the Festivities — "Attack with a unit. It gains Saboteur for this
                          // attack. If you control fewer resources than an opponent, it gets +2/+0 for
                          // this attack."
            global $playerID; $playerID = intval($player);
            $readyUnits = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
                $arr = GetZone($zone);
                for ($i = 0; $i < count($arr); $i++) {
                    $u = $arr[$i];
                    if (SWUObjGone($u)) continue;
                    if (intval($u->Status) === 1) $readyUnits[] = "{$zone}-{$i}";
                }
            }
            if (empty($readyUnits)) return;
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with_(Saboteur)", "LAW_202#0|" . OtherPlayer(intval($player)));
            return;
};
