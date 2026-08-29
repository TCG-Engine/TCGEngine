<?php
// JTL_155
// Cost 1 - They Hate That Ship - [Aggression,Heroism]
// Text: An opponent creates 2 TIE Fighter tokens and readies them. Then, play a Vehicle unit from your hand. It costs 3 resources less.

// ── JTL_155 They Hate That Ship — play the chosen Vehicle from hand at a 3-resource discount. The event
// owns the action (FINISH_PLAY_CARD), so neutralise the nested ActivateCard's after-action like SOR_219.
$customDQHandlers["JTL_155#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer;
    $playerID  = intval($player);
    SWUNestedPlay(intval($player), $lastDecision, false, 3);
};

// JTL_155 They Hate That Ship — the CHOSEN opponent creates 2 readied TIEs, then the caster plays a Vehicle.
$customDQHandlers["JTL_155#OPP"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWUCreateUnitTokens($opp, 'JTL_T01', 2, true);
    _SWUJtl155PlayVehicle(intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_155:0"] = function($player, $mzID = '') {
// They Hate That Ship — "An opponent creates 2 TIE Fighter tokens and readies
                          // them. Then, play a Vehicle unit from your hand. It costs 3 resources less."
            global $playerID;
            $playerID = intval($player);
            if (SeatCountForGame() > 2) {   // Twin Suns: pick which opponent gets the 2 TIEs
                SWUQueueChooseOpponent(intval($player), "JTL_155#OPP", "Which_opponent_creates_2_TIE_Fighters?");
                return;
            }
            $opp = OtherPlayer(intval($player));
            SWUCreateUnitTokens($opp, 'JTL_T01', 2, true); // 2 TIE Fighters (Space, 1/1), readied
            _SWUJtl155PlayVehicle(intval($player));
            return;
};

// Relocated from CardEffects.php: the caster's own "then play a Vehicle from hand, -3" follow-up
// (shared by the 2-player inline path and the Twin Suns per-opponent continuation JTL_155#OPP).
function _SWUJtl155PlayVehicle(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (SWUHandPlayablesAtDiscount($player, ['Unit'], 3) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID, 'Vehicle')) $targets[] = $mz;
    }
    if (empty($targets)) return; // no affordable Vehicle in hand → only the TIEs were created
    SWUQueueChooseTarget($player, $targets, "Play_a_Vehicle_unit_(costs_3_less)", "JTL_155#0");
}
