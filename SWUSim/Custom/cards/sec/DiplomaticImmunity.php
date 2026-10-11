<?php
// SEC_052
// Cost 2 - Diplomatic Immunity - [Vigilance,Heroism] - Upgrade Power 2 - Upgrade HP 2
// Text: Attached unit gains: "When this unit is attacked: You may disclose VigilanceVigilanceHeroismHeroism (reveal cards from your hand with these aspect icons among them). If you do, the attacker gets -2/-0 for this attack."

$onDefenseFromUpgradeAbilities["SEC_052"] = function($player, $hostMzID) {
    SWUQueueDisclose(intval($player), ['Vigilance', 'Vigilance', 'Heroism', 'Heroism'], "SEC_052#0",
        "Disclose_VigilanceVigilanceHeroismHeroism_to_give_the_attacker_-2/-0");
};

$customDQHandlers["SEC_052#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // Re-find the attacker by UniqueID in THIS (defender's) frame — same fix as LOF_067 Chirrut. The stored
    // SWU_CURRENT_ATTACKER is in the attacker's frame, and a my→their flip names the wrong seat above two.
    $uid = intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0'));
    $atkMz = $uid > 0 ? SWUFindMzByUID($uid) : null;
    if ($atkMz === null) return;
    AddTurnEffect($atkMz, SWUMakeTurnEffect('SWUDEBUFF', [2, 0], SWU_DUR_ATTACK));
};
