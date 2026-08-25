<?php
// ⚠ Target pools use AnyUnitFilter (Unit + TOKEN Unit + Leader Unit): this card's text is
// unqualified, and a hand-built ["Unit","Leader Unit"] filter silently excluded token units
// (the Open Fire bug report, 2026-08-13 — a whole family of six files had the same miss).
// SOR_169
// Keep Fighting
// Text: Ready a unit with 3 or less power.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_169:0"] = function($player, $mzID = '') {
// Keep Fighting — "Ready a unit with 3 or less power."
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $obj = GetZoneObject($mz);
                if (SWUObjGone($obj)) continue;
                if (ObjectCurrentPower($obj) <= 3) $targets[] = $mz;
            }
            if (empty($targets)) return;
            // ⚠ The continuation's BLOCK must not undercut the choose: AddDecision inserts in block
            // order, so a block-0 READY_UNIT behind a block-1 MZCHOOSE ran FIRST, on a stale answer —
            // the two-target path never readied anything (caught by the token-target guard, which was
            // the first test to ever offer two legal targets).
            if (count($targets) === 1) {
                DecisionQueueController::AddDecision($player, "PASSPARAMETER", $targets[0], 0);
                DecisionQueueController::AddDecision($player, "CUSTOM", "READY_UNIT", 0);
            } else {
                $targetStr = implode("&", $targets);
                DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, tooltip:"Choose_a_unit_to_ready");
                DecisionQueueController::AddDecision($player, "CUSTOM", "READY_UNIT", 1);
            }
            return;
};
