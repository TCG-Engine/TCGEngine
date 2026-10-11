<?php
/**
 * Semantic fixtures for cards whose only fixture used to be a recorded snapshot (the rich-board template).
 */

// --- Rescue the Heir: return target ally you control to its owner's memory; [Level 1+] costs 1 less with a unique ally ---
// Rescue the Heir (t0240ykvj0, NORM action, reserve 1): "[Level 1+] As long as you control a unique ally, this card costs 1 less to
// activate. Return target ally you control to its owner's memory." A level-1 champion with only a plain ally pays the full 1.
gaScenario('rescue-the-heir-returns-an-ally-to-memory-and-costs-one-without-a-unique-ally')
    ->standardDeck('Rescue the Heir')
    ->readyFor('Rescue the Heir', level: 1)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Rescue the Heir')
    ->castSpell('Rescue the Heir', pay: 1, target: 'myField-1')
    ->expectZoneCount('myField', 1, 1, 'The Dungeon Guide left the field')
    ->expectProperty('p1Memory-1', 'CardID', 'em6eEh9q8y', 'The ally was returned to its owner\'s memory (after the one card paid for the spell)')
    ->expectZoneCount('myMemory', 2, 1, 'One card paid the full reserve cost of 1, plus the returned ally')
    ->expectDqEmpty()
    ->covers(['targeting', 'zone-movement', 'cost-modification'], 'Return target ally you control to its owner\'s memory.', '[Level 1+] As long as you control a unique ally, this card costs 1 less to activate.')
    ->register();

gaScenario('rescue-the-heir-costs-one-less-with-a-unique-ally-at-level-one')
    ->standardDeck('Rescue the Heir')
    ->readyFor('Rescue the Heir', level: 1)
    ->seed('myField', 'Gawain, Chivalrous Thief')
    ->seed('myHand', 'Rescue the Heir')
    ->castSpell('Rescue the Heir', target: 'myField-1')
    ->expectProperty('p1Memory-0', 'CardID', 'du50pcescf', 'The unique ally was returned to memory with no reserve paid: the spell cost 1 less')
    ->expectZoneCount('myMemory', 1, 1, 'Nothing was paid into memory')
    ->expectZoneCount('myField', 1, 1, 'The unique ally left the field')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'level-gate', 'condition'], '[Level 1+] As long as you control a unique ally, this card costs 1 less to activate.')
    ->register();

// --- Baleful Oblation: [Ciel Bonus] costs 2 less; X damage to every unit but your champion, X = the lowest omen reserve cost ---
// Omens: a Dungeon Guide (reserve 3) and an Automaton Beastkeeper (reserve 4), so X = 3 and kills the opposing 3-life Dungeon Guide.
gaScenario('baleful-oblation-ciel-bonus-costs-two-less-and-deals-the-lowest-omen-cost')
    ->standardDeck('Baleful Oblation')
    ->champion(cardName: 'Ciel, Loyal Valet', lineage: ['Tristan, Shadowdancer'])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Automaton Beastkeeper', properties: ['Counters' => ['omen' => 1]])
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Baleful Oblation')
    ->castSpell('Baleful Oblation', pay: 3, window: 'both')
    ->expectDamage('p2Field-0', 3, 'The opposing champion took X = 3 damage (the lowest omen reserve cost)')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', 'The opposing 3-life Dungeon Guide took 3 damage and died')
    ->expectDamage('p1Field-0', 0, 'Your own champion is excluded')
    ->expectZoneCount('myMemory', 3, 1, '[Ciel Bonus] The printed 5 reserve cost took only 3 payments')
    ->expectDqEmpty()
    ->covers(['damage', 'cost-modification', 'omen'], 'Deal X damage to all units except for your champion, where X is the lowest reserve cost among your omens.', '[Ciel Bonus] This card costs 2 less to activate.')
    ->register();

// --- Scars of Old: draw then discard; [Class Bonus] buff counter on each damaged ally ---
gaScenario('scars-of-old-draws-a-card-then-discards-a-card')
    ->standardDeck('Scars of Old')
    ->seed('myHand', 'Scars of Old')
    ->castSpell('Scars of Old', pay: 2, window: 'opp')
    ->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 2, 1, 'Scars of Old and the discarded card are in the graveyard')
    ->expectZoneCount('myHand', 5, 1, 'Hand: 8 - Scars of Old - 2 reserve + 1 drawn - 1 discarded = 5')
    ->expectDqEmpty()
    ->covers(['draw-discard'], 'Draw a card, then discard a card.')
    ->register();

gaScenario('scars-of-old-class-bonus-buffs-each-damaged-ally')
    ->standardDeck('Scars of Old')
    ->readyFor('Scars of Old', classBonus: true)
    ->seed('myField', 'Dungeon Guide', properties: ['Damage' => 1])
    ->seed('myField', 'Fluffy Shopkeep')
    ->seed('myHand', 'Scars of Old')
    ->castSpell('Scars of Old', pay: 2, window: 'opp')
    ->answer(1, 'myHand-0')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] The damaged Dungeon Guide got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '[]', 'The undamaged Fluffy Shopkeep got none')
    ->expectDqEmpty()
    ->covers(['counter', 'class-bonus', 'condition'], '[Class Bonus] Put a buff counter on each damaged ally you control.')
    ->register();

