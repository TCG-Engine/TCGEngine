<?php
// JTL_181
// Planetary Bombardment
// Text: Deal 8 indirect damage to a player. If you control a Capital Ship unit, deal 12 indirect damage instead.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_181:0"] = function($player, $mzID = '') {
// Planetary Bombardment — "Deal 8 indirect damage to a player. If you control a
                          // Capital Ship unit, deal 12 indirect damage instead."
            global $playerID;
            $playerID = intval($player);
            $cap = false;
            foreach (GetUnitsInPlay(intval($player)) as $u) { if (HasTrait($u->CardID ?? '', 'Capital Ship')) { $cap = true; break; } }
            SWUDealIndirectToChosenPlayer(intval($player), $cap ? 12 : 8);
            return;
};
