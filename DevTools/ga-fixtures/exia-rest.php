<?php
/**
 * Phase 3e (Exia), the cards left over after exia.php: Fervent Lancer and the opponent-phase reactions.
 */

// ---- Fervent Lancer ----

gaScenario('fervent-lancer-banishes-the-exia-card-it-resolves-and-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloodshroud Temper', damage: 0)
    ->seed('myField', 'Fervent Lancer')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Bloodshroud Temper')
    ->play('Bloodshroud Temper')->pay(2)->target('myField-2')->answer(1, 'YES')->passBoth()
    ->expectZoneCount('myBanish', 1, 1, 'The resolved exia card was banished instead of reaching the graveyard')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing reached the graveyard')
    ->expectPower('myField-1', 5, 'The Lancer is 3 POWER plus 2 for the banished card')
    ->tested('Fervent Lancer')
    ->covers(['trigger', 'zone-movement', 'power-modifier'], 'Whenever you activate an exia element card, you may banish it as it resolves.', 'As long as there\'s a card banished by CARDNAME, it gets +2 [POWER]')
    ->register();

gaScenario('fervent-lancer-declining-the-banish-leaves-the-exia-card-in-the-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloodshroud Temper', damage: 0)
    ->seed('myField', 'Fervent Lancer')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Bloodshroud Temper')
    ->play('Bloodshroud Temper')->pay(2)->target('myField-2')->answer(1, 'NO')->passBoth()
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->expectZoneCount('myGraveyard', 1, 1, 'The exia card went to the graveyard')
    ->expectPower('myField-1', 3, 'No banished card, no bonus')
    ->tested('Fervent Lancer')
    ->covers(['trigger', 'condition'], 'Whenever you activate an exia element card, you may banish it as it resolves.')
    ->register();

gaScenario('fervent-lancer-holding-a-banished-card-cannot-end-the-turn-while-it-can-attack-a-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fervent Lancer')
    ->seed('myField', 'Fervent Lancer', properties: ['Counters' => ['banished_card' => true]])
    ->endTurn(1)->endTurn(2)->pass(1)
    ->endTurn(1)
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('theirField-0', 5, 'The turn did not end: the Lancer (3 POWER +2) still attacked the champion in this main phase')
    ->tested('Fervent Lancer')
    ->covers(['combat', 'condition'], 'must attack a champion each turn if able.')
    ->register();

gaScenario('fervent-lancer-without-a-banished-card-may-end-the-turn-without-attacking')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fervent Lancer')
    ->seed('myField', 'Fervent Lancer')
    ->endTurn(1)->endTurn(2)->pass(1)
    ->endTurn(1)->pass(2)
    ->expectZoneCount('theirHand', 9, 1, 'The turn ended: the opponent drew for their next turn')
    ->tested('Fervent Lancer')
    ->covers(['combat', 'condition'], 'As long as there\'s a card banished by CARDNAME, it gets +2 [POWER] and must attack a champion each turn if able.')
    ->register();

// ---- Hemorrhaged Intimidation ----

