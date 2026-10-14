<?php
/**
 * Phase 3d (Norm): boons played from hand ("As you gain this boon" clauses resolve on entry). 'quiet' deck.
 */

// ---- Lesser Boon of Isis ----

gaScenario('lesser-boon-of-isis-gaining-it-empowers-the-champion-by-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Isis', classBonus: true)
    ->seed('myHand', 'Lesser Boon of Isis')
    ->castSpell('Lesser Boon of Isis', pay: 2, window: '')
    ->expectProperty('p1Field-0', 'TurnEffects', '["GlqhpkmflM","EMPOWERED","EMPOWER_PLUS_3"]', 'The champion is empowered by 3')
    ->tested('Lesser Boon of Isis')
    ->covers(['class-locked', 'empower'], 'As you gain this boon, **empower 3**.')
    ->register();

// ---- Lesser Boon of Parvati ----

gaScenario('lesser-boon-of-parvati-puts-six-durability-counters-on-your-siegeable-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Epicurean Institute')
    ->seed('myHand', 'Lesser Boon of Parvati')
    ->play('Lesser Boon of Parvati')->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":9}', 'The domain\'s 3 durability counters plus six')
    ->tested('Lesser Boon of Parvati')
    ->covers(['durability', 'domain'], 'As you gain this boon, put six **durability** counters on a Siegeable domain you control or put three **durability** counters on a Siegeable domain you don\'t control.')
    ->register();

gaScenario('lesser-boon-of-parvati-puts-three-durability-counters-on-an-opposing-siegeable-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Epicurean Institute', player: 2)
    ->seed('myHand', 'Lesser Boon of Parvati')
    ->play('Lesser Boon of Parvati')->target('theirField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"durability":6}', 'The opposing domain\'s 3 durability counters plus three')
    ->tested('Lesser Boon of Parvati')
    ->covers(['durability', 'domain', 'targeting'], 'put three **durability** counters on a Siegeable domain you don\'t control.')
    ->register();

// ---- Greater Boon of Parvati ----

gaScenario('greater-boon-of-parvati-gaining-it-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Might of Humanity')
    ->seed('myHand', 'Greater Boon of Parvati')
    ->castSpell('Greater Boon of Parvati', pay: 4, window: '')
    ->expectZoneCount('myHand', 4, 1, 'Eight in hand, minus the boon and four payments, plus the drawn card (8 - 1 - 4 + 1)')
    ->tested('Greater Boon of Parvati')
    ->covers(['level-locked', 'draw'], 'As you gain this boon, draw a card.')
    ->register();

// ---- Greater Boon of the Underdog ----

gaScenario('greater-boon-of-the-underdog-draws-two-and-puts-two-buff-counters-on-an-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Might of Humanity')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Greater Boon of the Underdog')
    ->play('Greater Boon of the Underdog')->pay(3)->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Two buff counters on the ally')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus the boon and three payments, plus two drawn (8 - 1 - 3 + 2)')
    ->tested('Greater Boon of the Underdog')
    ->covers(['level-locked', 'draw', 'counter'], 'As you gain this boon, draw two cards and put two **buff** counters on an ally you control.')
    ->register();

// ---- Lesser Boon of Allurement ----

gaScenario('lesser-boon-of-allurement-the-next-phantasia-costs-two-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Allurement', classBonus: true)
    ->seed('myHand', 'Lesser Boon of Allurement')
    ->seed('myHand', 'Bygone Days')
    ->play('Lesser Boon of Allurement')
    ->play('Bygone Days')->passBoth()
    ->expectCardExists('myField', 'Bygone Days', 'The 2-cost phantasia entered without any payment')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->tested('Lesser Boon of Allurement')
    ->covers(['class-locked', 'cost-reduction', 'phantasia'], 'As you gain this boon, the next phantasia card you activate this turn costs (2) less to activate.')
    ->register();

gaScenario('lesser-boon-of-allurement-five-draws-a-card-into-memory-only-once')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Allurement', classBonus: true)
    ->seed('myField', 'Lesser Boon of Allurement')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(5)
    ->expectZoneCount('myMemory', 6, 1, 'Five paid plus one card drawn into memory')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myMemory', 6, 1, 'Only once: the second activation was refused')
    ->tested('Lesser Boon of Allurement')
    ->covers(['activated-ability', 'draw-into-memory', 'once'], '(5): Draw a card into your memory. This ability costs (1) less to activate for each phantasia you control. Activate this ability only once.')
    ->register();

// ---- Lesser Boon of Distance ----

gaScenario('lesser-boon-of-distance-two-makes-a-ranger-unit-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Distance', classBonus: true)
    ->seed('myField', 'Lesser Boon of Distance')
    ->seed('myField', 'Lone Gunslinger')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->pay(2)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The Ranger ally became distant')
    ->expectZoneCount('myMemory', 2, 1, 'Only the two payments: no draw before the third resolution')
    ->tested('Lesser Boon of Distance')
    ->covers(['activated-ability', 'distant'], '(2): Target Ranger unit becomes **distant**.')
    ->register();

gaScenario('lesser-boon-of-distance-the-third-resolution-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Lesser Boon of Distance')
    ->seed('myField', 'Lone Gunslinger')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->pay(2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->pay(2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')->pay(2)
    ->expectZoneCount('myMemory', 7, 1, 'Six payments plus the card drawn into memory by the third resolution')
    ->tested('Lesser Boon of Distance')
    ->covers(['activated-ability', 'draw-into-memory', 'counter'], 'If this is the third time this ability has resolved, draw a card into your memory. Activate this ability only thrice.')
    ->register();
