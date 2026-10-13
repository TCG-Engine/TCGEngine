<?php
/**
 * Phase 3d (Norm): actions, first batch (recover, tokens, counters, level and simple draws). Written on the 'quiet' deck.
 */

// ---- Auspicious Feast ----

gaScenario('auspicious-feast-each-player-recovers-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Auspicious Feast', damage: 6)
    ->patch('myField-0', ['Damage' => 6], player: 2)
    ->seed('myHand', 'Auspicious Feast')
    ->castSpell('Auspicious Feast', pay: 1, window: '')
    ->expectDamage('p1Field-0', 2, 'Recover 4: 6 - 4')
    ->expectDamage('p2Field-0', 2, 'Each player recovers: the opponent too, 6 - 4')
    ->tested('Auspicious Feast')
    ->covers(['recover'], 'Each player **recovers 4**.')
    ->register();

// ---- Bandage Wound ----

gaScenario('bandage-wound-recovers-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bandage Wound', damage: 6)
    ->seed('myHand', 'Bandage Wound')
    ->castSpell('Bandage Wound', pay: 2, window: '')
    ->expectDamage('p1Field-0', 2, 'Recover 4: 6 - 4')
    ->tested('Bandage Wound')
    ->covers(['recover'], '**Recover 4**.')
    ->register();

// ---- Enchanted Fete ----

gaScenario('enchanted-fete-recovers-three-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Enchanted Fete', damage: 6)
    ->seed('myHand', 'Enchanted Fete')
    ->castSpell('Enchanted Fete', pay: 4, window: '')
    ->expectDamage('p1Field-0', 3, 'Recover 3: 6 - 3')
    ->expectZoneCount('myMemory', 5, 1, 'Four paid plus one drawn into memory')
    ->tested('Enchanted Fete')
    ->covers(['recover', 'draw-into-memory'], '**Recover** 3 and draw a card into your memory.')
    ->register();

// ---- Another Round ----

gaScenario('another-round-recovers-one-plus-twice-the-copies-in-graveyards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Another Round', damage: 9)
    ->seed('myGraveyard', 'Another Round')
    ->seed('myGraveyard', 'Another Round', player: 2)
    ->seed('myHand', 'Another Round')
    ->castSpell('Another Round', pay: 2, window: '')
    ->expectDamage('p1Field-0', 4, 'Two copies in graveyards: recover 1 + 2 x 2 = 5 (9 - 5)')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid plus one drawn into memory')
    ->tested('Another Round')
    ->covers(['recover', 'variable', 'draw-into-memory'], '**Recover 1+X**, where **X** is twice the amount of cards named CARDNAME in all graveyards. Then draw a card into your memory.')
    ->register();

// ---- Belted Tune ----

gaScenario('belted-tune-puts-a-buff-counter-on-the-target-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Belted Tune')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The target ally got a buff counter')
    ->tested('Belted Tune')
    ->covers(['counter', 'targeting'], 'Put a **buff** counter on target ally.')
    ->register();

// ---- Cell Production ----

gaScenario('cell-production-summons-two-rested-powercells')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cell Production')
    ->seed('myHand', 'Cell Production')
    ->castSpell('Cell Production', pay: 3, window: '')
    ->expectZoneCount('myField', 3, 1, 'The champion and two Powercell tokens')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Powercells entered rested')
    ->tested('Cell Production')
    ->covers(['summon'], '**Summon** two Powercell tokens rested.')
    ->register();

// ---- Feathery Tune ----

gaScenario('feathery-tune-summons-two-fledglings')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Feathery Tune')
    ->seed('myHand', 'Feathery Tune')
    ->castSpell('Feathery Tune', pay: 3, window: '')
    ->expectCardExists('myField', 'Fledgling', 'A Fledgling token was summoned')
    ->expectZoneCount('myField', 3, 1, 'The champion and two Fledglings')
    ->tested('Feathery Tune')
    ->covers(['summon'], '**Summon** two Fledgling tokens.')
    ->register();

// ---- Develop Mana ----

gaScenario('develop-mana-puts-a-level-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Develop Mana')
    ->seed('myHand', 'Develop Mana')
    ->castSpell('Develop Mana', pay: 3, window: '')
    ->expectLevel('p1Field-0', 1, 'A level counter: level 0 + 1')
    ->tested('Develop Mana')
    ->covers(['counter', 'level'], 'Put a **level** counter on your champion.')
    ->register();

// ---- Cram Session ----

gaScenario('cram-session-gives-the-champion-plus-one-level-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cram Session')
    ->seed('myHand', 'Cram Session')
    ->castSpell('Cram Session', pay: 1, window: '')
    ->expectLevel('p1Field-0', 1, '+1 level until end of turn')
    ->tested('Cram Session')
    ->covers(['level', 'stat-change'], 'Your champion gets +1 level until end of turn.')
    ->register();

