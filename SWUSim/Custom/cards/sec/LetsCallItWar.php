<?php
// SEC_180
// Cost 3 - Let's Call It War - [Aggression]
// Text: Deal 3 damage to a unit. Then, if you have the initiative, you may deal 2 damage to another unit in the same arena.

$customDQHandlers["SEC_180#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $first = GetZoneObject($lastDecision);
    $firstUID = ($first !== null) ? intval($first->UniqueID ?? 0) : 0;
    $isSpace = ($first !== null) && strpos((string)($first->Location ?? ''), 'Space') !== false;

    // The first damage can be DEFERRED by a replacement effect that queues a decision on the TARGET
    // CONTROLLER's queue (SEC_101 Queen Amidala's "defeat a trait-sharing friendly to prevent", ASH_062's
    // shield sacrifice). While that is pending the board is not final — the unit they are about to
    // sacrifice is still standing — so building the second target list NOW would offer a slot that is
    // about to vanish, and picking it silently throws the 2 damage away. Blocks only order within ONE
    // player's queue, so the only way to sequence behind their decision is to queue the offer-builder
    // onto THEIR queue, after the entries the damage just added. Detect that by watching their queue grow.
    // ⚠ The queue to watch belongs to the DAMAGED UNIT'S controller — a determined seat, since a
    // replacement effect (Amidala, The Mandalorian) is offered to whoever controls the unit taking the
    // damage. OtherPlayer() named seat 2, so above two seats this watched a bystander's queue: it never
    // grew, the deferral never happened, and the second 2 damage resolved against a board mid-change.
    $opp = SWUMzOwner((string)$lastDecision, intval($player));
    $qBefore = count(GetDecisionQueue($opp));
    SWUDealDamageToUnit($lastDecision, 3, intval($player));
    $playerID = intval($player);
    if (!PlayerHasIniative(intval($player))) return;
    if (count(GetDecisionQueue($opp)) > $qBefore) {
        // Deferred: build the offer once their replacement has resolved. $player rides in the payload
        // because the handler will run while the OPPONENT's queue is being drained.
        DecisionQueueController::AddDecision($opp, 'CUSTOM',
            "SEC_180#1|{$player}|{$firstUID}|" . ($isSpace ? '1' : '0'), 1, dontSkipOnPass: 1);
        return;
    }
    _SWULetsCallItWarSecondOffer(intval($player), $firstUID, $isSpace);
};

// Builds the "deal 2 to another unit in the same arena" offer from the CURRENT board. Called inline when
// the first damage resolved immediately, or from the opponent's queue once a replacement effect settled.
function _SWULetsCallItWarSecondOffer(int $player, int $firstUID, bool $isSpace): void {
    global $playerID; $playerID = $player;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 2, 'may' => true,
        'arena' => $isSpace ? 'Space' : 'Ground', 'excludeUID' => $firstUID,
        'question' => "Deal_2_to_another_unit_in_the_same_arena?", 'prompt' => "Deal_2_damage_to_a_unit",
    ]);
}

// Runs on the OPPONENT's queue after their replacement decision; $parts[0] is the real acting player.
$customDQHandlers["SEC_180#1"] = function($player, $parts, $lastDecision) {
    $actor    = intval($parts[0] ?? 0);
    $firstUID = intval($parts[1] ?? 0);
    $isSpace  = ($parts[2] ?? '0') === '1';
    if ($actor <= 0) return;
    global $playerID; $savedPID = $playerID;
    _SWULetsCallItWarSecondOffer($actor, $firstUID, $isSpace);
    $playerID = $savedPID;
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_180:0"] = function($player, $mzID = '') {
// Let's Call It War — "Deal 3 to a unit. Then, if you have the initiative, you
                          // may deal 2 to another unit in the same arena."
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Deal_3_to_a_unit", "SEC_180#0");
            return;
};
