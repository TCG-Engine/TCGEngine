<?php
// HMW_232 Mon Cal Cruiser — Unit (Space) 4/7, cost 6, [Cunning], Rebel/Vehicle/Capital Ship.
// "When Played: Choose one: Attack with a unit. It gets +2/+0 for this attack. —or— Look at an opponent's
//  hand. You may discard a card from it. If you do, they draw a card."
// Mode 1 is SOR_220 Surprise Strike's shape (a READY friendly unit; the Cruiser enters exhausted). Mode 2
// is HMW_205 Intelligence Agency's text. A mode that can do nothing is not offered; one mode left resolves
// without the OPTIONCHOOSE. Mode 2 filters to opponents holding a card, exactly as HMW_205 does.

$whenPlayedAbilities["HMW_232:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $modes = [];
    if (!empty(_SWUHmw232Attackers(intval($player)))) $modes[] = 'Attack';
    if (!empty(SWUOpponentsWithCards(intval($player)))) $modes[] = 'LookAtHand';
    if (empty($modes)) return;
    if (count($modes) === 1) { _SWUHmw232Resolve(intval($player), $modes[0]); return; }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", implode('&', $modes), 1, tooltip: "Choose_one");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_232#0", 1);
};

if (!function_exists('_SWUHmw232Attackers')) {
    function _SWUHmw232Attackers(int $player): array {
        $out = [];
        foreach (SWUAllUnits('my') as $mz) {
            $o = GetZoneObject($mz);
            if (!SWUObjGone($o) && intval($o->Status ?? 0) === 1) $out[] = $mz;
        }
        return $out;
    }
    function _SWUHmw232Resolve(int $player, string $mode): void {
        global $playerID; $playerID = $player;
        if ($mode === 'Attack') {
            SWUQueueChooseTarget($player, _SWUHmw232Attackers($player), "Attack_with_a_unit_(+2/+0)", "HMW_232#1");
        } elseif ($mode === 'LookAtHand') {
            SWUQueueChooseOpponent($player, 'HMW_232#2', "Look_at_which_opponent's_hand?", SWUOpponentsWithCards($player));
        }
    }
}

$customDQHandlers["HMW_232#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    _SWUHmw232Resolve(intval($player), (string)$lastDecision);
};

$customDQHandlers["HMW_232#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    if (SWUObjGone(GetZoneObject((string)$lastDecision))) return;
    SWUAddAttackPowerBonus((string)$lastDecision, 2);
    BeginSWUAttack(intval($player), (string)$lastDecision);
};

$customDQHandlers["HMW_232#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    $targets = SWULookAtOpponentHand(intval($player), null, $opp);
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Discard_a_card_from_their_hand?", "Discard_a_card_from_their_hand?", "HMW_205#0");
};
