<?php
// TS26_33
// Cost 3 - Kouhun Assassination - [Cunning,Vigilance,Villainy]
// Text: An opponent (of your choice) may discard a card from their hand. If they do, give a non-Vehicle unit -8/-8 for this phase.

// TS26_33 Kouhun Assassination — the opponent's discard choice ($player = opponent). If they discarded,
// the caster (parts[0]) gives a non-Vehicle unit -8/-8 for this phase.
$customDQHandlers["TS26_33#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $playerID = intval($player);          // opponent frame — discard their chosen card
    DoDiscardCard(intval($player), $lastDecision);
    $playerID = $caster;                  // caster picks the -8/-8 target
    $tg = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, ['Unit', 'Token Unit', 'Leader Unit']) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $tg[] = $mz;
        }
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget($caster, $tg, "Give_a_non-Vehicle_unit_-8/-8", "APPLY_PHASE_DEBUFF|8|8|TS26_33");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_33:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    $playerID = $opp;
    $hand = ZoneSearch('myHand');   // opponent's hand (opponent frame)
    if (empty($hand)) return;
    SWUQueueMayChooseTarget($opp, $hand, "Discard_a_card?", "Choose_a_card_to_discard", "TS26_33#0|" . intval($player));
};
