<?php
// IC27_013
// Cost 1 - Nute Gunray - Ring Ring, Space Calling - [Vigilance][Villainy] - Unit (Ground) 1/4
// Traits: Separatist, Official - Unique
// Text: Each other friendly Separatist unit gains Restore 1. (When it attacks, heal 1 damage from your base.)
//
// A CONSTANT ability, modelled exactly as HMW_039 Mother Talzin ("Each other friendly unit gains Restore 1"):
// summed into GetConditionalKeyword_Restore_Value (KeywordEffects) and recomputed from the live board on every
// read, so it ends the instant Nute is defeated or blanked.
//
// ⚠ PREVIEW SET: IC27 is not in card-specific-rulings.md, so the readings below are CR + analogue.
//   • OTHER — excluded by UniqueID, not CardID: uniqueness is per PLAYER, so a Team Suns teammate may control a
//     second Nute, and each grants to the other.
//   • FRIENDLY — the controller's team (self + teammates), relative to Nute's CONTROLLER, not his owner.
//   • SEPARATIST — the receiving unit's live traits (TraitContains), tokens and deployed leaders included.
//   • A COUNT, not a flag: CR 7.9.b adds Restore from different sources, so two friendly Nutes give Restore 2.
//     (HMW_039 returns a flag; two Talzins on one team would under-heal — noted there, not changed.)
//   • A BLANKED Nute grants nothing (LostAbilities); a blanked RECEIVER is handled by the generic suppression
//     gate in GetKeyword_Restore_Value.

// How many OTHER friendly (team-wide), un-blanked Nute Gunrays grant Restore 1 to $obj.
function _SWUIc27013RestoreGrants($obj): int {
    if ($obj === null || !empty($obj->removed)) return 0;
    if (!TraitContains($obj, 'Separatist')) return 0;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return 0;
    $selfUID = intval($obj->UniqueID ?? -1);
    $n = 0;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
        foreach (GetUnitsInPlay(intval($seat)) as $u) {           // already skips removed units
            if (($u->CardID ?? '') !== 'IC27_013') continue;
            if (intval($u->UniqueID ?? -2) === $selfUID) continue;   // "OTHER" — never himself
            if (LostAbilities($u)) continue;                          // a blanked source grants nothing
            $n++;
        }
    }
    return $n;
}
