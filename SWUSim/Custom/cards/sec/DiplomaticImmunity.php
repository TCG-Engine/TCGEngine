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
    $atk = GetSWUVar('SWU_CURRENT_ATTACKER', '');
    if ($atk === '' || $atk === null) return;
    $atkDef = preg_replace('/^my/', 'their', $atk); // attacker stored in attacker frame; flip to defender frame
    AddTurnEffect($atkDef, SWUMakeTurnEffect('SWUDEBUFF', [2, 0], SWU_DUR_ATTACK));
};
