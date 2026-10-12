<?php
/**
 * Phase 3b (Wind): actions, fifth file -- tokens, materialize, look-and-reorder and replacement effects.
 */

// ---- Purging Tempest ----

gaScenario('purging-tempest-banishes-cards-entering-your-graveyard-this-turn')
    ->standardDeck('Purging Tempest')
    ->readyFor('Purging Tempest')
    ->seed('myHand', 'Purging Tempest')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Purging Tempest', pay: 2, window: 'me', target: 'myField-0')
    ->castSpell('Nascent Blast', pay: 3, window: '', target: 'theirField-0')
    ->expectCardExists('myBanish', 'Nascent Blast', 'Target player is you: the resolved Blast was banished instead of entering the graveyard')
    ->expectCardExists('myGraveyard', 'Purging Tempest', 'Only the Tempest itself is in the graveyard (it moved there before its own effect began)')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Blast did not join it')
    ->tested('Purging Tempest')
    ->covers(['targeting', 'zone-movement', 'condition'], 'Until end of turn, if one or more cards would enter target player\'s graveyard from anywhere except the field, banish them instead.')
    ->register();

gaScenario('purging-tempest-targeting-the-opponent-leaves-your-graveyard-alone')
    ->standardDeck('Purging Tempest')
    ->readyFor('Purging Tempest')
    ->seed('myHand', 'Purging Tempest')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Purging Tempest', pay: 2, window: 'me', target: 'theirField-0')
    ->castSpell('Nascent Blast', pay: 3, window: '', target: 'theirField-0')
    ->expectCardExists('myGraveyard', 'Nascent Blast', 'Target player is the opponent: your Blast still enters your graveyard')
    ->tested('Purging Tempest')
    ->covers(['targeting', 'condition'], 'if one or more cards would enter target player\'s graveyard')
    ->register();

// ---- Silvergale Obelith's Call ----

gaScenario('silvergale-obeliths-call-summons-a-memorite-obelith-with-a-sheen-counter')
    ->standardDeck('Silvergale Obelith\'s Call')
    ->readyFor('Silvergale Obelith\'s Call')
    ->seed('myHand', 'Silvergale Obelith\'s Call')
    ->castSpell('Silvergale Obelith\'s Call', pay: 2, window: 'me')
    ->expectCardExists('myField', 'Memorite Obelith', 'The token was summoned')
    ->expectProperty('p1Field-1', 'Counters', '{"sheen":1}', 'It has a sheen counter')
    ->tested('Silvergale Obelith\'s Call')
    ->covers(['counter', 'token'], '**Summon** a Memorite Obelith token with a **sheen** counter on it.')
    ->register();

// ---- Wildheart Hymn ----

gaScenario('wildheart-hymn-puts-a-buff-counter-on-an-animal-that-enters')
    ->standardDeck('Wildheart Hymn')
    ->readyFor('Wildheart Hymn')
    ->seed('myHand', 'Wildheart Hymn')
    ->seed('myHand', 'Woodland Squirrels')
    ->castSpell('Wildheart Hymn', pay: 2, window: 'me')
    ->castSpell('Woodland Squirrels', window: 'me')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Animal that entered got a buff counter')
    ->tested('Wildheart Hymn')
    ->covers(['counter', 'trigger', 'stat-change'], 'Until end of turn, whenever an Animal enters the field under your control, put a **buff** counter on it.')
    ->register();

gaScenario('wildheart-hymn-a-non-animal-ally-gets-no-counter')
    ->standardDeck('Wildheart Hymn')
    ->readyFor('Wildheart Hymn')
    ->seed('myHand', 'Wildheart Hymn')
    ->seed('myHand', 'Dungeon Guide')
    ->castSpell('Wildheart Hymn', pay: 2, window: 'me')
    ->castSpell('Dungeon Guide', pay: 3)
    ->expectCardExists('myField', 'Dungeon Guide', 'The Human entered')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'A Human is not an Animal: no buff counter')
    ->tested('Wildheart Hymn')
    ->covers(['trigger', 'condition'], 'whenever an Animal enters the field under your control')
    ->register();

// ---- Beseech the Winds ----

