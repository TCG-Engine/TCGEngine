<?php
// HMW_124
// Cost 7 - Luminara Unduli - Besieged General - [Command,Heroism] - Power 7 - HP 7 - Ground - Force, Jedi, Republic
// Text: When you play a unit (including this one): You may attack with a unit. It gets +2/+0 for this attack.

// Reactive own-play observer: armed in SWUCollectOwnPlayReactions (GameLogic.php) and dispatched by the
// DispatchTrigger 'HMW_124' case, so it rides the played unit's own entry-trigger flush.
//
// "(including this one)" = NO self-exclusion — the UniqueID compare every "another unit" observer does
// (HMW_115, TWI_184, LOF_087, …) is deliberately absent, so playing Luminara herself triggers her.
// The trigger fires on a UNIT play only: events and upgrades don't arm it, and neither does a Piloting
// card played as a pilot (CR 17.c — it is an upgrade for that purpose; the collector's $playedAsUpgrade
// flag). Creating a token is not playing, so token creation doesn't arm it either.
//
// ⚠ Known family gap (NOT introduced here): the other "when you play a unit" / "when you play an upgrade"
// observers in SWUCollectOwnPlayReactions still read the raw $isUnit/$isUpgrade, so a pilot-as-upgrade
// play is a unit play to them and is not an upgrade play. Only HMW_124 honours $playedAsUpgrade — the
// family-wide sweep is deliberately deferred pending a ruling, since it changes ~15 shipped cards.
//
// "Attack with a unit" carries no "even if it's exhausted" clause, so the attacker pool is every friendly
// unit that could declare an attack RIGHT NOW: _SWUUnitCanAttackNow enforces ready + the printed/granted
// can't-attack restrictions + having at least one legal target, at SELECTION time (a zero-effect pick is
// never offered). The unit that was just played is naturally absent from it — it entered exhausted —
// while Luminara herself IS eligible whenever she is ready, because the attacker is "a unit", not
// "another unit". With no eligible attacker the ability raises no prompt at all.
function LuminaraUnduliAttackOffer(int $player): void {
    global $playerID; $playerID = $player;
    $units = [];
    foreach (['myGroundArena' => 'GroundArena', 'mySpaceArena' => 'SpaceArena'] as $zone => $arena) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            if (_SWUUnitCanAttackNow($player, $arr[$i], $arena)) $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) return;
    SWUQueueMayChooseTarget($player, $units, "Attack_with_a_unit_(+2/+0)?",
        "Choose_a_unit_to_attack_with", "HMW_124#0");
}

// "+2/+0 for THIS attack" is SWUAddAttackPowerBonus — the one-shot SWU_ATK_POWER_2 marker SWUCombatDamage
// consumes — NOT a phase buff (which would linger to the regroup). This continuation resolves inside the
// played unit's entry-trigger resolution, so the play's flow / combat owns the After Action: no
// SWUAfterAction on any branch (same shape as IBH_064 Hoth Lieutenant's When Played attack).
$customDQHandlers["HMW_124#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // MZMAYCHOOSE decline — nothing happens
    global $playerID; $playerID = intval($player);
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);
};
