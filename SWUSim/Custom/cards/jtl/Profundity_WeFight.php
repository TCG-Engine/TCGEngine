<?php
// JTL_154
// Cost 9 - Profundity - We Fight! - [Aggression,Heroism] - Power 8 - HP 9
// Text: Overwhelm / When Played/When Defeated: Choose a player. They discard a card from their hand. Then, if they have more cards in their hand than you, they discard a card from their hand.

$customDQHandlers["JTL_154#0"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0]);
    $target = SWUDecodePlayerPick($lastDecision, $caster); // "You"→caster, "Opponent"/"P{n}"→that player
    global $playerID;
    $playerID = $caster;
    SWUDiscardCards($caster, 1, $target);  // $target discards 1
    DecisionQueueController::AddDecision($caster, "CUSTOM", "JTL_154#1|{$target}|{$caster}", 1);
};

$customDQHandlers["JTL_154#1"] = function($player, $parts, $lastDecision) {
    $target = intval($parts[0]); $caster = intval($parts[1]);
    global $playerID;
    $playerID = $caster;
    $count = function($p) { $n = 0; foreach (GetHand($p) as $c) { if (empty($c->removed)) $n++; } return $n; };
    if ($count($target) > $count($caster)) SWUDiscardCards($caster, 1, $target); // $target discards again
};

$jtl154_choose = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1, "Choose_a_player_to_discard");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "JTL_154#0|" . intval($player), 1);
};

$whenPlayedAbilities["JTL_154:0"] = $jtl154_choose;
$whenDefeatedAbilities["JTL_154:0"] = $jtl154_choose;
