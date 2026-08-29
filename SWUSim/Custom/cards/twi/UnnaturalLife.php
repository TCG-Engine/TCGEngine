<?php
// TWI_189
// Cost 3 - Unnatural Life - [Cunning,Villainy]
// Text: Play a unit that was defeated this phase from your discard pile. It costs 2 resources less and enters play ready. At the start of the regroup phase, defeat it.

// TWI_189 Unnatural Life (event continuation) — play the chosen defeated-this-phase discard unit at -2,
// enter ready, and mark it to be defeated at the next regroup (SWU_SNEAK_DEFEAT).
$customDQHandlers["TWI_189#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !preg_match('/myDiscard-(\d+)/', (string)$lastDecision, $m)) return;
    global $playerID; $playerID = intval($player);
    SWUNestedPlay(intval($player), $lastDecision, false, 2);   // nested: outer event owns the after-action // via canonical play
    $newMz = $GLOBALS['gLastPlayedMzID'];
    if ($newMz === '' || $newMz === null) return;
    $o = GetZoneObject($newMz);
    if ($o !== null) { $o->Status = 1; }            // enters play ready
    AddTurnEffect($newMz, 'SWU_SNEAK_DEFEAT');       // defeated at the start of the next regroup
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_189:0"] = function($player, $mzID = '') {
// Unnatural Life — "Play a unit that was defeated this phase from your discard
                          // pile. It costs 2 resources less and enters play ready. At the start of the
                          // regroup phase, defeat it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)
                    && GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . ($o->CardID ?? '')) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_defeated_this_phase_(-2,_ready,_defeated_at_regroup)", "TWI_189#0");
            return;
};
