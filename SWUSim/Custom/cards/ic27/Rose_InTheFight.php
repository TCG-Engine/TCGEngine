<?php
// IC27_040
// Cost 6 - Rose - In the Fight - [Vigilance,Heroism] - Unit (Ground) 6/6 (unique)
//   Traits: Resistance
// Text: Shielded. On Attack: You may give a Shield token to another Resistance unit.
//
// SOR_050 The Ghost's clause with a different trait. Shielded is printed (auto-wired). "another Resistance unit"
// has no "friendly": the pool is every Resistance unit on the table, enemy ones included, minus Rose herself (by
// UniqueID). An optional pick (MZMAYCHOOSE), so even a lone candidate can be declined.
// Tests: SWUSim/Tests/Cases/ic27/Rose_InTheFight.md
$onAttackAbilities["IC27_040:0"] = function($player, $mzID) {
    SWUOfferUnitTarget(intval($player), $mzID, [
        'traits' => 'Resistance', 'excludeSelf' => true, 'may' => true,
        'continuation' => 'GIVE_SHIELD', 'prompt' => 'Give_a_Shield_token_to_another_Resistance_unit',
    ]);
};
