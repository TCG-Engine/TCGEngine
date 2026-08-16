<?php
// SHD_129
// Cost 1 - Timely Intervention - [Command]
// Text: Play a unit from your hand. Give it Ambush for this phase. (When you play it, it may ready and attack an enemy unit.) / Smuggle [2 resources, command] (If this card is a resource, you may play it for its smuggle cost. Replace it with the top card of your deck.)

// ─── SHD_129 Timely Intervention — play the chosen hand unit, granting Ambush ──
// Nested play with the turn/PASS save-restore; the EVENT flow (FINISH_PLAY_CARD) owns the
// after-action, so no SWUAfterAction here (contrast the SEC_007 leader-action twin).
$customDQHandlers["SHD_129#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) return;
    $gPlayGrantTurnEffect = 'SHD_129';   // the played unit gains Ambush this phase
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $mz, false);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $gPlayGrantTurnEffect = null;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_129:0"] = function($player, $mzID = '') {
// Timely Intervention — play a unit from your hand (paying its cost);
                          // it gains Ambush for this phase (SEC_007 Dryden mirror, event form).
            global $playerID; $playerID = intval($player);
            DecisionQueueController::CleanupRemovedCards();   // the event is a removed hand entry
            $ready = SWUTotalPaymentCapacity(intval($player));
            $units = [];
            foreach (ZoneSearch('myHand') as $hmz) {
                $u = GetZoneObject($hmz);
                if (SWUObjGone($u)) continue;
                if (stripos(CardType($u->CardID) ?? '', 'Unit') === false) continue;
                if (SWUComputePlayCost(intval($player), $u) > $ready) continue;
                $units[] = $hmz;
            }
            if (empty($units)) return;
            // DECLINABLE (user ruling 2026-08-15): "play a unit from your HAND" can always be declined,
            // because the hand is a HIDDEN zone — a player is never forced to reveal that they held a
            // playable unit. Same reasoning for SHD_016 Fennec Shand and SOR_022 Energy Conversion Lab.
            SWUQueueMayChooseTarget(intval($player), $units,
                "Play_a_unit_from_your_hand_(it_gains_Ambush)?",
                "Play_a_unit_from_your_hand_(it_gains_Ambush)", "SHD_129#0");
            return;
};
