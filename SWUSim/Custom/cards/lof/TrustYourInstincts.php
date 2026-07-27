<?php
// LOF_221
// Cost 1 - Trust Your Instincts - [Cunning]
// Text: Use the Force. If you do, attack with a unit. It gets +2/+0 for this attack and deals its combat damage before the defender. (If the defender is defeated, it deals no combat damage.)

// LOF_221 Trust Your Instincts — the chosen attacker gets +2/+0 and Shoot First, then attacks.
$customDQHandlers["LOF_221#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $attackerMzID = $lastDecision ?? '';
  if (empty($attackerMzID) || !str_contains($attackerMzID, '-')) {
    SWUAfterAction(intval($player));
    return;
  }
  $attacker = GetZoneObject($attackerMzID);
  if (SWUObjGone($attacker)) {
    SWUAfterAction(intval($player));
    return;
  }
  SWUApplyPhaseBuff($attackerMzID, 2, 0, 'LOF_221'); // +2/+0 for this attack
  AddTurnEffect($attackerMzID, 'SHOOT_FIRST');       // deals combat damage before the defender
  BeginSWUAttack(intval($player), $attackerMzID);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_221:0"] = function($player, $mzID = '') {
// Trust Your Instincts — "Use the Force. If you do, attack with a unit. It gets
                          // +2/+0 for this attack and deals its combat damage before the defender."
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            global $playerID;
            $playerID = intval($player);
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
            // The event-owns-after-action flag is set centrally by BeginSWUAttack (LOF_221#0 → BeginSWUAttack),
            // which detects the pending FINISH_PLAY_CARD terminator. No manual flag needed.
            SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "LOF_221#0", 1);
            return;
};
