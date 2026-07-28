<?php
// LOF_206
// Cost 1 - Babu Frik - Heyyy! - [Cunning] - Power 1 - HP 4
// Text: Action [Exhaust]: You may attack with a friendly Droid unit. For this attack, it deals damage equal to its remaining HP instead of its power.

// LOF_206 — Action [Exhaust]: You may attack with a friendly Droid unit. For this attack, it deals
// damage equal to its remaining HP instead of its power (the SWU_HP_AS_DAMAGE attack-duration marker
// is read in SWUCombatDamage). Mirrors JTL_146's "attack with a Fighter" shape.
$unitAbilities["LOF_206"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $droids = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (HasTrait($u->CardID, 'Droid')) $droids[] = "{$zone}-{$i}";
        }
    }
    if (empty($droids)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $droids, "Choose_a_Droid_to_attack_with_(damage_=_remaining_HP)", "LOF_206#0");
};

$customDQHandlers["LOF_206#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') { SWUAfterAction($player); return; }
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) { SWUAfterAction($player); return; }
    AddTurnEffect($lastDecision, 'SWU_HP_AS_DAMAGE'); // deals damage = remaining HP this attack
    BeginSWUAttack(intval($player), $lastDecision);    // combat owns SWUAfterAction
};
