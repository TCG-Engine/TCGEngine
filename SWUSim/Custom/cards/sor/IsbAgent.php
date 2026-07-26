<?php
// SOR_176  |  Reprints: SEC_184
// Cost 1 - ISB Agent - [Cunning,Villainy] - Power 1 - HP 3
// Text: When Played: You may reveal an event from your hand. If you do, deal 1 damage to a unit.

// SOR_176 ISB Agent — When Played: you may reveal an event from your hand. If you do, deal 1 to a unit.
// Single MZMAYCHOOSE; gated on having an event to reveal. The reveal is the commitment, so it
// happens in the SOR_176 handler only when the player actually picks a target.
$whenPlayedAbilities["SOR_176:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (empty(ZoneSearch("myHand", ["Event"]))) return;   // nothing to reveal → ability does nothing
    $targets = SWUAllUnits();
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Reveal_an_event_from_your_hand_to_deal_1_damage?", "Deal_1_damage_to_a_unit", "SOR_176#0");
};

$customDQHandlers["SOR_176#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return; // declined → no reveal, no damage
    global $playerID;
    $playerID = intval($player);
    $events = ZoneSearch("myHand", ["Event"]);
    if (!empty($events)) {
        DoRevealCard(intval($player), $events[0]);
        // SEC_016 Padmé "When you reveal … 1 or more cards from your hand" — a non-disclose hand reveal
        // must fire her react too (fires once per reveal event; no-op when no Padmé is in play).
        if (function_exists('_SWUSec016React')) _SWUSec016React(intval($player));
    }
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
};

// SEC_184 ISB Agent — When Played: you may reveal an event from your hand. If you do, deal 1 to a unit.
// Identical card to SOR_176 (reprint) — route through the shared SOR_176#0 continuation so the reveal
// actually happens (DoRevealCard) and fires SEC_016 Padmé's "when you reveal from hand" react. Previously
// it skipped the reveal entirely and dealt via a bare DEAL_UNIT_DAMAGE, so Padmé never triggered.
$whenPlayedAbilities["SEC_184:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (empty(ZoneSearch("myHand", ["Event"]))) return;   // nothing to reveal → ability does nothing
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Reveal_an_event_from_your_hand_to_deal_1_damage?", "Deal_1_damage_to_a_unit", "SOR_176#0");
};
