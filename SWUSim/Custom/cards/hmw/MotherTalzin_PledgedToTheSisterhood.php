<?php
// HMW_039
// Cost 3 - Mother Talzin - Pledged to the Sisterhood - [Vigilance][Aggression] - Unit (Ground) 3/4
// Traits: Force, Night - Unique
// Text: Raid 1
//       Each other friendly unit gains Restore 1.
//
// Raid 1 is generated (GeneratedKeywordCode carries 'HMW_039' => 1) — nothing to write for it.
//
// The aura is a CONSTANT ability, so it lives in GetConditionalKeyword_Restore_Value (KeywordEffects)
// and is recomputed from the live board on every read rather than stamped onto allies when they
// arrive. That is what makes it end the instant Talzin is defeated or blanked, with no bookkeeping.
// Its textual twins are SEC_047 Coronet and SOR_102 Home One, which sum into the same value.
//
// ⚠ PREVIEW SET: HMW is not in card-specific-rulings.md, so the readings below are CR + analogue.
//
// "EACH OTHER FRIENDLY UNIT"
//   • OTHER — Talzin is excluded from her own aura, by UniqueID rather than by CardID. That
//     distinction is not academic in Team Suns: uniqueness is per PLAYER, so a teammate may control a
//     second Talzin, and each grants Restore 1 to the other. A CardID exclusion would silently drop
//     that.
//   • FRIENDLY — spans the TEAM, unlike "you control", which is self-only. So a teammate's Talzin
//     grants to your units, and Restore then heals the ATTACKING unit's own base.
//   • No arena, trait or type qualifier — ground and space units alike.
//   • A BLANKED Talzin grants nothing (LostAbilities), the same rule the shared friendly-unit grant
//     loop in KeywordEffects already applies to every other granting unit.
//
// ⚠ FAMILY NOTE — RAISED, NOT SWEPT. SEC_047 Coronet, SOR_102 Home One and TS26_40 Obi-Wan print the
// same word "friendly" but are implemented SELF-ONLY: they are cases inside
// `foreach (GetUnitsInPlay($obj->Controller) as $u)`, which never looks at a teammate's board. If the
// team reading is right for HMW_039 it is right for them too, and the same shape recurs in the
// Overwhelm/Raid grant loops. Widening that shared loop would touch every case in it — including ones
// whose text says "you control" — so it is a family-wide design call, deliberately left for the user
// rather than swept in mid-card. HMW_039 is correct on its own terms today.

// True when some OTHER friendly (team-wide) unit is an un-blanked Mother Talzin.
function _SWUHmw039GrantsRestore($obj): bool {
    if ($obj === null || !empty($obj->removed)) return false;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return false;
    $selfUID = intval($obj->UniqueID ?? -1);
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {
        foreach (GetUnitsInPlay(intval($seat)) as $u) {
            if (!empty($u->removed)) continue;
            if (($u->CardID ?? '') !== 'HMW_039') continue;
            if (intval($u->UniqueID ?? -2) === $selfUID) continue;   // "OTHER" — never herself
            if (LostAbilities($u)) continue;                          // a blanked source grants nothing
            return true;
        }
    }
    return false;
}
