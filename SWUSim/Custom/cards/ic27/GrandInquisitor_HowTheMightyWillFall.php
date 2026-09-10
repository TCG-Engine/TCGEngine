<?php
// IC27_103
// Cost 4 - Grand Inquisitor - How The Mighty Will Fall - [Aggression,Villainy] - Unit (Ground) 3/6
// Traits: Force, Imperial, Inquisitor - unique
// Text: While this unit is damaged, he gains Raid 3 and Saboteur.
//
// No registrations — both grants are keyword wiring, read live on every keyword query:
//   * Raid 3   → a case in GetConditionalKeyword_Raid_Value (KeywordEffects.php), which the generated
//                GetKeyword_Raid_Value ADDS to every other Raid source (Raid stacks — user ruling
//                2026-09-10, CR 7.5.8.b — so a granted Raid 2 makes him Raid 5);
//   * Saboteur → a case in HasConditionalKeyword_Saboteur's self-conditional switch.
// Both call _SWUIc27103Active so the condition cannot drift between the two halves.
//
// "Damaged" = any damage on him (Damage > 0). Nothing is snapshotted: healing him to 0 drops both
// keywords, and damage taken on the opponent's turn grants them on his next attack.
// A blanked Inquisitor (loses all abilities) gains neither — the generated readers check
// SWUKeywordSuppressed before any conditional, so no LostAbilities test is needed here.
// Tests: SWUSim/Tests/Cases/ic27/GrandInquisitor_HowTheMightyWillFall.md

function _SWUIc27103Active($obj): bool {
    return $obj !== null && ($obj->CardID ?? '') === 'IC27_103' && intval($obj->Damage ?? 0) > 0;
}
