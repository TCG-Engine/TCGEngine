<?php
// SOR_055
// Cost 4 - The Force Is With Me - [Vigilance,Heroism]
// Text: Choose a friendly unit and give 2 Experience tokens to it. If you control a FORCE unit, also give a Shield token to the chosen unit. You may attack with the chosen unit.

// SOR_055 The Force Is With Me (event) — give the chosen friendly unit 2 Experience, a Shield if a
// Force unit is controlled, then offer an optional attack with it. The chosen mzID rides through the
// pipe-delimited CUSTOM param to the attack follow-up.
$customDQHandlers["SOR_055#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $mz = $lastDecision;
    $obj = GetZoneObject($mz);
    DoGiveExperienceToken(intval($player), $mz);
    DoGiveExperienceToken(intval($player), $mz);
    if (_SWUControlsForceUnit(intval($player))) DoGiveShieldToken(intval($player), $mz);
    // YESNO prompt text lives in the TOOLTIP (param "-"); the client renders Tooltip (underscores→
    // spaces), else falls back to "Please choose Yes or No:". Resolve the unit's title from its CardID.
    $title = ($obj !== null) ? SWUObjectTitle($obj) : '';
    $prompt = ($title !== '') ? "Attack_with_" . str_replace(' ', '_', $title) . "?" : "Attack_with_the_chosen_unit?";
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip:$prompt);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SOR_055#1|" . $mz, 1);
};

$customDQHandlers["SOR_055#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return; // optional attack declined → event cleanup handles after-action
    global $playerID;
    $playerID = intval($player);
    BeginSWUAttack(intval($player), $parts[0]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_055:0"] = function($player, $mzID = '') {
// The Force Is With Me — "Choose a friendly unit and give 2 Experience tokens
            // to it. If you control a FORCE unit, also give a Shield token to it. You may attack with
            // the chosen unit."
            global $playerID;
            $playerID = intval($player);
            $friendly = SWUFriendlyUnits();
            if (empty($friendly)) return;
            SWUQueueChooseTarget($player, $friendly, "Choose_a_friendly_unit", "SOR_055#0");
            return;
};
