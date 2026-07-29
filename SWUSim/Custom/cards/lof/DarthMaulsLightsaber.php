<?php
// LOF_140
// Cost 3 - Darth Maul's Lightsaber - [Aggression,Villainy] - Upgrade Power 4 - Upgrade HP 2
// Text: Attach to a friendly non-Vehicle unit. / When Played: If attached unit is Darth Maul, you may attack with him. For this attack, he gains Overwhelm and can't attack bases.

// LOF_140 Maul's Lightsaber — When Played (as upgrade; $mzID = the HOST): if the attached unit is Darth
// Maul, you may attack with him; for this attack he gains Overwhelm and can't attack bases.
$whenPlayedAbilities["LOF_140:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host)) return;
    if (SWUObjectTitle($host) !== 'Darth Maul') return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Attack_with_Darth_Maul_(Overwhelm,_can't_attack_bases)?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_140#0|" . intval($host->UniqueID ?? -1), 1);
};

$customDQHandlers["LOF_140#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? -1));
    if ($mz === null || $mz === '') return;
    AddTurnEffect($mz, 'OVERWHELM^LOF_140');         // for this attack (phase-duration grant)
    BeginSWUAttack(intval($player), $mz, true);       // noBases = true (can't attack bases)
};
