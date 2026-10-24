<?php
// IC27_070
// Cost 1 - C-3PO - Impulse Buy - [Command,Heroism] - Unit (Ground) 1/3 (unique)
//   Traits: Rebel, Droid
// Text: Action [Exhaust]: Give an Experience token to another unit. Then, if it has 6 or more power,
//       draw a card.
//
// SOR_094 Bail Organa's action with the "friendly" dropped: "another unit" is the WHOLE table — enemy
// units, leader units and Twin Suns far seats included — minus C-3PO himself (by UniqueID). The pick is
// mandatory (no "you may"); one candidate auto-resolves.
// "it" is the unit that just got the token, and its power is read AFTER the token lands — so a 5-power
// unit qualifies (the +1/+0 makes it 6). The card draws for whoever ACTIVATED the action, even when the
// token went to an enemy unit.
// No availability gate (unlike SOR_094, which predates the CR 6.4.587.c sweep): the [Exhaust] cost
// changes the game state, so with no other unit in play the action is still usable and simply resolves
// to nothing — no token, no draw.
$unitAbilities["IC27_070"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $selfUID = SWUObjUID(GetZoneObject($mzID));
    $targets = _SWUCollectUnitTargets(intval($player), ['excludeUID' => $selfUID]);
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_an_Experience_token_to_another_unit_(draw_if_it_has_6+_power)", "IC27_070#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["IC27_070#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    DoGiveExperienceToken(intval($player), $lastDecision);
    $it = GetZoneObject($lastDecision);
    if (!SWUObjGone($it) && ObjectCurrentPower($it) >= 6) DoDrawCard(intval($player), 1);
};
