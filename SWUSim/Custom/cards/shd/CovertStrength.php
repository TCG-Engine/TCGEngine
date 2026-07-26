<?php
// SHD_075
// Cost 1 - Covert Strength - [Vigilance]
// Text: Heal 2 damage from a unit and give an Experience token to it. / Smuggle [3 resources Vigilance] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_075 Covert Strength — heal 2 + Experience to the chosen unit ──────────
$customDQHandlers["SHD_075#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnHealUnit(intval($player), $lastDecision, 2);
    DoGiveExperienceToken(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_075:0"] = function($player, $mzID = '') {
// Covert Strength — Heal 2 damage from a unit AND give an Experience token
                          // to it (one pick, both effects → SHD_075#0).
            global $playerID; $playerID = intval($player);
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Heal_2_and_give_an_Experience_token_to_a_unit", "SHD_075#0");
            return;
};
