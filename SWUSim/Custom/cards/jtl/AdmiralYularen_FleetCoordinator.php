<?php
// JTL_047
// Cost 3 - Admiral Yularen - Fleet Coordinator - [Vigilance,Heroism] - Power 1 - HP 5
// Text: When Played: Choose Grit, Restore 1, Sentinel, or Shielded. While this unit is in play, each friendly Vehicle unit gains the chosen keyword.

// ── JTL_047 Admiral Yularen — When Played: choose Grit / Restore 1 / Sentinel / Shielded; while in play,
// each friendly Vehicle gains the chosen keyword (stored per-UID, read by the conditional-keyword auras).
$whenPlayedAbilities["JTL_047:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    $uid = SWUObjUID($obj, 0);
    if ($uid === 0) return;
    DecisionQueueController::AddDecision($player, 'OPTIONCHOOSE', 'Grit&Restore_1&Sentinel&Shielded', 1, "Choose_a_keyword_for_your_Vehicles");
    DecisionQueueController::AddDecision($player, 'CUSTOM', "JTL_047#0|{$uid}", 1);
};

$customDQHandlers["JTL_047#0"] = function($player, $parts, $lastDecision) {
    $uid = intval($parts[0] ?? 0);
    $map = ['Grit' => 'GRIT', 'Restore_1' => 'RESTORE', 'Sentinel' => 'SENTINEL', 'Shielded' => 'SHIELDED'];
    $kw  = $map[$lastDecision] ?? null;
    if ($uid === 0 || $kw === null) return;
    AddGlobalEffects(intval($player), "SWU_YULAREN_{$uid}_{$kw}");
};
