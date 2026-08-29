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
    // ⚠ SWUWithNestedActionFrame CANNOT express that here, and this is the general limit of the depth
    // primitive: BeginSWUAttack is ASYNCHRONOUS. It queues the attack and returns, so the frame has
    // already exited by the time _SWUCombatFinishAction reaches the after-action — measured at depth=0.
    // Depth is a within-request property; anything that finishes in a later drain needs a PERSISTED
    // marker instead, which is exactly what this flag is (and why the deferred nested-play leg needs the
    // gamestate close-stamp rather than depth). Keeping the flag.
    SetSWUVar('SWU_SUPPRESS_AFTERACTION', '1');
    BeginSWUAttack(intval($player), $lastDecision);
};