gaScenario('hemorrhaged-intimidation-draws-into-memory-and-with-sixteen-damage-taxes-the-opponents-next-card-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hemorrhaged Intimidation', classBonus: true, damage: 16)
    ->seed('myHand', 'Hemorrhaged Intimidation')
    ->champion(classes: 'MAGE', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->reachTurnThree()->pass(2)->endTurn()->pass(2)
    ->answer(1, 'myHand-7')->pay(1)
    ->action(2, 10002, 'myHand-7!FSM!')->pay(5, 2)
    ->expectZoneCount('myMemory', 2, 1, 'Paid (1) and drew a card into memory')
    ->expectZoneCount('theirMemory', 5, 1, 'The opponent paid 3 + 2 for 16 damage counters')
    ->expectZoneCount('theirField', 2, 1, 'The Dungeon Guide resolved')
    ->tested('Hemorrhaged Intimidation')
    ->covers(['timing-restriction', 'draw-discard', 'cost-increase', 'class-bonus'], 'Activate this card only during an opponent\'s recollection phase.', 'Draw a card into your memory.', '[Class Bonus] The next card your opponent activates this turn costs (1) more to activate for every eight damage counters on your champion.')
    ->register();

gaScenario('hemorrhaged-intimidation-without-the-class-bonus-taxes-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hemorrhaged Intimidation', damage: 16)
    ->seed('myHand', 'Hemorrhaged Intimidation')
    ->champion(classes: 'MAGE', player: 2)
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->reachTurnThree()->pass(2)->endTurn()->pass(2)
    ->answer(1, 'myHand-7')->pay(1)
    ->action(2, 10002, 'myHand-7!FSM!')->pay(3, 2)
    ->expectZoneCount('theirMemory', 3, 1, 'No Class Bonus: the Dungeon Guide cost its printed 3')
    ->expectZoneCount('theirField', 2, 1, 'The Dungeon Guide resolved')
    ->tested('Hemorrhaged Intimidation')
    ->covers(['class-bonus', 'condition', 'cost-increase'], '[Class Bonus] The next card your opponent activates this turn costs (1) more to activate for every eight damage counters on your champion.')
    ->register();

// ---- Ingress of Sanguine Ire ----

gaScenario('ingress-of-sanguine-ire-in-the-opponents-end-phase-draws-two-into-memory-and-buffs-the-first-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ingress of Sanguine Ire')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Ingress of Sanguine Ire')
    ->endTurn(1)->endTurn(2)
    ->answer(1, 'myHand-7')->pay(2)
    ->expectZoneCount('myMemory', 4, 1, 'Paid (2) and drew two cards into memory: the champion took no damage this turn', step: 5)
    ->passBoth()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->pass(2)
    ->expectDamage('theirField-0', 4, 'Training Sword\'s 1 POWER plus 3 for the first attack')
    ->tested('Ingress of Sanguine Ire')
    ->covers(['timing-restriction', 'stat-change', 'draw-discard', 'condition'], 'Activate this card only during an opponent\'s end phase.', 'Your champion\'s first attack during your next turn gets +3 [POWER].', 'If your champion hasn\'t taken damage this turn, draw two cards into your memory.')
    ->register();

gaScenario('ingress-of-sanguine-ire-draws-nothing-when-the-champion-took-damage-this-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ingress of Sanguine Ire')
    ->seed('myHand', 'Ingress of Sanguine Ire')
    ->seed('myField', 'Training Sword', player: 2)
    ->endTurn(1)->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->pass(1)->answer(1, '-')
    ->endTurn(2)
    ->answer(1, 'myHand-7')->pay(2)
    ->expectZoneCount('myMemory', 2, 1, 'Only the two paid cards: the champion took damage this turn, so nothing was drawn')
    ->tested('Ingress of Sanguine Ire')
    ->covers(['condition', 'draw-discard'], 'If your champion hasn\'t taken damage this turn, draw two cards into your memory.')
    ->register();

// ---- Deflecting Advantage ----

gaScenario('deflecting-advantage-prevents-one-damage-per-three-damage-counters-from-the-opponents-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Deflecting Advantage', damage: 3)
    ->seed('myHand', 'Deflecting Advantage')
    ->seed('myField', 'Bulwark Sword', player: 2)
    ->endTurn(1)->pass(1)->pass(1)->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->pay(2, 2)
    ->answer(1, 'myHand-7')->pay(1)->target('myField-0')->passBoth()
    ->expectDamage('p1Field-0', 4, 'Bulwark Sword hits for 2, one of it prevented: 3 + 1')
    ->tested('Deflecting Advantage')
    ->covers(['damage-prevention', 'scaling'], 'Prevent the next 1 damage that would be dealt to target unit this turn from sources you don\'t control for every three damage counters on your champion.')
    ->register();

gaScenario('deflecting-advantage-jin-bonus-triggers-the-on-enter-of-a-polearm-and-adds-a-durability-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Deflecting Advantage', damage: 3)
    ->champion(cardName: 'Jin, Fate Defiant', damage: 3)
    ->seed('myField', 'Shuang Ji of Sacrifice')
    ->seed('myHand', 'Deflecting Advantage')
    ->answer(1, 'NO')
    ->play('Deflecting Advantage')->pay(1)->target('myField-0')->answer(1, 'myField-1')->answer(1, 'YES')
    ->expectDamage('p1Field-0', 8, 'Shuang Ji of Sacrifice\'s On Enter dealt 5 unpreventable damage to the champion (3 + 5)')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'The polearm got a durability counter on top of the three it entered with')
    ->tested('Deflecting Advantage')
    ->covers(['jin-bonus', 'on-enter', 'durability'], '[Jin Bonus] Choose a Polearm weapon you control and trigger each of its **on enter** effects. Then put a **durability** counter on it.')
    ->register();

