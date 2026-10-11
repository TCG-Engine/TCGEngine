<?php
// LOF_008
// Cost 5 - Obi-Wan Kenobi - Courage Makes Heroes - [Command,Heroism] - Power 3 - HP 6
// Text: Action [Exhaust, use the Force (lose your Force token)]: Give an Experience token to a unit without an Experience token on it.
// DeployText: On Attack: You may give an Experience token to another unit without an Experience token on it.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_008 Obi-Wan Kenobi — On Attack: You may give an Experience token to ANOTHER unit without an
// Experience token on it.
$onAttackAbilities["LOF_008:0"] = function($player, $mzID) {
    // Another unit (either player) without an Experience token on it.
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'excludeSelf' => true, 'may' => true,
        // _CountExperienceSubcards, not a raw `$sc->CardID` loop: subcards reloaded from the gamestate are
        // ARRAYS, so a property read saw no token and re-offered an Experienced unit (game 1647080).
        'extraFilter' => fn($o) => _CountExperienceSubcards($o) === 0,
        'question' => "Give_an_Experience_token_to_a_unit_without_one?",
        'prompt'   => "Choose_a_unit",
    ]);
};

// LOF_008 Obi-Wan Kenobi — Action [Exhaust, use the Force]: Give an Experience token to a unit without an
// Experience token on it.
$leaderAbilities["LOF_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table. NOT my*+their*: `their*` excludes a Team Suns
    // teammate, so that pairing leaves their units in NEITHER list and they silently drop out
    // of the pool. SWUAllUnits() starts from 'team' (degrades to 'my' outside a team game, so
    // Premier is byte-identical). See memory: unqualified pools miss teammates.
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o)) continue;
        if (_CountExperienceSubcards($o) === 0) $targets[] = $mz;   // array-safe (see the On Attack filter)
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_without_one", "LOF_008#0");
};

$customDQHandlers["LOF_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') DoGiveExperienceToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};
