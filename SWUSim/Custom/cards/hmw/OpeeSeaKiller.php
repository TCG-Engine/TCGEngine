<?php
// HMW_090
// Cost 5 - Opee Sea Killer - [Vigilance] - Unit (Ground) 5/6 - Trait: Creature
// Text: While you control a Naboo base, this unit gains Grit. (This unit gets +1/+0 for each damage on it.)

// No card code here. The conditional Grit is a self-grant: `case 'HMW_090'` in HasConditionalKeyword_Grit
// (Custom/KeywordEffects.php), read live off the CONTROLLER's base trait via _SWUControlsBaseWithTrait —
// the same shape as HMW_084 Gunga City Guard's Naboo-base Shielded. The generator correctly leaves HMW_090
// out of $Grit_Cards (the keyword is conditional, not printed).
