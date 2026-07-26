<?php
// TS26_59
// Cost 3 - Brothers - [Command]
// Text: Attack with up to 2 unique units (one at a time). Prevent all combat damage that would be dealt to each of them for these attacks.

// Count-capped loop; _SWUTs26059Offer (GameLogic.php) offers each attack and decrements SWU_TS26059_LOOP.
$whenPlayedAbilities["TS26_59:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SetSWUVar('SWU_TS26059_LOOP', '2');   // "{remaining}" — no excludes yet
    _SWUTs26059Offer(intval($player));
};
