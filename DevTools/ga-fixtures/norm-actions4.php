<?php
/**
 * Phase 3d (Norm): actions, fourth batch (cost reductions and board-wide effects). 'quiet' deck.
 */

// ---- Hire Mercenaries ----

gaScenario('hire-mercenaries-with-an-opposing-ally-costs-one-less-and-puts-one-of-the-top-two-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hire Mercenaries')
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Hire Mercenaries')
    ->castSpell('Hire Mercenaries', pay: 1, window: '')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myMemory', 2, 1, 'One paid (2 - 1) plus the chosen card put into memory')
    ->expectZoneCount('myDeck', 10, 1, 'The other card went to the bottom of the deck (9 + 2 on top - 1 to memory)')
    ->tested('Hire Mercenaries')
    ->covers(['cost-reduction', 'condition', 'look'], 'This card costs 1 less to activate if an opponent controls one or more allies.', 'Look at the top two cards of your deck. Put one of those cards into your memory and the other on the bottom of your deck.')
    ->register();

// ---- Lost Wisdom ----

gaScenario('lost-wisdom-class-bonus-with-a-unique-ally-costs-two-less-and-returns-a-spell-to-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lost Wisdom', classBonus: true)
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myGraveyard', 'Purification')
    ->seed('myHand', 'Lost Wisdom')
    ->castSpell('Lost Wisdom', pay: 2, window: '')->answer(1, 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Purification', 'The Spell card returned from the graveyard to memory')
    ->tested('Lost Wisdom')
    ->covers(['class-bonus', 'cost-reduction', 'zone-movement'], '[Class Bonus] This card costs 2 less to activate if you control one or more unique allies.', 'Return a Spell card from your graveyard to your memory.')
    ->register();

// ---- Collect Junk ----

gaScenario('collect-junk-costs-two-less-per-raccoon-ally-banishes-a-graveyard-card-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Collect Junk')
    ->seed('myField', 'Banner Raccoon')
    ->seed('myField', 'Portly Raccoon')
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Collect Junk')
    ->castSpell('Collect Junk', pay: 1, window: '', target: 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard card was banished')
    ->expectZoneCount('myMemory', 2, 1, 'One paid (5 - 4) plus one drawn into memory')
    ->tested('Collect Junk')
    ->covers(['cost-reduction', 'banish', 'draw-into-memory'], 'This card costs 2 less to activate for each Raccoon ally you control.', 'Banish up to one target card from a graveyard. Draw a card into your memory.')
    ->register();

// ---- Coronation Ceremony ----

gaScenario('coronation-ceremony-targeting-a-unique-ally-costs-two-less-and-grants-spellshroud')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Coronation Ceremony')
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myHand', 'Coronation Ceremony')
    ->play('Coronation Ceremony')->answer(1, 'YES')->pay(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The unique ally gained spellshroud')
    ->expectZoneCount('myMemory', 1, 1, 'Cost 3 - 2 = 1 paid for targeting a unique ally')
    ->tested('Coronation Ceremony')
    ->covers(['cost-reduction', 'condition', 'spellshroud', 'targeting'], 'This card costs 2 less to activate if it targets a unique ally.', 'Target unit gains **spellshroud** until end of turn.')
    ->register();

gaScenario('coronation-ceremony-not-targeting-a-unique-ally-costs-the-printed-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Coronation Ceremony')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Coronation Ceremony')
    ->play('Coronation Ceremony')->pay(3)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The non-unique unit gained spellshroud')
    ->expectZoneCount('myMemory', 3, 1, 'The printed cost 3')
    ->tested('Coronation Ceremony')
    ->covers(['cost-reduction', 'condition'], 'This card costs 2 less to activate if it targets a unique ally.')
    ->register();

// ---- Mob Vantage ----

gaScenario('mob-vantage-class-bonus-against-three-units-costs-two-less-makes-the-champion-distant-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mob Vantage', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Mob Vantage')
    ->castSpell('Mob Vantage', pay: 1, window: '')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","vz4kc558yx-ACTION_ACTIVATED"]', 'Your champion became distant')
    ->expectZoneCount('myMemory', 2, 1, 'One paid (3 - 2) plus one drawn into memory')
    ->tested('Mob Vantage')
    ->covers(['class-bonus', 'cost-reduction', 'condition', 'distant', 'draw-into-memory'], '[Class Bonus] As long as an opponent controls three or more units, this card costs 2 less to activate.', 'Your champion becomes **distant**. Draw a card into your memory.')
    ->register();

// ---- Mortal Ambition ----

gaScenario('mortal-ambition-costs-two-less-per-unique-ally-and-buffs-human-and-horse-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mortal Ambition')
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Mortal Ambition')
    ->castSpell('Mortal Ambition', pay: 4, window: '')
    ->expectLife('p1Field-2', 4, 'The Horse ally got +1 life: 3 + 1')
    ->tested('Mortal Ambition')
    ->covers(['cost-reduction', 'stat-change', 'ambush', 'steadfast'], 'This card costs 2 less to activate for each unique ally you control.', 'Until end of turn, Human and Horse allies you control get +1 [LIFE] and gain **ambush** and **steadfast**.')
    ->register();

// ---- Animal Encounter ----

gaScenario('animal-encounter-puts-buff-counters-on-both-allies-and-gains-crowds-favor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Animal Encounter', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Animal Encounter')
    ->castSpell('Animal Encounter', pay: 2, window: '', target: 'myField-1')->answer(1, 'theirField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Your ally got a buff counter')
    ->expectProperty('p2Field-1', 'Counters', '{"buff":1}', 'The opposing ally got a buff counter')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'A counter on an ally you do not control: Crowd\'s Favor gained')
    ->tested('Animal Encounter')
    ->covers(['class-bonus', 'cost-reduction', 'counter', 'status', 'targeting'], '[Class Bonus] This card costs 1 less to activate.', 'Put a **buff** counter on an ally you control and up to one ally you don\'t control. If a **buff** counter was put on an ally you don\'t control this way, you gain the Crowd\'s Favor status.')
    ->register();

// ---- Slime Party ----

gaScenario('slime-party-puts-a-buff-counter-on-each-slime-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Slime Party')
    ->seed('myField', 'Simple Slime')
    ->seed('myField', 'Baby Slime')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Slime Party')
    ->castSpell('Slime Party', pay: 4, window: '')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A Slime ally got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The other Slime ally too')
    ->expectProperty('p1Field-3', 'Counters', '[]', 'The Dungeon Guide is not a Slime')
    ->tested('Slime Party')
    ->covers(['counter'], 'Put a **buff** counter on each Slime ally you control.')
    ->register();

// Silvie bonus: costs 1 less for each Slime ally with a different element (Red Slime FIRE, Storm Slime ARCANE, Simple Slime NORM, second Red Slime repeats FIRE).
gaScenario('slime-party-silvie-bonus-costs-one-less-per-different-element-slime')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Silvie, Slime Sovereign'])
    ->seed('myField', 'Red Slime')
    ->seed('myField', 'Red Slime')
    ->seed('myField', 'Storm Slime')
    ->seed('myField', 'Simple Slime')
    ->seed('myHand', 'Slime Party')
    ->castSpell('Slime Party', pay: 1, window: '')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Paying 1 (4 minus three different elements) resolved Slime Party')
    ->tested('Slime Party')
    ->covers(['cost-reduction'], 'This card costs 1 less to activate for each different element Slime ally you control.')
    ->register();

// ---- Allowance Race ----

gaScenario('allowance-race-resting-a-horse-ally-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Allowance Race')
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Allowance Race')
    ->castSpell('Allowance Race', pay: 3, window: '')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Horse ally was rested')
    ->expectZoneCount('myHand', 5, 1, 'Its controller drew a card (8 - 1 - 3 + 1)')
    ->tested('Allowance Race')
    ->covers(['rest', 'draw'], 'Each player may rest up to two Horse allies they control. For each Horse ally rested this way, its controller draws a card.')
    ->register();

// ---- Decompose ----

gaScenario('decompose-sacrifices-an-ally-and-gathers-for-its-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Decompose')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Decompose')
    ->play('Decompose')->answer(1, 'myField-1')->pay(2)
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'The ally was sacrificed as the additional cost')
    ->expectZoneCount('myField', 4, 1, 'Gather X = the sacrificed ally\'s 3 life: three plant tokens beside the champion')
    ->tested('Decompose')
    ->covers(['additional-cost', 'sacrifice', 'gather'], 'As an additional cost to activate this card, sacrifice an ally.', '**Gather X** times, where **X** is the sacrificed ally’s life stat.')
    ->register();

// ---- Liturgy of Corruption ----

gaScenario('liturgy-of-corruption-puts-a-buff-counter-on-the-target-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Liturgy of Corruption')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'An unfostered ally got one buff counter')
    ->tested('Liturgy of Corruption')
    ->covers(['counter', 'targeting'], 'Put a **buff** counter on target ally.')
    ->register();

gaScenario('liturgy-of-corruption-a-fostered-ally-gets-three-counters-and-you-take-six-unpreventable-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Liturgy of Corruption')
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['FOSTERED']])
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":3}', 'A fostered ally got two additional buff counters')
    ->expectDamage('p1Field-0', 6, 'Six unpreventable damage to your champion')
    ->tested('Liturgy of Corruption')
    ->covers(['foster', 'counter', 'damage', 'condition'], 'If that ally is **fostered**, put two additional **buff** counters on it and deal 6 unpreventable damage to your champion.')
    ->register();

