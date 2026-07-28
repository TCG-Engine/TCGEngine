<?php
// SOR_196
// Cost 5 - Chewbacca - Loyal Companion - [Cunning,Heroism] - Power 3 - HP 6
// Text: Sentinel (Units in this arena can't attack your non-Sentinel units or your base.) / When this unit is attacked: Ready him.

// SOR_196 Chewbacca (unit) — "When this unit is attacked: Ready him." First implemented On Defense
// ability (CR 15.c: "When this unit is attacked" = the On Defense window). Sentinel is auto-wired.
// The OnDefense mzID is already converted to this controller's frame in CombatLogic, so OnReadyCard
// readies Chewbacca (the defender), not the attacker. Mandatory + automatic (no "may", no decision).
$onDefenseAbilities["SOR_196:0"] = function($player, $mzID) {
    OnReadyCard(intval($player), $mzID);
};
