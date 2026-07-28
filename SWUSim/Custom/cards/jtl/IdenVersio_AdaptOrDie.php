<?php
// JTL_036
// Cost 4 - Iden Versio - Adapt or Die - [Vigilance,Villainy] - Power 4 - HP 3 - Upgrade Power 3 - Upgrade HP 3
// Text: Shielded / Piloting [3 resources Vigilance Villainy] / When this upgrade attaches to a unit: Give a Shield token to that unit.

// ── JTL_036 Iden Versio — OnAttached ─────────────────────────────────────────
// "When this upgrade attaches to a unit: Give a Shield token to that unit."
// $mzID is the host unit's arena mzID. The unit-side Shielded keyword does NOT fire here —
// JTL_036 enters as an upgrade (pilot), not as a unit.
$onAttachedAbilities["JTL_036:0"] = function($player, $mzID) {
    GiveShieldToken(intval($player), $mzID);
};
