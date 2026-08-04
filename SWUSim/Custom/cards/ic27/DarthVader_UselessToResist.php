<?php
// IC27_067
// Cost 8 - Darth Vader - Useless to Resist - [Command,Villainy] - Power 8 - HP 8
// Text: Ambush / Each other friendly unit gains Ambush.
//
// No registrations here — both halves are keyword wiring:
//   * his OWN Ambush is auto-derived by the generator ($Ambush_Cards in GeneratedKeywordCode.php);
//   * the AURA is a case in HasConditionalKeyword_Ambush (KeywordEffects.php), inside the
//     "another friendly unit grants me a keyword" loop that already self-excludes by UniqueID
//     (same shape as SOR_079 Admiral Piett / SOR_100 Wedge Antilles).
// Tests: SWUSim/Tests/Cases/ic27/DarthVader_UselessToResist.md