// ---- Empowering Prayer ----

gaScenario('empowering-prayer-gives-plus-two-level-and-draws-when-level-equals-the-memory-count')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Empowering Prayer')
    ->seed('myHand', 'Empowering Prayer')
    ->castSpell('Empowering Prayer', pay: 2, window: '')
    ->expectLevel('p1Field-0', 2, '+2 level until end of turn')
    ->expectZoneCount('myHand', 6, 1, 'Level 2 equals the 2 cards in memory: a card was drawn (7 + 1 - 1 - 2 + 1)')
    ->tested('Empowering Prayer')
    ->covers(['level', 'stat-change', 'condition', 'draw'], 'Your champion gets +2 level until end of turn. Then if your champion\'s level is equal to the amount of cards in your memory, draw a card.')
    ->register();

// ---- Empowering Enlightenment ----

gaScenario('empowering-enlightenment-puts-an-enlighten-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Empowering Enlightenment')
    ->seed('myHand', 'Empowering Enlightenment')
    ->castSpell('Empowering Enlightenment', pay: 2, window: '')
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}', 'An enlighten counter went on the champion')
    ->tested('Empowering Enlightenment')
    ->covers(['empower', 'counter'], '**Empower 2**. Put an **enlighten** counter on your champion.')
    ->register();

// ---- Fight for the Crown ----

gaScenario('fight-for-the-crown-each-player-sacrifices-an-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fight for the Crown')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Fight for the Crown')
    ->castSpell('Fight for the Crown', pay: 6, window: '')->answer(1, 'myField-1')->answer(2, 'myField-1')
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'You sacrificed your only ally')
    ->expectCardExists('myGraveyard', 'Gray Wolf', 'The opponent sacrificed theirs', 2)
    ->tested('Fight for the Crown')
    ->covers(['sacrifice'], 'Each player sacrifices an ally.')
    ->register();

// ---- Conceal ----

gaScenario('conceal-allies-you-control-gain-stealth-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Conceal')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Conceal')
    ->castSpell('Conceal', pay: 2, window: '')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'The ally gained stealth until end of turn')
    ->tested('Conceal')
    ->covers(['stealth'], 'Allies you control gain **stealth** until end of turn.')
    ->register();

// ---- Hightail / Backdash ----

gaScenario('hightail-makes-the-target-unit-distant-and-a-ranger-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hightail')
    ->seed('myField', 'Seasoned Archer')
    ->seed('myHand', 'Hightail')
    ->castSpell('Hightail', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The Ranger ally became distant')
    ->expectZoneCount('myMemory', 2, 1, 'One paid plus one drawn into memory (the target is a Ranger)')
    ->tested('Hightail')
    ->covers(['distant', 'draw-into-memory', 'condition', 'targeting'], 'Target unit becomes **distant**. If that unit is a Ranger, draw a card into your memory.')
    ->register();

gaScenario('hightail-a-non-ranger-target-becomes-distant-without-the-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hightail')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Hightail')
    ->castSpell('Hightail', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The ally became distant')
    ->expectZoneCount('myMemory', 1, 1, 'No Ranger: no card drawn into memory')
    ->tested('Hightail')
    ->covers(['distant', 'condition'], 'If that unit is a Ranger, draw a card into your memory.')
    ->register();

gaScenario('backdash-makes-the-target-unit-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Backdash')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Backdash')
    ->castSpell('Backdash', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The ally became distant')
    ->tested('Backdash')
    ->covers(['distant', 'targeting'], 'Target unit becomes **distant**.')
    ->register();

gaScenario('backdash-class-bonus-level-two-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Backdash', classBonus: true, level: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Backdash')
    ->castSpell('Backdash', pay: 1, window: '', target: 'myField-1')
    ->expectZoneCount('myHand', 7, 1, '[Class Bonus] [Level 2+] a card was drawn (7 + 1 seeded - 1 cast - 1 paid + 1 drawn)')
    ->tested('Backdash')
    ->covers(['class-bonus', 'level-gate', 'draw'], '[Class Bonus] [Level 2+] Draw a card.')
    ->register();

// ---- Escape the Wreckage ----

gaScenario('escape-the-wreckage-returns-an-ally-to-memory-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Escape the Wreckage')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Escape the Wreckage')
    ->castSpell('Escape the Wreckage', pay: 3, window: '', target: 'myField-1')
    ->expectCardExists('myMemory', 'Dungeon Guide', 'The ally returned to its owner\'s memory')
    ->expectZoneCount('myMemory', 5, 1, 'Three paid, the returned ally and one drawn card')
    ->tested('Escape the Wreckage')
    ->covers(['zone-movement', 'draw-into-memory', 'targeting'], 'Return target ally you control to its owner\'s memory. If you do, draw a card into your memory.')
    ->register();