// ---- Tend the Land ----

gaScenario('tend-the-land-gathers-then-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tend the Land')
    ->seed('myHand', 'Tend the Land')
    ->castSpell('Tend the Land', pay: 3, window: '')
    ->expectZoneCount('myField', 2, 1, 'Gather: a plant token joined the champion')
    ->expectZoneCount('myHand', 5, 1, 'A card was drawn (8 - 1 - 3 + 1)')
    ->tested('Tend the Land')
    ->covers(['gather', 'draw'], '**Gather**, then draw a card.')
    ->register();

// ---- Surprise Reveal ----

gaScenario('surprise-reveal-gains-crowds-favor-and-class-bonus-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Surprise Reveal', classBonus: true)
    ->seed('myHand', 'Surprise Reveal')
    ->castSpell('Surprise Reveal', pay: 2, window: '')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'gpmJdGYqoC', 'You gained the Crowd\'s Favor status')
    ->expectZoneCount('myHand', 6, 1, '[Class Bonus] A card was drawn (8 - 1 - 2 + 1)')
    ->tested('Surprise Reveal')
    ->covers(['status', 'class-bonus', 'draw', 'reveal'], 'Target opponent reveals all cards in their hand and memory. You gain the Crowd\'s Favor status.', '[Class Bonus] Draw a card.')
    ->register();
