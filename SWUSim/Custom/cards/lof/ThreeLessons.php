<?php
// LOF_225
// Cost 2 - Three Lessons - [Cunning]
// Text: Play a unit from your hand (paying its cost). It gains Hidden for this phase. Give an Experience token and a Shield token to it.

// LOF_225 Three Lessons — play the chosen unit from hand at full cost; it enters with Hidden (this phase),
// an Experience token and a Shield token (entry hooks). Neutralise the inner ActivateCard turn advance.
$customDQHandlers["LOF_225#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect, $gPlayGrantExp, $gPlayGrantShield;
    $playerID  = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantTurnEffect = 'HIDDEN';
    $gPlayGrantExp        = 1;
    $gPlayGrantShield     = 1;
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
    $gPlayGrantExp        = null;
    $gPlayGrantShield     = null;
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_225:0"] = function($player, $mzID = '') {
// Three Lessons — "Play a unit from your hand (paying its cost). It gains Hidden
                          // for this phase. Give an Experience token and a Shield token to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(Hidden_+_Experience_+_Shield)", "LOF_225#0");
            return;
};
