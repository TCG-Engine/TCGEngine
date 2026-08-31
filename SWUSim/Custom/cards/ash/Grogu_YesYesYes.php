<?php
// ASH_155
// Cost 3 - Grogu - Yes. Yes. Yes. - [Aggression,Heroism] - Power 2 - HP 6
// Text: When you take the initiative: You may attack with a unit.

// ASH_155 Grogu — "When you take the initiative: you may attack with a unit." The attack is an action
// granted by a REACTIVE TRIGGER, so it resolves inside the initiative action rather than starting a new
// one: run it as a nested frame so its terminal after-action cannot swap the turn a second time.
$customDQHandlers["ASH_155#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    // USER RULING (2026-08-29): this is "an action you get from a REACTIVE TRIGGER to 'When you take
    // the initiative'" — it resolves inside the initiative action, so it must not swap the turn again.
    //
    // ⚠ NOTHING IS DONE HERE TO ENFORCE THAT, AND THAT IS THE POINT. This used to set a one-shot
    // SWU_SUPPRESS_AFTERACTION flag consumed in SWUAfterAction, because the close ledger could not see
    // the initiative claim's action: the claim OPENED an id but the pass swapped the turn without ever
    // closing it, so the bonus attack's own close was granted and swapped a SECOND time (at 3+ seats
    // that eats the next seat; at 2 it reads as a free extra action). SWUPassAction now stamps that id
    // closed, and the PASS reset moved under the same gate, so the duplicate close is refused
    // structurally — for every card, not just this one. Removed 2026-08-31; the four-seat sections in
    // Tests/Cases/ash/Grogu_YesYesYes_MultiSeatTurnOrder.md are what hold it.
    //
    // ⚠ SWUWithNestedActionFrame still cannot express it either, and that limit is unchanged:
    // BeginSWUAttack is ASYNCHRONOUS — it queues the attack and returns, so the frame has already
    // exited by the time _SWUCombatFinishAction reaches the after-action (measured at depth=0). Depth
    // is a within-request property; anything finishing in a later drain needs the PERSISTED stamp.
    BeginSWUAttack(intval($player), $lastDecision);
};
