<?php
// HMW_084
// Cost 2 - Gunga City Guard - [Vigilance] - Unit (Ground) 2/1 - Trait: Gungan
// Text: Restore 1 / While you control another Gungan unit or Naboo base, this unit gains Shielded.

// Restore 1 is a PRINTED keyword and is auto-registered by the generator ($Restore_Cards) — no code.
// The conditional Shielded is a self-grant: `case 'HMW_084'` in HasConditionalKeyword_Shielded
// (Custom/KeywordEffects.php). Shielded's token is granted only as the unit ENTERS play, so a later
// enabler makes the unit HAVE the keyword without granting a token retroactively.
