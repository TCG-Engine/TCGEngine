<?php
// LOF_123
// Cost 1 - Directed by the Force - [Command]
// Text: The Force is with you (create your Force token). You may play a unit from your hand (paying its cost).

// LOF_123 Directed by the Force — plays the chosen hand unit ($lastDecision) at FULL cost. Runs inside
// the event's resolution, so the event's FINISH_PLAY_CARD owns the after-action: neutralise the inner
// ActivateCard's own turn advance by capturing/restoring the turn state (mirrors SOR_219). "You may",
// so a '-'/empty decline is a clean no-op.
$customDQHandlers["LOF_123#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer;
    $playerID  = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_123:0"] = function($player, $mzID = '') {
// Directed by the Force — "The Force is with you. You may play a unit from your
                          // hand (paying its cost)." Nested play inside the event resolution.
            TheForceIsWithYou(intval($player));
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Play_a_unit_from_your_hand?", "Choose_a_unit_to_play", "LOF_123#0");
            return;
};
