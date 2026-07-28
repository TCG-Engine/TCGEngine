<?php
// JTL_157
// Cost 6 - Relentless Firespray - [Aggression,Aggression] - Power 4 - HP 6
// Text: On Attack: Ready this unit. Use this ability only once each round.

// ── JTL_157 Relentless Firespray — On Attack: Ready this unit. Once each round. ───────────────────────
$onAttackAbilities["JTL_157:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if ($o === null) return;
    if (!SWUHasUseAvailable($o)) return; // once per round — per-unit NumUses budget
    SWUConsumeUse($o);
    OnReadyCard(intval($player), $mzID);
};
