<?php
// LOF_193
// Cost 2 - Youngling Padawan - [Cunning,Heroism] - Power 2 - HP 3
// Text: When Played: The Force is with you (create your Force token).

// ── LOF Force-creation units (Phase 2) — each just creates the controller's Force token. ────────────
// LOF_193 Youngling Padawan — When Played.
$whenPlayedAbilities["LOF_193:0"] = function($player, $mzID) { TheForceIsWithYou(intval($player)); };
