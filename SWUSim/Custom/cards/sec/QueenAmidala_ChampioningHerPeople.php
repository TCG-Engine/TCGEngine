<?php
// SEC_101
// Cost 5 - Queen Amidala - Championing Her People - [Command,Heroism] - Power 5 - HP 3
// Text: When Played: Create 2 Spy tokens. / If damage would be dealt to this unit, you may defeat another friendly unit that shares a trait with this unit. If you do, prevent that damage.

// SEC_101 Queen Amidala — When Played: create 2 Spy tokens. (The interactive damage prevention is a
// passive replacement hooked in SWUDealDamageToUnit + SWUCombatDamage; see _SWUAmidalaPreventTargets.)
$whenPlayedAbilities["SEC_101:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 2);
};
