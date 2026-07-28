<?php
// SEC_076
// Cost 4 - Charged with Murder - [Vigilance]
// Text: You may disclose VigilanceVigilance (reveal cards from your hand with these aspect icons among them). If you do, defeat a damaged non-leader unit.

// SEC_076 Charged with Murder (Event) — "if you do" effect: defeat a damaged non-leader unit.
$customDQHandlers["SEC_076#0"] = function ($player, $parts, $lastDecision) {
  SWUOfferUnitTarget($player, '', [
      'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
      'extraFilter' => fn($o) => intval($o->Damage ?? 0) > 0,
      'prompt' => "Defeat_a_damaged_non-leader_unit",
  ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_076:0"] = function($player, $mzID = '') {
{ // Charged with Murder — "You may disclose VigilanceVigilance ... If you do,
                          // defeat a damaged non-leader unit." (CR §38)
            SWUQueueDisclose(intval($player), ['Vigilance', 'Vigilance'], "SEC_076#0",
                "Disclose_VigilanceVigilance_to_defeat_a_damaged_non-leader_unit");
            return;
        }

        // ── LOF Events (Phase 13) ──────────────────────────────────────────────
};
