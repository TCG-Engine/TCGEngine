<?php
// HMW_255
// Cost 2 - C-3PO, Captivating Storyteller - [Heroism] - Unit (Ground) 2/3 - Traits: Rebel, Droid - Unique
// Text: When Played: You may give an Ewok unit +2/+2 for this phase. You may give a Rebel unit +2/+2 for
//       this phase.
//
// TWO INDEPENDENT "may" choices. Both are queued up front so declining (or having no target for) the first
// still offers the second. No "friendly" qualifier, so any Ewok / any Rebel unit is a legal target (per the
// SWU targeting default — including an enemy one, and C-3PO itself for the Rebil half). SWUApplyPhaseBuff
// stacks per-application (_SWUStackingStatToken), so if a single unit ever qualified for both it would get
// +4/+4 — no Ewok is also a Rebel in the current pool, so that path is not exercisable here.
$whenPlayedAbilities["HMW_255:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ewoks  = _SWUCollectUnits(-1, fn($o) => TraitContains($o, 'Ewok'));
    $rebels = _SWUCollectUnits(-1, fn($o) => TraitContains($o, 'Rebel'));
    if (!empty($ewoks)) {
        SWUQueueMayChooseTarget(intval($player), $ewoks,
            "Give_an_Ewok_unit_+2/+2?", "Give_an_Ewok_unit_+2/+2_this_phase", 'HMW_255#0');
    }
    if (!empty($rebels)) {
        SWUQueueMayChooseTarget(intval($player), $rebels,
            "Give_a_Rebel_unit_+2/+2?", "Give_a_Rebel_unit_+2/+2_this_phase", 'HMW_255#1');
    }
};

$customDQHandlers["HMW_255#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'HMW_255');
};
$customDQHandlers["HMW_255#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'HMW_255');
};
