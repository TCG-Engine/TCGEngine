<?php
// SHD_148
// Cost 3 - Cassian Andor - Rebellions Are Built On Hope - [Heroism,Aggression] - Power 3 - HP 5
// Text: Smuggle [5 resources Aggression Heroism] (If this card is a resource, you may play him for his smuggle cost. Replace it with the top card of your deck.) / When played using Smuggle: Ready this unit.

// ─── SHD_148 Cassian Andor ────────────────────────────────────────────────────
// When played using Smuggle: Ready this unit (normal smuggle entry is exhausted).
$whenPlayedUsingSmuggleAbilities["SHD_148:0"] = function($player, $mzID) {
    OnReadyCard(intval($player), $mzID);
};
