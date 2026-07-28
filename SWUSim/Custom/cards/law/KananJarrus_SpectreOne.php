<?php
// LAW_089
// Cost 4 - Kanan Jarrus - Spectre One - [Cunning,Vigilance,Heroism] - Power 3 - HP 4
// Text: Restore 1 / When Played: You may return a non-leader unit that costs 2 or less to its owner's hand. If you control a Command or Aggression unit, you may return a non-leader unit that costs 4 or less instead.

// LAW_089 Kanan Jarrus — Restore 1 + When Played: you may return a non-leader unit that costs 2 or less
// to its owner's hand (4 or less instead if you control a Command or Aggression unit).
$whenPlayedAbilities["LAW_089:0"] = function($player, $mzID) {
    $threshold = (PlayerHasUnitWithAspectInPlay(intval($player), 'Command') || PlayerHasUnitWithAspectInPlay(intval($player), 'Aggression')) ? 4 : 2;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID ?? '')) <= $threshold,
        'question' => "Return_a_non-leader_unit_to_hand?", 'prompt' => "Choose_a_unit",
    ]);
};
