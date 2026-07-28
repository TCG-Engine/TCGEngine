<?php
// ASH_180
// Cost 1 - Bokken Saber - [Aggression] - Upgrade Power 1 - Upgrade HP 1
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "When Attack Ends: Give an Advantage token to this unit."

// ASH_180 Bokken Saber — non-Vehicle upgrade (+1/+1). Attached unit gains: "When Attack Ends: Give an
// Advantage token to this unit." Fires via the OnAttackEndFromUpgrade scan (the host survived gate is
// the null-check in CollectAfterAttackTriggers).
$onAttackEndFromUpgradeAbilities["ASH_180"] = function($player, $hostMzID) {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($hostMzID);
    if (SWUObjGone($host)) return;
    DoGiveAdvantageToken(intval($player), $hostMzID);
};
