<?php
// ASH_234
// Cost 2 - Masterstroke - [Cunning]
// Text: Attack with a unit. It gets +1/+0 for this attack for each unit the defending player controls in its arena.

// ASH_234 Masterstroke — the chosen unit attacks with +1/+0 per unit the defending player controls in
// its (the attacker's) arena. Bonus computed at declaration.
$customDQHandlers["ASH_234#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $savedPID = $playerID; $playerID = intval($player);
    $attackerMzID = $lastDecision ?? '';
    $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
    if (SWUObjGone($attacker)) { $playerID = $savedPID; SWUAfterAction($player); return; }
    // "+1/+0 for each unit THE DEFENDING PLAYER controls in its arena" — the defending player is not
    // known yet: BeginSWUAttack below is what lets the player declare a target. Counting here read
    // "their{$arena}", which above two seats is EVERY live opponent's board (the Twin Suns fan-out),
    // so the bonus counted bystanders. Stamp a marker instead and let ExecuteSWUAttack do the count
    // once SWU_CURRENT_DEFENDING_SEAT is published — the same shape TWI_012 Anakin already uses.
    // At two seats the one opponent IS the defender, so the resulting number is unchanged (I1).
    AddTurnEffect($attackerMzID, 'ASH_234_ATK');
    BeginSWUAttack($player, $attackerMzID);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_234:0"] = function($player, $mzID = '') {
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
    SWUQueueChooseTarget(intval($player), $readyUnits, "Choose_a_unit_to_attack_with", "ASH_234#0");
};