gaScenario('beseech-the-winds-materializes-a-card-and-you-pay-its-cost')
    ->standardDeck('Beseech the Winds')
    ->readyFor('Beseech the Winds')
    ->seed('myHand', 'Beseech the Winds')
    ->castSpell('Beseech the Winds', pay: 3, window: 'me')->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Lorraine, Wandering Warrior', 'The chosen material card was materialized (the champion levelled into it)')
    ->expectZoneCount('myMaterial', 3, 1, 'It left the material deck')
    ->expectZoneCount('myMemory', 2, 1, 'Beseech put 3 cards into memory; materializing paid one of them')
    ->tested('Beseech the Winds')
    ->covers(['cost', 'materialize'], 'Materialize a card from your material deck. *(You still pay for its costs.)*')
    ->register();

// ---- Scout the Land ----

gaScenario('scout-the-land-reorders-the-top-four-and-draws-the-new-top-card')
    ->standardDeck('Scout the Land')
    ->readyFor('Scout the Land')
    ->deckTop(['Dungeon Guide', 'Gray Wolf', 'Owl Familiar', 'Woodland Squirrels'])
    ->seed('myHand', 'Scout the Land')
    ->castSpell('Scout the Land', pay: 2, window: 'me')
    ->answer(1, 'Top=6W5AJwF3Y3,WShYN9M3lU,hJ2xh9lNMR,em6eEh9q8y;Bottom=')
    ->expectCardExists('myHand', 'Woodland Squirrels', 'The new top card (Woodland Squirrels, formerly last of the four) was drawn')
    ->expectCardExists('myDeck', 'Owl Familiar', 'The rest stayed on top in the chosen order')
    ->tested('Scout the Land')
    ->covers(['draw-discard', 'look'], 'Look at the top four cards of your deck and put them back in any order. Draw a card.')
    ->register();

// ---- Vengeful Gust ----

gaScenario('vengeful-gust-suppresses-an-opposing-ally')
    ->standardDeck('Vengeful Gust')
    ->readyFor('Vengeful Gust', level: 2)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Vengeful Gust')
    ->castSpell('Vengeful Gust', pay: 3, window: 'me', target: 'theirField-1')
    ->expectZoneCount('theirBanish', 1, 1, 'The opposing ally was banished (suppressed)')
    ->expectZoneCount('theirField', 1, 1, 'Only their champion remains')
    ->tested('Vengeful Gust')
    ->covers(['suppress', 'targeting', 'level-gate'], '**Suppress** target ally you don\'t control.')
    ->register();

gaScenario('vengeful-gust-level-three-returning-ally-deals-four-damage-to-its-controllers-champion')
    ->standardDeck('Vengeful Gust')
    ->readyFor('Vengeful Gust', level: 3)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Vengeful Gust')
    ->castSpell('Vengeful Gust', pay: 3, window: 'me', target: 'theirField-1')
    ->endTurn()->pass(1)->pass(1)
    ->expectDamage('p2Field-0', 4, 'The suppressed ally re-entered with the On Enter: 4 damage to its controller\'s champion')
    ->expectCardExists('theirField', 'Giant Tortoise', 'It returned at the end phase')
    ->tested('Vengeful Gust')
    ->covers(['level-gate', 'on-enter', 'damage', 'suppress'], '[Level 3+] The next time that card would enter the field this turn, it enters with "**On Enter:** Deal 4 damage to your champion."')
    ->register();

gaScenario('vengeful-gust-below-level-three-the-ally-returns-without-the-damage')
    ->standardDeck('Vengeful Gust')
    ->readyFor('Vengeful Gust', level: 2)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Vengeful Gust')
    ->castSpell('Vengeful Gust', pay: 3, window: 'me', target: 'theirField-1')
    ->endTurn()->pass(1)->pass(1)
    ->expectDamage('p2Field-0', 0, 'Level 2: no On Enter damage')
    ->expectCardExists('theirField', 'Giant Tortoise', 'It returned at the end phase')
    ->tested('Vengeful Gust')
    ->covers(['level-gate'], '[Level 3+] The next time that card would enter the field this turn')
    ->register();

// ---- Bagua of Cardinal Fate ----

