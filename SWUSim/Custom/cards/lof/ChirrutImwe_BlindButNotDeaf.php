<?php
// LOF_067
// Cost 4 - Chirrut Îmwe - Blind, but not Deaf - [Vigilance] - Power 3 - HP 5
// Text: Sentinel / When this unit is attacked (before damage is dealt): You may use the Force (lose your Force token). If you do, the attacker gets -2/-0 for this attack.

$onDefenseAbilities["LOF_067:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_give_the_attacker_-2/-0?", "LOF_067#0");
};

$customDQHandlers["LOF_067#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    // Re-find the attacker by UniqueID in THIS (defender's) frame. SWU_CURRENT_ATTACKER is written in the
    // attacker's own frame ("myGroundArena-0"); a my→their flip only names the right seat at two seats —
    // above that it aimed at another opponent and the -2/-0 silently missed (game 1647080).
    $uid = intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0'));
    $atkMz = $uid > 0 ? SWUFindMzByUID($uid) : null;
    if ($atkMz === null) return;
    AddTurnEffect($atkMz, SWUMakeTurnEffect('SWUDEBUFF', [2, 0], SWU_DUR_ATTACK));
};