// --- Incarnate Majesty: put The Majestic Spirit from the material deck onto the field; costs 1 less per regalia weapon banished ---
// Incarnate Majesty (7dl5j4lx6x, CRUX, reserve 12): "[Class Bonus] Efficiency. This card costs 1 less to activate for each regalia
// weapon card in your banishment. Put a card named The Majestic Spirit from your material deck or banishment onto the field."
// Four Warrior's Longswords in the banishment take the 12 down to 8.
gaScenario('incarnate-majesty-puts-the-majestic-spirit-onto-the-field-for-less-per-banished-weapon')
    ->deck("# Material\n1 Spirit of Fire\n1 The Majestic Spirit\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n")
    ->readyFor('Incarnate Majesty', classBonus: true)
    ->seed('myBanish', 'Warrior\'s Longsword')
    ->seed('myBanish', 'Warrior\'s Longsword')
    ->seed('myBanish', 'Warrior\'s Longsword')
    ->seed('myBanish', 'Warrior\'s Longsword')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Incarnate Majesty')
    ->castSpell('Incarnate Majesty', pay: 8, window: 'both')
    ->expectProperty('p1Field-1', 'CardID', 'tsvbgl6ffq', 'The Majestic Spirit was put onto the field from the material deck')
    ->expectZoneCount('myMaterial', 0, 1, 'It left the material deck')
    ->expectZoneCount('myMemory', 8, 1, 'Four banished regalia weapons reduced the 12 reserve cost to 8 payments')
    ->expectDqEmpty()
    ->covers(['cost-modification', 'zone-movement', 'class-bonus'], 'This card costs 1 less to activate for each regalia weapon card in your banishment.', 'Put a card named The Majestic Spirit from your material deck or banishment onto the field.')
    ->register();

// --- Synthetic Core: when a non-token Automaton ally you control dies, you may banish it to return that ally to memory ---
// Undeniable Truth's additional cost (sacrifice an ally) kills the Automaton Beastkeeper; Synthetic Core then offers its trigger.
gaScenario('synthetic-core-banishes-itself-to-return-a-dead-automaton-to-memory')
    ->standardDeck('Undeniable Truth')
    ->seed('myField', 'Synthetic Core')
    ->seed('myField', 'Automaton Beastkeeper')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-2')->answer(1, 'YES')->pay(1)
    ->tested('Synthetic Core', 'Automaton Beastkeeper')
    ->expectZoneCount('myBanish', 1, 1, 'Synthetic Core banished itself')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only Undeniable Truth is in the graveyard: the Automaton did not stay there')
    ->expectProperty('p1Memory-0', 'CardID', 'i5jnsl7ddc', 'The dead Automaton Beastkeeper was returned to memory (before the reserve payment, which lands after it)')
    ->expectDqEmpty()
    ->covers(['trigger', 'zone-movement', 'banish-self'], 'Whenever a non-token Automaton ally you control dies, you may banish Synthetic Core. If you do, return that ally to your memory.')
    ->register();

gaScenario('synthetic-core-declined-leaves-the-dead-automaton-in-the-graveyard')
    ->standardDeck('Undeniable Truth')
    ->seed('myField', 'Synthetic Core')
    ->seed('myField', 'Automaton Beastkeeper')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-2')->answer(1, 'NO')->pay(1)
    ->tested('Synthetic Core', 'Automaton Beastkeeper')
    ->expectZoneCount('myBanish', 0, 1, 'Synthetic Core stays on the field: the offer was declined')
    ->expectZoneCount('myGraveyard', 2, 1, 'Undeniable Truth and the dead Automaton Beastkeeper are in the graveyard')
    ->expectDqEmpty()
    ->covers(['trigger', 'optional-choice-decline'], 'Whenever a non-token Automaton ally you control dies, you may banish Synthetic Core.')
    ->register();

// --- Sneaky Raccoon: has stealth as long as the opponent's graveyard is empty ---
// Player 2 attacks on their own turn. Stealth removes the Raccoon from the legal attack targets, so the attack must go to the champion.
gaScenario('sneaky-raccoon-has-stealth-while-the-opponent-graveyard-is-empty')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Sneaky Raccoon')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Sneaky Raccoon has stealth while the attacker\'s graveyard is empty: it cannot be chosen as the attack target')
    ->answer(2, 'theirField-0')
    ->tested('Sneaky Raccoon')
    ->expectDamage('p1Field-1', 0, 'The Raccoon took no damage')
    ->expectDamage('p1Field-0', 1, 'The attack went to the champion instead')
    ->covers(['stealth', 'condition', 'targeting'], 'As long as an opponent has no cards in their graveyard, CARDNAME has stealth.')
    ->register();

gaScenario('sneaky-raccoon-loses-stealth-once-the-opponent-has-a-graveyard-card')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Sneaky Raccoon')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myGraveyard', 'Fluffy Shopkeep', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pass(1)
    ->tested('Sneaky Raccoon')
    ->expectDamage('p1Field-1', 1, 'With a card in the attacker\'s graveyard the Raccoon has no stealth and was a legal target: it took the 1 damage')
    ->covers(['stealth', 'condition', 'targeting'], 'As long as an opponent has no cards in their graveyard, CARDNAME has stealth.')
    ->register();
