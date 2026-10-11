<?php
// LOF_082
// Cost 2 - Vaneé - I Live to Serve - [Command,Villainy] - Power 2 - HP 4
// Text: When Played/On Attack: You may defeat an Experience token on a friendly unit. If you do, give an Experience token to a friendly unit.

// LOF_082 Vaneé — When Played/On Attack: may defeat an Experience token on a friendly unit. If you do,
// give an Experience token to a friendly unit.
$whenPlayedAbilities["LOF_082:0"] =
$onAttackAbilities["LOF_082:0"]   = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    // ⚠ FRIENDLY spans the TEAM (user ruling 2026-08-25, IBH_095): in Team Suns a teammate's
    // unit is friendly, so the pool is SWUFriendlyUnits() ('team'), not 'my'. ⚠ NOT the same as "a unit you control",
    // which stays 'my' — control is per-player. 'team' degrades to 'my' outside a team game, so
    // Premier is byte-identical. See memory: unqualified pools miss teammates.
    foreach (SWUFriendlyUnits() as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && _CountExperienceSubcards($o) > 0) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_an_Experience_token_on_a_friendly_unit?", "Choose_a_unit", "LOF_082#0");
};

$customDQHandlers["LOF_082#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    if (!SWUDefeatExperienceToken($lastDecision)) return;
    GiveTokenUpgrade(intval($player), '', [
        'prompt' => "Give_an_Experience_token_to_a_friendly_unit",
    ]);
};
