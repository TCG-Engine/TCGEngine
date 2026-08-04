<?php
// IC27_071
// Cost 2 - Avar Kriss - For Light and Life - [Command,Heroism] - Power 0 - HP 5
// Text: Raid 1 (This unit gets +1/+0 while attacking.) / This unit gains Raid 1 for each other friendly unit.
//
// No registrations here — both halves are keyword wiring:
//   * her PRINTED Raid 1 is auto-derived by the generator ($Raid_Cards => 1);
//   * the per-other-friendly-unit bonus is a case in GetConditionalKeyword_Raid_Value
//     (KeywordEffects.php), which the generated GetKeyword_Raid_Value ADDS to the printed value
//     (same shape as SEC_171 Punishing One's "Raid 1 for each damaged enemy unit").
// Tests: SWUSim/Tests/Cases/ic27/AvarKriss_ForLightAndLife.md
