<?php
// LOF_166
// Cost 5 - Blockade Runner - [Aggression] - Power 4 - HP 4
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When this unit deals combat damage to a base: You may give an Experience token to this unit.

$customDQHandlers["LOF_166#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) DoGiveExperienceToken(intval($player), $mz);
};

function LOF166BaseReaction(int $player, string $mzID): void
{
  $o = GetZoneObject($mzID);
  $uid = SWUObjUID($o, 0);
  DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Give_this_unit_an_Experience_token?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_166#0|{$uid}", 1);
}
