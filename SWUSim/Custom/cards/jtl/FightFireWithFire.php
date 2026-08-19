<?php
// JTL_173
// Cost 1 - Fight Fire With Fire - [Aggression]
// Text: Choose a friendly unit and an enemy unit in the same arena. If you do, deal 3 damage to each of them.

// ── JTL_173 Fight Fire With Fire (event) — friendly chosen ($lastDecision); pick the same-arena enemy,
// then deal 3 to each. ───────────────────────────────────────────────────────────────────────────────
$customDQHandlers["JTL_173#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $arena = (strpos($lastDecision, 'Space') !== false) ? 'Space' : 'Ground';
    $enemies = ZoneSearch("their{$arena}Arena", AnyUnitFilter);
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies,
        "Choose_an_enemy_unit_in_the_same_arena_(3_damage_to_it_AND_to_your_chosen_unit)",
        "JTL_173#1|" . $lastDecision);
};

$customDQHandlers["JTL_173#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
    global $playerID;
    $playerID = intval($player);
    $friendlyMz = $parts[0] ?? '';
    SWUDealDamageToUnit($lastDecision, 3, intval($player)); // enemy (their arena — defeat shifts only their indices)
    if ($friendlyMz !== '') SWUDealDamageToUnit($friendlyMz, 3, intval($player)); // friendly
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_173:0"] = function($player, $mzID = '') {
// Fight Fire With Fire — choose a friendly unit and an enemy unit in the SAME
                          // arena; deal 3 to each. Offer only friendly units in an arena that has an
                          // enemy unit, then pick the same-arena enemy (continuation JTL_173 → #1).
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (['Ground', 'Space'] as $a) {
                if (empty(ZoneSearch("their{$a}Arena", AnyUnitFilter))) continue;
                foreach (ZoneSearch("my{$a}Arena", AnyUnitFilter) as $mz) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets,
                "Choose_a_friendly_unit_(deal_3_to_it_and_a_same-arena_enemy)", "JTL_173#0");
            return;
};
