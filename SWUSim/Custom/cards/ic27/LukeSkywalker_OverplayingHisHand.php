<?php
// IC27_165
// Cost 8 - Luke Skywalker - Overplaying His Hand - [Cunning,Heroism] - Power 9 - HP 9
// Traits: Force, Rebel
// Text: When Played: You may put an enemy non-leader unit on the bottom of its owner's deck.
//       Its owner draws 2 cards.
//
// One optional pick (MZMAYCHOOSE, so a lone target still offers the decline). "Enemy" = side 'their',
// which fans out to every opponent in Twin Suns and leaves out a Team Suns teammate. The unit goes to the
// bottom FIRST (SWUUnitToBottomOfDeck: upgrades defeated, captives rescued, a token ceases), THEN its
// OWNER draws 2 — so with one card in that deck the second draw is the unit itself. "Its owner" is read
// off the chosen unit (determined, never a prompt), so a stolen unit sends the draws to its owner, not
// its controller. Declining leaves no "its", so nobody draws. The draw is not "If you do": a token that
// ceases instead of reaching the deck still gives its owner the 2 cards.
// Tests: SWUSim/Tests/Cases/ic27/LukeSkywalker_OverplayingHisHand.md

$whenPlayedAbilities["IC27_165:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'side' => 'their', 'nonLeader' => true, 'may' => true,
        'continuation' => 'IC27_165#0',
        'prompt' => 'Put_an_enemy_non-leader_unit_on_the_bottom_of_its_owners_deck',
    ]);
};

$customDQHandlers["IC27_165#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) return;
    $owner = SWUObjSeat($o, true);
    SWUUnitToBottomOfDeck(intval($player), (string)$lastDecision);
    DoDrawCard($owner, 2);
};