gaScenario('bagua-of-cardinal-fate-north-puts-a-buff-counter-on-an-ally')
    ->standardDeck('Bagua of Cardinal Fate')
    ->readyFor('Bagua of Cardinal Fate')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Bagua of Cardinal Fate')
    ->castSpell('Bagua of Cardinal Fate', pay: 1, window: 'me')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'North: the ally got a buff counter')
    ->tested('Bagua of Cardinal Fate')
    ->covers(['counter', 'targeting'], 'Depending on your Shifting Currents\' direction—', '• North— Put a **buff** counter on an ally you control.')
    ->register();

gaScenario('bagua-of-cardinal-fate-south-returns-an-ally-to-your-hand')
    ->standardDeck('Bagua of Cardinal Fate')
    ->readyFor('Bagua of Cardinal Fate')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Bagua of Cardinal Fate')
    ->castSpell('Bagua of Cardinal Fate', pay: 1, window: 'me')->answer(1, 'myField-1')
    ->expectCardExists('myHand', 'Giant Tortoise', 'South: the ally returned to hand')
    ->expectZoneCount('myField', 1, 1, 'Only the champion remains on the field')
    ->tested('Bagua of Cardinal Fate')
    ->covers(['zone-movement', 'targeting'], '• South— Return an ally you control to your owner\'s hand.')
    ->register();

gaScenario('bagua-of-cardinal-fate-west-glimpses-three-and-draws-a-card')
    ->standardDeck('Bagua of Cardinal Fate')
    ->readyFor('Bagua of Cardinal Fate')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->deckTop(['Dungeon Guide', 'Gray Wolf', 'Owl Familiar'])
    ->seed('myHand', 'Bagua of Cardinal Fate')
    ->castSpell('Bagua of Cardinal Fate', pay: 1, window: 'me')->answer(1, 'Top=hJ2xh9lNMR,em6eEh9q8y,WShYN9M3lU;Bottom=')
    ->expectCardExists('myHand', 'Gray Wolf', 'West: glimpse 3 put the Wolf on top, then the draw took it')
    ->tested('Bagua of Cardinal Fate')
    ->covers(['glimpse', 'draw-discard'], '• West— **Glimpse 3**. Draw a card.')
    ->register();

gaScenario('bagua-of-cardinal-fate-east-wakes-only-the-defending-ally')
    ->standardDeck('Bagua of Cardinal Fate')
    ->readyFor('Bagua of Cardinal Fate')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myHand', 'Bagua of Cardinal Fate')
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(1)->pass(1)
    ->expectProperty('p1Field-1', 'Status', '2', 'East: the defending ally woke up')
    ->expectProperty('p1Field-2', 'Status', '1', 'East: an ally that is not defending stays rested')
    ->expectPower('p1Field-1', 3, 'East: the defending Giant Tortoise has 1 + 2 power')
    ->expectPower('p1Field-2', 1, 'East: the other Giant Tortoise keeps its printed 1 power')
    ->tested('Bagua of Cardinal Fate')
    ->covers(['status', 'stat-change'], '• East— Wake up each defending ally. They each get +2 [POWER] until end of turn.')
    ->register();

// ---- Aenean Cyclic Winds ----

gaScenario('aenean-cyclic-winds-level-three-class-bonus-costs-two-less-and-returns-a-spell-to-memory')
    ->standardDeck('Aenean Cyclic Winds')
    ->readyFor('Aenean Cyclic Winds', classBonus: true, level: 3)
    ->seed('myGraveyard', 'Nascent Blast')
    ->seed('myHand', 'Aenean Cyclic Winds')
    ->castSpell('Aenean Cyclic Winds', pay: 3, window: 'me', target: 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Nascent Blast', 'The Spell card moved from the graveyard to memory')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only the Cyclic Winds itself is left in the graveyard')
    ->expectZoneCount('myMemory', 4, 1, 'Class Bonus at Level 3+: cost 5 - 2 = 3, plus the returned Spell')
    ->tested('Aenean Cyclic Winds')
    ->covers(['class-bonus', 'level-gate', 'cost-reduction', 'zone-movement', 'targeting'], '[Class Bonus] [Level 3+] This card costs 2 less to activate.', 'Return target Spell card from your graveyard to your memory.')
    ->register();

