<?php
// JTL_116
// Cost 5 - Dornean Gunship - [Command] - Power 4 - HP 6
// Text: When Played: Deal indirect damage to a player equal to the number of Vehicle units you control. (That player assigns that much unpreventable damage among their base and units.)

// ── JTL_116 Dornean Gunship — When Played: deal indirect damage to a player equal to the number of
// Vehicle units you control. ──────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_116:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $cnt = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (HasTrait($u->CardID ?? '', 'Vehicle')) $cnt++; }
    if ($cnt > 0) SWUDealIndirectToChosenPlayer(intval($player), $cnt, '', _SWUSrcUID($mzID));
};