gaScenario('deflecting-advantage-without-jin-skips-the-polearm-clause')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Deflecting Advantage', damage: 3)
    ->seed('myField', 'Shuang Ji of Sacrifice')
    ->seed('myHand', 'Deflecting Advantage')
    ->answer(1, 'NO')
    ->play('Deflecting Advantage')->pay(1)->target('myField-0')
    ->expectDamage('p1Field-0', 3, 'No Jin: no polearm was chosen, so the On Enter never ran')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'No durability counter was added')
    ->tested('Deflecting Advantage')
    ->covers(['jin-bonus', 'condition'], '[Jin Bonus] Choose a Polearm weapon you control and trigger each of its **on enter** effects. Then put a **durability** counter on it.')
    ->register();

// ---- Thanatotic Hemosynth ----

gaScenario('thanatotic-hemosynth-with-ten-damage-lets-an-aenean-spell-in-the-graveyard-be-ephemerated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Thanatotic Hemosynth', classBonus: true, damage: 10)
    ->seed('myGraveyard', 'Aenean Scorching Comet')
    ->seed('myHand', 'Thanatotic Hemosynth')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Thanatotic Hemosynth')->pay(1)
    ->answer(1, 'myGraveyard-0')
    ->expectGlobalEffect(1, 'THANATOTIC_EPHEMERATE_50m1nBduUZ', 'The Aenean spell gained ephemerate')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)->target('theirField-1')->passBoth()
    ->expectZoneCount('myBanish', 1, 1, 'The ephemerated action was banished as it resolved')
    ->expectDamage('theirField-1', 2, 'Aenean Scorching Comet dealt 2 damage')
    ->tested('Thanatotic Hemosynth')
    ->covers(['damage-threshold', 'ephemerate', 'graveyard-ability'], '[Damage 10+] **On Enter: **Target Aenean Spell card in your graveyard gains "**Ephemerate — (X)**", where **X** is that card’s reserve cost.')
    ->register();

gaScenario('thanatotic-hemosynth-below-ten-damage-grants-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Thanatotic Hemosynth', classBonus: true, damage: 9)
    ->seed('myGraveyard', 'Aenean Scorching Comet')
    ->seed('myHand', 'Thanatotic Hemosynth')
    ->play('Thanatotic Hemosynth')->pay(1)
    ->expectGlobalEffect(1, 'THANATOTIC_EPHEMERATE_50m1nBduUZ', 'Nine damage: no ephemerate was granted', 0)
    ->expectDqEmpty()
    ->tested('Thanatotic Hemosynth')
    ->covers(['damage-threshold', 'condition'], '[Damage 10+] **On Enter: **Target Aenean Spell card in your graveyard gains "**Ephemerate — (X)**", where **X** is that card’s reserve cost.')
    ->register();
