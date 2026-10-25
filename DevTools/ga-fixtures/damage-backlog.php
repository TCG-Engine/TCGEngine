<?php
/**
 * New damage-spell fixtures from the semantic backlog, written with the scenario builder (see Scenario.php).
 */

// --- Thunderclap: deal 4 damage to target ally (full 4-reserve cost below level 3) ---
// Thunderclap (0xm513tj3j, FIRE, reserve 4, fast): "[Level 3+] This card costs 2 less to activate. Deal 4 damage
// to target ally." The champion is level 0 here, so the discount does not apply and all four reserve payments are
// made. A Dungeon Guide (3 life) is seeded onto the opposing field as the target; 4 damage destroys it.
gaScenario('thunderclap-base-cost-four-damage-to-ally')
    ->standardDeck('Thunderclap')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Thunderclap')
    ->castSpell('Thunderclap', pay: 4, window: 'both', target: 'theirField-1')
    ->expectZoneCount('myMemory', 4, 1, 'Below level 3 the full 4 reserve is paid (4 cards in memory)')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The 3-life Dungeon Guide took 4 damage and was destroyed')
    ->expectProperty('p1Graveyard-0', 'CardID', '0xm513tj3j', 'Thunderclap resolved and went to the graveyard')
    ->expectZoneCount('theirField', 1, 1, 'Only the opposing champion is left on the field')
    ->expectDqEmpty()
    ->notes('Base-cost path of Thunderclap. The level-3 discount and the champion-is-not-a-legal-target path are separate fixtures.')
    ->covers(['cost', 'targeting', 'damage'], 'Deal 4 damage to target ally.')
    ->register();

// --- Thunderclap: [Level 3+] costs 2 less to activate ---
// Same setup as the base-cost fixture but the champion is level 3, so only two reserve payments are needed.
gaScenario('thunderclap-level-three-costs-two-less')
    ->standardDeck('Thunderclap')
    ->champion(level: 3)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Thunderclap')
    ->castSpell('Thunderclap', pay: 2, window: 'both', target: 'theirField-1')
    ->expectProperty('p1Field-0', 'Counters', '{"level":3}', 'The champion is level 3, which is what unlocks the discount')
    ->expectZoneCount('myMemory', 2, 1, 'At level 3+ Thunderclap costs 2 less: only 2 reserve are paid (2 cards in memory)')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The discounted Thunderclap still deals its 4 damage and destroys the ally')
    ->expectDqEmpty()
    ->notes('Discount path of Thunderclap: a level-3 champion pays 4 - 2 = 2 reserve.')
    ->covers(['cost', 'targeting', 'damage', 'condition'], '[Level 3+] This card costs 2 less to activate.', 'Deal 4 damage to target ally.')
    ->register();

// --- Thunderclap: a champion is not a legal target ---
// Thunderclap only targets an ALLY. After the cost is paid the opposing champion is offered as the answer and the
// engine must refuse it; the Dungeon Guide is then chosen normally.
gaScenario('thunderclap-rejects-a-champion-target')
    ->standardDeck('Thunderclap')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Thunderclap')
    ->castSpell('Thunderclap', pay: 4, window: 'both')
    ->rejects(1, 'theirField-0', 'Rejects the opposing champion because Thunderclap targets allies only')
    ->target('theirField-1')
    ->expectDamage('p2Field-0', 0, 'The champion took no damage')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The ally chosen instead was destroyed')
    ->expectDqEmpty()
    ->notes('Negative path of Thunderclap: the champion is refused as a target, then the ally is chosen.')
    ->covers(['targeting', 'damage'], 'Deal 4 damage to target ally.')
    ->register();
