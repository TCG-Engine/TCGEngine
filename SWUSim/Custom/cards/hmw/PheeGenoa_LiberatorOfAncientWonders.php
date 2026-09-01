<?php
// HMW_214
// Cost 4 - Phee Genoa - Liberator of Ancient Wonders - [Cunning,Heroism] - Unit - Power 5 - HP 4
// Traits: Underworld - UNIQUE
// Text: Hidden
//       When an enemy leader deploys: Its controller may pay [2 resources]. If they don't, exhaust
//       that leader.
//
// Hidden is keyword-only and auto-wired from the generated registry — nothing to do for it here.
//
// The observer itself is armed in SWUDeployLeader (see the note there, beside the JTL_191 hook it
// mirrors). This file holds only what happens once it fires.
//
// WHO DECIDES: "ITS controller" is the leader's controller, i.e. the player who just deployed and is
// therefore the ACTIVE player. Phee's own controller never chooses anything, which is what makes this
// card's cross-player shape simple — the decision lands on the queue of a player who is already acting,
// so none of the usual non-active-player drain problems apply.
//
// A deployed leader unit enters play READY (AddGroundArena/AddSpaceArena with Status:1), so exhausting
// it is a genuine cost and paying 2 is a genuine choice.

// $mzID carries "{leaderIndex},{reactingSeat}" — the leader's DeployedUniqueID does not exist yet when
// the trigger is armed, and the reacting seat is needed to resolve the leader unit in the right frame.
// ⚠ COMMA-delimited, never pipe: the trigger dispatcher splits a queued trigger's params on '|' before
// this function is called, so a pipe would silently drop everything after the first field.
function Hmw214EnemyDeployTrigger($player, $mzID): void {
    global $playerID; $playerID = intval($player);
    $bits    = explode(',', (string)$mzID);
    $idx     = intval($bits[0] ?? 0);
    $reactor = intval($bits[1] ?? 0);
    $leader  = SWUGetLeaderByIndex(intval($player), $idx);
    if ($leader === null) return;
    $uid = intval($leader->DeployedUniqueID ?? 0);
    // Deployed as a PILOT: the leader is a Subcard on a Vehicle, not an arena unit, so there is no
    // leader unit to exhaust and the leader card itself is already exhausted by the deploy. No-op.
    if ($uid <= 0) return;

    // "May pay 2" is an ordinary cost, so Credits and SEC_122 Droids pay it (CR 3.13) — the gate is
    // TOTAL PAYMENT CAPACITY, never a bare ready-resource count. A player who cannot reach 2 is not
    // asked a question they cannot act on: the "if they don't" branch resolves immediately.
    if (SWUTotalPaymentCapacity(intval($player)) < 2) {
        _SWUHmw214ExhaustLeader($reactor, $uid);
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Pay_2_resources_or_this_leader_becomes_exhausted?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_214#0|{$uid}|{$reactor}", 1);
}

$customDQHandlers["HMW_214#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid     = intval($parts[0] ?? 0);
    $reactor = intval($parts[1] ?? 0);
    if (strtoupper((string)$lastDecision) === 'YES') {
        SWUPayInlineAbilityCost(intval($player), 2);
        return;
    }
    _SWUHmw214ExhaustLeader($reactor, $uid);
};

// The exhaust is caused by PHEE — an enemy card ability — so it is performed AS the reacting seat, not
// as the leader's own controller. That matters twice over: it is what lets "can't be exhausted by enemy
// card abilities" (LOF_040 / LOF_073) refuse it, and OnExhaustCard resolves its mzID against the ambient
// frame, so the mz must be minted in the same frame it is spent in.
function _SWUHmw214ExhaustLeader(int $reactor, int $uid): void {
    if ($uid <= 0 || $reactor <= 0) return;
    global $playerID; $saved = $playerID; $playerID = $reactor;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) OnExhaustCard($reactor, $mz);
    $playerID = $saved;
}