gaScenario('aenean-cyclic-winds-without-the-class-bonus-pays-the-full-five')
    ->standardDeck('Aenean Cyclic Winds')
    ->readyFor('Aenean Cyclic Winds', level: 3)
    ->seed('myGraveyard', 'Nascent Blast')
    ->seed('myHand', 'Aenean Cyclic Winds')
    ->castSpell('Aenean Cyclic Winds', pay: 5, window: 'me', target: 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Nascent Blast', 'The Spell card still returns without the class bonus')
    ->expectZoneCount('myMemory', 6, 1, 'Full cost 5, plus the returned Spell')
    ->tested('Aenean Cyclic Winds')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] [Level 3+] This card costs 2 less to activate.')
    ->register();

gaScenario('aenean-cyclic-winds-level-five-returns-two-wind-cards-from-memory-and-empowers')
    ->standardDeck('Aenean Cyclic Winds')
    ->readyFor('Aenean Cyclic Winds', classBonus: true, level: 5)
    ->seed('myGraveyard', 'Nascent Blast')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Galvanizing Gale')
    ->seed('myHand', 'Aenean Cyclic Winds')
    ->castSpell('Aenean Cyclic Winds', pay: 3, window: 'me', target: 'myGraveyard-0')->answer(1, 'myMemory-0&myMemory-1')
    ->expectCardExists('myHand', 'Fairy Whispers', 'Level 5+: the first Wind card went to hand')
    ->expectCardExists('myHand', 'Galvanizing Gale', 'Level 5+: the second Wind card went to hand')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","3GjTPUfsp3","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 marks the champion (EMPOWERED, EMPOWER_PLUS_2)')
    ->tested('Aenean Cyclic Winds')
    ->covers(['class-bonus', 'level-gate', 'reveal-look', 'zone-movement'], '[Class Bonus] [Level 5+] Reveal up to two wind element cards from your memory and return them to your hand. Then **empower 2**.')
    ->register();

gaScenario('aenean-cyclic-winds-below-level-five-returns-only-the-spell')
    ->standardDeck('Aenean Cyclic Winds')
    ->readyFor('Aenean Cyclic Winds', classBonus: true, level: 4)
    ->seed('myGraveyard', 'Nascent Blast')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Aenean Cyclic Winds')
    ->castSpell('Aenean Cyclic Winds', pay: 3, window: 'me', target: 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Fairy Whispers', 'Level 4: the Wind card stays in memory')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', 'No empower below level 5: only the activation marker')
    ->tested('Aenean Cyclic Winds')
    ->covers(['level-gate', 'class-bonus'], '[Class Bonus] [Level 5+] Reveal up to two wind element cards from your memory')
    ->register();

// ---- Enhance Potency ----

gaScenario('enhance-potency-class-bonus-copies-the-next-potion-sacrifice')
    ->standardDeck('Enhance Potency')
    ->readyFor('Enhance Potency', classBonus: true, damage: 12)
    ->seed('myField', 'Potion of Healing')
    ->seed('myHand', 'Enhance Potency')
    ->castSpell('Enhance Potency', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Potion was rested (checked before it is sacrificed)', 5)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->expectDamage('p1Field-0', 2, 'The sacrifice recovered 5 twice (the copy): 12 - 5 - 5')
    ->expectZoneCount('myMemory', 2, 1, 'Class Bonus: cost 3 - 1 = 2')
    ->tested('Enhance Potency')
    ->covers(['class-bonus', 'cost-reduction', 'status'], 'Rest target Potion. If you do, the next time you activate an ability of that Potion this turn, copy that ability.')
    ->register();

gaScenario('enhance-potency-without-the-class-bonus-pays-three-and-the-copy-still-happens')
    ->standardDeck('Enhance Potency')
    ->readyFor('Enhance Potency', damage: 12)
    ->seed('myField', 'Potion of Healing')
    ->seed('myHand', 'Enhance Potency')
    ->castSpell('Enhance Potency', pay: 3, window: 'me', target: 'myField-1')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->expectDamage('p1Field-0', 2, 'The copy still happens without the class bonus')
    ->expectZoneCount('myMemory', 3, 1, 'Full cost 3')
    ->tested('Enhance Potency')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();
