<?php
// HMW_240
// Cost 3 - Sandstorm - [Cunning] - Event - Traits: Disaster - NON-unique
// Text: While you control a Tatooine base, this event costs [1 resource] less to play.
//       Choose an arena, Give a Weakness token to each exhausted enemy unit in that arena.
//
// ⚠ THE COST DISCOUNT IS NOT IN THIS FILE. It is registered as $playCostModifiers["HMW_240"] in
// GameLogic.php beside SHD_182 / LAW_179 / TS26_71, because $playCostModifiers is initialized AFTER
// cards/_loader.php runs — a registration written here would be silently wiped and the discount would
// never apply, with nothing to show for it.

// "Choose an arena" is a mandatory PARAMETER, not a modal of effects and not a "you may": both options
// are always offered, and picking the arena with nothing eligible in it is a legal (if bad) play. That
// is the opposite of HMW_221 Teeka, where a mode whose pool is empty is filtered off the menu — there
// the labels ARE the effects, here they are just a target scope.
$whenPlayedAbilities["HMW_240:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Ground&Space", 1,
        tooltip: "Choose_an_arena");
    DecisionQueueController::AddDecision($player, "CUSTOM", "HMW_240#0", 1);
};

$customDQHandlers["HMW_240#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $arena = ($lastDecision === 'Space') ? 'SpaceArena' : 'GroundArena';

    // "each exhausted ENEMY unit in that arena" — three restrictions, all load-bearing:
    //   • ENEMY  → only their<Arena>, never the caster's own units (Sandstorm is a Disaster, but this
    //     one is pointed outward). ZoneSearch fans "their…" across every live opponent at 3+ seats, so
    //     this is already the Twin Suns loop and needs no hand-rolled seat enumeration.
    //   • EXHAUSTED → Status 0. ⚠ 1 = ready, 0 = exhausted; the sense is easy to invert.
    //   • IN THAT ARENA → only the chosen one.
    $targets = [];
    foreach (ZoneSearch("their{$arena}", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->Status ?? 0) !== 0) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;

    // Attach EVERY token first, then sweep for shrink-defeats ONCE. Weakness is -1/-1, so its HP half
    // is HP reduction and can drop a 1-HP unit to zero — but nothing defeats it automatically, and
    // sweeping per target would compact the arena mid-loop and strand every later mzID (the multi-unit
    // debuff loop-shift family; SWUGiveSplitWeakness carries the same note for HMW_071 Ravage).
    // No once-per-unit clause here, so an already-weakened unit correctly gains a second token.
    foreach ($targets as $mz) DoGiveTokenUpgrade(intval($player), $mz, 'HMW_T02');
    SWUCheckShrinkDefeats();
};
