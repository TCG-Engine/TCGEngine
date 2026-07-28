<?php
// TWI_101
// Cost 2 - Mas Amedda - Vice Chair - [Command,Command] - Power 0 - HP 4
// Text: When you play another unit: You may exhaust this unit. If you do, search the top 4 cards of your deck for a unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

$customDQHandlers["TWI_101#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    OnExhaustCard(intval($player), $mz);
    if (count(GetDeck(intval($player))) === 0) return;
    DoTopDeckSearch(intval($player), 4, fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false, 1);
};

function Twi101Reaction(int $player, int $uid): void
{
  global $playerID;
  $playerID = intval($player);
  $mz = SWUFindMzByUID($uid);
  if ($mz === null)
    return;
  $o = GetZoneObject($mz);
  if (SWUObjGone($o) || intval($o->Status ?? 0) !== 1)
    return;
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Exhaust_Mas_Amedda_to_search_the_top_4_for_a_unit?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TWI_101#0|" . $uid, 1);
}
