<?php
// SEC_082
// Cost 3 - Chancellor Palpatine - I Am the Senate - [Command,Villainy] - Power 2 - HP 2
// Text: When Played: If you control a leader unit, create 2 Spy tokens and give those tokens Sentinel for this phase. / Plot

// ── SEC Phase 2: Spy tokens ──────────────────────────────────────────────────
// SEC_082 Chancellor Palpatine — When Played: if you control a leader unit, create 2 Spy tokens and
// give those tokens Sentinel for this phase. (Plot keyword auto-wired; dormant when played from hand.)
$whenPlayedAbilities["SEC_082:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (!SWUControlsLeaderUnit(intval($player))) return;
    // "Create 2 Spy tokens" is ONE create-a-number-of-tokens instruction — use the batch API so ASH_094
    // Moff Jerjerrod's "you may defeat this unit → create twice that number" replacement is offered ONCE
    // (create 2 → 4), not per-token. The Sentinel turn-effect rides through the doubling to all tokens.
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 2, false, 'SENTINEL^SEC_082');
};
