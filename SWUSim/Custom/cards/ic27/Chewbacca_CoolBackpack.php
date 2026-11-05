<?php
// IC27_162
// Cost 6 - Chewbacca - Cool Backpack - [Cunning,Heroism] - Unit (Ground) 4/6
// Traits: Rebel, Wookiee - Unique
// Text: Sentinel. While this unit is damaged, he gets +4/+0.
//
// LOF_233 Scimitar's shape. ObjectCurrentPower's per-identity self-buff switch (GameLogic.php) adds this on every
// read, so the bonus starts with the first damage counter and ends when he is healed to 0; that switch is skipped for
// a blanked unit. Power only — HP is untouched. Sentinel is the generated keyword.
// Tests: SWUSim/Tests/Cases/ic27/Chewbacca_CoolBackpack.md

function _SWUIc27162Bonus($obj): int {
    return ($obj !== null && intval($obj->Damage ?? 0) > 0) ? 4 : 0;
}
