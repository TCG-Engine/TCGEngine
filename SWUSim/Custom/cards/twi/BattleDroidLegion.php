<?php
// TWI_235
// Cost 9 - Battle Droid Legion - [Villainy] - Power 6 - HP 5
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / When Defeated: Create 3 Battle Droid tokens.

// TWI_235 Battle Droid Legion — "Exploit 2. When Defeated: Create 3 Battle Droid tokens."
$whenDefeatedAbilities["TWI_235:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'TWI_T01', 3);
};
