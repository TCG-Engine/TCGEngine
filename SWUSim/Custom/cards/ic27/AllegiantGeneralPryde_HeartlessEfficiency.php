<?php
// IC27_056
// Cost 1 - Allegiant General Pryde - Heartless Efficiency - [Command,Villainy] - Unit (Ground) 2/2 (unique)
//   Traits: First Order, Official
// Text: When Defeated: Give an Experience token to the friendly unit with the most power. (If multiple units are
//       tied, choose one.)
//
// The pick is mandatory, so it is built in a CUSTOM (a mandatory relative-mzID choose queued straight from a
// trigger closure can be skipped when DispatchTrigger restores $playerID). The pool is every friendly unit — the
// TEAM in Team Suns — with the highest CURRENT power (ObjectCurrentPower, Experience and buffs included); Pryde
// himself has already left play by the time his When Defeated runs. One leader auto-resolves; a tie prompts
// between the tied units only.
// Tests: SWUSim/Tests/Cases/ic27/AllegiantGeneralPryde_HeartlessEfficiency.md
$whenDefeatedAbilities["IC27_056:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_056#0', 1);
};

$customDQHandlers["IC27_056#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $best = -1; $pool = [];
    foreach (_SWUCollectUnitTargets(intval($player), ['side' => 'friendly']) as $mz) {
        $pw = intval(ObjectCurrentPower(GetZoneObject($mz)));
        if ($pw > $best) { $best = $pw; $pool = [$mz]; }
        elseif ($pw === $best) $pool[] = $mz;
    }
    if (empty($pool)) return;
    SWUQueueChooseTarget(intval($player), $pool, 'Give_an_Experience_token_to_the_friendly_unit_with_the_most_power', 'GIVE_EXPERIENCE|1');
};
