<?php
// IC27_037
// Cost 4 - Finn - Stand and Fight - [Vigilance,Heroism] - Unit (Ground) 4/5 (unique)
//   Traits: Resistance
// Text: Sentinel. While this unit is upgraded, he gains Restore 2.
//
// No registrations — Sentinel is printed (auto-wired), and the Restore 2 is one self-conditional line in
// GetConditionalKeyword_Restore_Value (KeywordEffects.php), read live through _SWUIsUpgraded: any non-captive
// upgrade counts, Shield and Experience tokens included; a captured card does not. A blanked Finn has no
// Restore at all — the generated GetKeyword_Restore_Value checks SWUKeywordSuppressed first.
// Tests: SWUSim/Tests/Cases/ic27/Finn_StandAndFight.md
