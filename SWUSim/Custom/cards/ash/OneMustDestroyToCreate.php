<?php
// ASH_247
// Cost 3 - One Must Destroy to Create - [Villainy]
// Text: Defeat a friendly non-leader unit. Then, you may play that unit from your discard pile for free.

// ASH_247 One Must Destroy to Create — defeat the chosen friendly non-leader unit, then offer to play that
// same unit (now in the discard) for free.
$customDQHandlers["ASH_247#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID ?? '';
    SWUDefeatUnit(intval($player), $lastDecision);   // → goes to controller's discard (+ WhenDefeated etc.)
    DecisionQueueController::CleanupRemovedCards();
    // Confirm the card actually reached the discard (it owns it), then offer the free play.
    $found = false;
    foreach (GetDiscard(intval($player)) as $d) {
        if ($d !== null && empty($d->removed) && ($d->CardID ?? '') === $cardID) { $found = true; break; }
    }
    if (!$found) return;
    // The defeat above already flushed the unit's When Defeated trigger (a RESOLVE_TRIGGER at block
    // $gTriggerDepth = 1). Per CR the EVENT resolves fully — defeat AND this replay — before that
    // triggered ability resolves, so a self-replayed unit (ASH_191) is back in play and targetable by
    // its own When Defeated. Queue the replay at block 0 so it drains BEFORE that trigger. (Globals reset
    // per request, so the ordering must live in the persisted decision queue, not a defer flag.)
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 0, tooltip: "Play_" . GameLogCardRef($cardID) . "_from_your_discard_for_free?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_247#1|{$cardID}", 0);
};

$customDQHandlers["ASH_247#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;   // declined
    $cardID = $parts[0] ?? '';
    if ($cardID === '') return;
    // Find the LAST non-removed discard entry matching the defeated unit's CardID (most recently added).
    $discard = GetDiscard(intval($player));
    $idx = -1; $c = 0;
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if (($discard[$i]->CardID ?? '') === $cardID) $idx = $c;
        $c++;
    }
    if ($idx < 0) return;
    ActivateCard(intval($player), "myDiscard-{$idx}", false, 99);   // free (via canonical play)
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_247:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $units[] = $mz;
        }
    }
    if (empty($units)) return;   // no friendly non-leader unit → fizzle
    SWUQueueChooseTarget(intval($player), $units, "Defeat_a_friendly_non-leader_unit", "ASH_247#0");
};
