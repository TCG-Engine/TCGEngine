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

// ---- Skirting Step (Imbue 2) ----
// Imbue: the YESNO comes before payment; YES reveals the reserved cards (hand picks), and two Wind cards among them make the card imbued.

gaScenario('skirting-step-imbued-makes-the-unit-distant-prevents-one-damage-and-draws')
    ->standardDeck('Skirting Step')
    ->readyFor('Skirting Step')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Skirting Step')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->play('Skirting Step')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT","PREVENT_ALL_1"]', 'Distant, plus the next-1-damage prevention')
    ->expectZoneCount('myHand', 8, 1, 'Imbued: a card was drawn (7 left after the play and the payment, +1)')
    ->tested('Skirting Step')
    ->covers(['imbue', 'distant', 'prevent-damage', 'draw-discard', 'targeting'], 'Target unit becomes **distant**. If CARDNAME is **imbued**, prevent the next 1 damage that would be dealt to that unit this turn and draw a card.')
    ->register();

gaScenario('skirting-step-not-imbued-only-makes-the-unit-distant')
    ->standardDeck('Skirting Step')
    ->readyFor('Skirting Step')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Skirting Step')
    ->play('Skirting Step')->answer(1, 'NO')->pay(2)->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'Only distant')
    ->expectZoneCount('myHand', 5, 1, 'Not imbued: no card drawn')
    ->tested('Skirting Step')
    ->covers(['imbue', 'distant', 'targeting'], 'Target unit becomes **distant**.')
    ->register();

gaScenario('skirting-step-one-wind-card-revealed-is-not-enough-to-imbue')
    ->standardDeck('Skirting Step')
    ->readyFor('Skirting Step')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Skirting Step')
    ->seed('myHand', 'Fairy Whispers')
    ->play('Skirting Step')->answer(1, 'YES')->answer(1, 'myHand-7')->pay(1)->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'Only distant: one Wind card of two revealed')
    ->tested('Skirting Step')
    ->covers(['imbue'], 'If at least two of them are wind element, this card becomes **imbued**.')
    ->register();

// ---- Cultivate (Imbue 1) ----

foreach ([
    ['cultivate-imbued-with-three-herbs-gathers-twice', 3, true, 5, 'Three herbs: one gather, plus one more because it is imbued'],
    ['cultivate-not-imbued-with-three-herbs-gathers-once', 3, false, 4, 'Three herbs: one gather'],
    ['cultivate-not-imbued-with-two-herbs-does-not-gather', 2, false, 2, 'Two herbs are not enough for a gather'],
    ['cultivate-imbued-without-herbs-gathers-once', 0, true, 1, 'No herbs: only the imbued gather'],
] as [$slug, $herbs, $imbue, $expect, $why]) {
    $g = gaScenario($slug)
        ->standardDeck('Cultivate')
        ->readyFor('Cultivate');
    for ($h = 0; $h < $herbs; $h++) $g->seed('myField', 'Blightroot');
    if ($imbue) {
        $g->seed('myHand', 'Cultivate')->seed('myHand', 'Fairy Whispers')
          ->play('Cultivate')->answer(1, 'YES')->answer(1, 'myHand-7')->pay(1)->pass(1);
    } else {
        $g->seed('myHand', 'Cultivate')
          ->play('Cultivate')->answer(1, 'NO')->pay(2)->pass(1);
    }
    $g->expectZoneCount('myField', $expect + 1, 1, $why)
      ->tested('Cultivate')
      ->covers(['imbue', 'gather', 'condition'], 'For every three Herb items you control,  **gather**. Then if CARDNAME is **imbued**, **gather** an additional time.')
      ->register();
}

// ---- Squallbind Pounce (Imbue 2, Reaction) ----

gaScenario('squallbind-pounce-imbued-makes-the-ranger-distant-and-suppresses-the-attacking-ally')
    ->standardDeck('Squallbind Pounce')
    ->readyFor('Squallbind Pounce', classBonus: true)
    ->seed('myHand', 'Squallbind Pounce')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->pass(1)->answer(1, 'myField-0')->answer(1, 'theirField-1')->pass(1)->pass(1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The Ranger champion became distant (a Ranger champion is a Ranger unit)')
    ->expectCardExists('theirBanish', 'Giant Tortoise', 'Imbued: the attacking ally was suppressed (banished)')
    ->expectDamage('p1Field-0', 0, 'The suppressed attacker dealt no damage')
    ->tested('Squallbind Pounce')
    ->covers(['imbue', 'distant', 'suppress', 'targeting'], 'Up to one target Ranger unit becomes **distant**.', 'If Squallbind Pounce is **imbued**, **suppress** up to one target attacking ally.')
    ->register();

gaScenario('squallbind-pounce-not-imbued-only-makes-the-ranger-distant')
    ->standardDeck('Squallbind Pounce')
    ->readyFor('Squallbind Pounce', classBonus: true)
    ->seed('myHand', 'Squallbind Pounce')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->answer(1, 'NO')->pay(2)->pass(1)->answer(1, 'myField-0')->pass(1)->pass(1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","vz4kc558yx-ACTION_ACTIVATED","DISTANT","DAMAGED_SINCE_LAST_TURN"]', 'The Ranger champion became distant (a Ranger champion is a Ranger unit)')
    ->expectDamage('p1Field-0', 1, 'Not imbued: the attack was not stopped (1 damage)')
    ->tested('Squallbind Pounce')
    ->covers(['imbue', 'distant', 'condition'], 'If Squallbind Pounce is **imbued**, **suppress** up to one target attacking ally.')
    ->register();

// ---- Verdigris Decree (Imbue 3, modal) ----
// MZMODAL answers are mode indexes; two imbued modes are comma-separated ('1,2').

gaScenario('verdigris-decree-not-imbued-suppresses-an-ally')
    ->standardDeck('Verdigris Decree')
    ->readyFor('Verdigris Decree')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Verdigris Decree')
    ->play('Verdigris Decree')->answer(1, 'NO')->pay(3)->pass(1)->answer(1, '0')->answer(1, 'myField-1')
    ->expectZoneCount('myBanish', 1, 1, 'Suppress banished the ally')
    ->expectZoneCount('myField', 1, 1, 'Only the champion remains')
    ->tested('Verdigris Decree')
    ->covers(['imbue', 'modal-choice', 'suppress', 'targeting'], 'Choose one. If CARDNAME is **imbued**, choose two instead—', '• **Suppress** up to one target ally.')
    ->register();

gaScenario('verdigris-decree-not-imbued-gives-an-ally-plus-two-power')
    ->standardDeck('Verdigris Decree')
    ->readyFor('Verdigris Decree')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Verdigris Decree')
    ->play('Verdigris Decree')->answer(1, 'NO')->pay(3)->pass(1)->answer(1, '1')->answer(1, 'myField-1')
    ->expectPower('p1Field-1', 3, 'Giant Tortoise 1 + 2 power')
    ->tested('Verdigris Decree')
    ->covers(['modal-choice', 'stat-change', 'targeting'], '• Up to one target ally gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('verdigris-decree-not-imbued-destroys-a-phantasia')
    ->standardDeck('Verdigris Decree')
    ->readyFor('Verdigris Decree')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk', player: 2)
    ->seed('myHand', 'Verdigris Decree')
    ->play('Verdigris Decree')->answer(1, 'NO')->pay(3)->pass(1)->answer(1, '2')->answer(1, 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The phantasia was destroyed: only their champion remains')
    ->tested('Verdigris Decree')
    ->covers(['modal-choice', 'destroy', 'targeting'], '• Destroy up to one target phantasia.')
    ->register();

gaScenario('verdigris-decree-imbued-chooses-two-modes-power-and-destroy')
    ->standardDeck('Verdigris Decree')
    ->readyFor('Verdigris Decree')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk', player: 2)
    ->seed('myHand', 'Verdigris Decree')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->play('Verdigris Decree')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->pass(1)->answer(1, '1,2')->answer(1, 'myField-1')->answer(1, 'theirField-1')
    ->expectPower('p1Field-1', 3, 'Imbued: the +2 power mode happened')
    ->expectZoneCount('theirField', 1, 1, 'Imbued: the destroy mode happened too')
    ->tested('Verdigris Decree')
    ->covers(['imbue', 'modal-choice'], 'Choose one. If CARDNAME is **imbued**, choose two instead—')
    ->register();

// ---- Aethercharge actions: the load clause ----
// "You may load CARDNAME into an Aetherwing weapon you control": a YESNO after the effect; YES moves the card into the weapon's Subcards.

gaScenario('teasing-aerocharge-suppresses-an-ally-then-loads-into-the-aetherwing')
    ->standardDeck('Teasing Aerocharge')
    ->readyFor('Teasing Aerocharge')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Teasing Aerocharge')
    ->castSpell('Teasing Aerocharge', pay: 2, window: 'me', target: 'myField-1')->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Subcards', '["6lkv3tu69l"]', 'The Aerocharge is loaded in the weapon (the weapon moved down a slot when the ally was suppressed)')
    ->expectZoneCount('myGraveyard', 0, 1, 'It left the graveyard')
    ->expectZoneCount('myBanish', 1, 1, 'The suppressed ally is banished')
    ->tested('Teasing Aerocharge')
    ->covers(['suppress', 'load', 'targeting'], '**Suppress** target ally.', 'Then you may load Teasing Aerocharge into an Aetherwing weapon you control.')
    ->register();

gaScenario('teasing-aerocharge-declining-the-load-leaves-it-in-the-graveyard')
    ->standardDeck('Teasing Aerocharge')
    ->readyFor('Teasing Aerocharge')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Teasing Aerocharge')
    ->castSpell('Teasing Aerocharge', pay: 2, window: 'me', target: 'myField-1')->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Subcards', '[]', 'Nothing loaded')
    ->expectCardExists('myGraveyard', 'Teasing Aerocharge', 'The card stays in the graveyard')
    ->tested('Teasing Aerocharge')
    ->covers(['load'], 'you may load Teasing Aerocharge')
    ->register();

gaScenario('aethers-embrace-makes-a-unit-distant-and-loads-into-the-aetherwing')
    ->standardDeck('Aether\'s Embrace')
    ->readyFor('Aether\'s Embrace')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Aether\'s Embrace')
    ->castSpell('Aether\'s Embrace', pay: 2, window: 'me', target: 'myField-0')->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The champion became distant')
    ->expectProperty('p1Field-1', 'Subcards', '["wd7nuab7f3"]', 'Loaded into the Aetherwing')
    ->tested('Aether\'s Embrace')
    ->covers(['distant', 'load', 'targeting'], 'Target unit becomes **distant**.', 'You may load Aether\'s Embrace into an Aetherwing weapon you control.')
    ->register();

gaScenario('aethers-embrace-a-defending-ranger-ally-wakes-up-and-gets-plus-two-power-and-life')
    ->standardDeck('Aether\'s Embrace')
    ->readyFor('Aether\'s Embrace')
    ->seed('myField', 'Lone Gunslinger', properties: ['Status' => 1])
    ->seed('myHand', 'Aether\'s Embrace')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The defending Ranger ally woke up')
    ->expectPower('p1Field-1', 3, 'Lone Gunslinger 1 + 2 power')
    ->expectLife('p1Field-1', 3, 'Lone Gunslinger 1 + 2 life')
    ->tested('Aether\'s Embrace')
    ->covers(['status', 'stat-change', 'condition'], 'If that unit is a defending Ranger ally, wake it up and it gets +2[POWER] and +2[LIFE] until end of turn.')
    ->register();

// ---- Inspiring Aethercharge ----

gaScenario('inspiring-aethercharge-gives-allies-plus-one-power-and-loads')
    ->standardDeck('Inspiring Aethercharge')
    ->readyFor('Inspiring Aethercharge')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Inspiring Aethercharge')
    ->castSpell('Inspiring Aethercharge', pay: 2, window: 'me')->answer(1, 'YES')
    ->expectPower('p1Field-1', 2, 'Giant Tortoise 1 + 1 power')
    ->expectProperty('p1Field-2', 'Subcards', '["fPtTKILV7f"]', 'Loaded into the Aetherwing')
    ->tested('Inspiring Aethercharge')
    ->covers(['stat-change', 'load'], 'Allies you control get +1[POWER] until end of turn.', 'You may load CARDNAME into an Aetherwing weapon you control.')
    ->register();

// ---- Charging Galeshot ----

gaScenario('charging-galeshot-returns-the-attacking-ally-to-its-owners-memory-and-loads')
    ->standardDeck('Charging Galeshot')
    ->readyFor('Charging Galeshot')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Charging Galeshot')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->answer(1, 'theirField-1')->answer(1, 'YES')
    ->expectZoneCount('theirField', 1, 1, 'The attacking ally left the field')
    ->expectCardExists('theirMemory', 'Giant Tortoise', 'It went to its owner\'s memory')
    ->expectProperty('p1Field-1', 'Subcards', '["qu9wwsi17b"]', 'Loaded into the Aetherwing')
    ->tested('Charging Galeshot')
    ->covers(['zone-movement', 'load', 'targeting'], 'Return target attacking ally to its owner\'s memory.', 'Then you may load Charging Galeshot into an Aetherwing weapon you control.')
    ->register();

// ---- Fellowship's Gale ----

gaScenario('fellowships-gale-loads-into-the-aetherwing')
    ->standardDeck('Fellowship\'s Gale')
    ->readyFor('Fellowship\'s Gale')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Fellowship\'s Gale')
    ->castSpell('Fellowship\'s Gale', pay: 1, window: 'me')
    ->expectProperty('p1Field-1', 'Subcards', '["payjps7DkB"]', 'Loaded into the Aetherwing (not optional)')
    ->tested('Fellowship\'s Gale')
    ->covers(['load'], 'Load CARDNAME into an Aetherwing weapon you control.')
    ->register();

// ---- Melody and Harmony actions ----

gaScenario('sinfonia-of-hope-class-bonus-gives-life-and-makes-the-next-harmony-cost-two-less')
    ->standardDeck('Sinfonia of Hope')
    ->readyFor('Sinfonia of Hope', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sinfonia of Hope')
    ->seed('myHand', 'Anthem of Vitality')
    ->castSpell('Sinfonia of Hope', pay: 2, window: 'me', target: 'myField-1')
    ->castSpell('Anthem of Vitality', pay: 0, window: 'me', target: 'myField-1')->answer(1, 'myField-1')
    ->expectLife('p1Field-1', 13, 'Giant Tortoise 6 + 2 (Sinfonia) + 3 (Anthem) + 2 (two buff counters)')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Harmonize (a Melody was activated): two buff counters')
    ->expectZoneCount('myMemory', 2, 1, 'Class Bonus: the Harmony cost 2 - 2 = 0, only the Sinfonia was paid')
    ->tested('Sinfonia of Hope')
    ->covers(['melody', 'stat-change', 'class-bonus', 'cost-reduction', 'targeting'], 'Target ally gets +2[LIFE] until end of turn.', '[Class Bonus] The next Harmony card you activate this turn costs 2 less to activate.')
    ->register();

gaScenario('sinfonia-of-hope-without-the-class-bonus-the-harmony-costs-full')
    ->standardDeck('Sinfonia of Hope')
    ->readyFor('Sinfonia of Hope')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sinfonia of Hope')
    ->seed('myHand', 'Anthem of Vitality')
    ->castSpell('Sinfonia of Hope', pay: 2, window: 'me', target: 'myField-1')
    ->castSpell('Anthem of Vitality', pay: 2, window: 'me', target: 'myField-1')->answer(1, 'myField-1')
    ->expectLife('p1Field-1', 13, 'Giant Tortoise 6 + 2 (Sinfonia) + 3 (Anthem) + 2 (two buff counters)')
    ->expectZoneCount('myMemory', 4, 1, 'No class bonus: the Harmony paid its full 2')
    ->tested('Sinfonia of Hope')
    ->covers(['melody', 'class-bonus', 'cost-reduction'], '[Class Bonus] The next Harmony card you activate this turn costs 2 less to activate.')
    ->register();

gaScenario('anthem-of-vitality-without-a-melody-gives-life-and-spellshroud-but-no-counters')
    ->standardDeck('Anthem of Vitality')
    ->readyFor('Anthem of Vitality')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Anthem of Vitality')
    ->castSpell('Anthem of Vitality', pay: 2, window: 'me', target: 'myField-1')
    ->expectLife('p1Field-1', 9, 'Giant Tortoise 6 + 3')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No Melody this turn: Harmonize does nothing')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","vbgl6ffqsu-HP","SPELLSHROUD"]', 'Spellshroud until end of turn (and the +3 life marker)')
    ->tested('Anthem of Vitality')
    ->covers(['stat-change', 'spellshroud', 'melody', 'targeting', 'condition'], 'Target Animal or Beast ally gets +3 [LIFE] and gains **spellshroud** until end of turn.', '***Harmonize*** — If you\'ve activated a Melody card this turn, put two **buff** counters on an Animal or Beast ally you control.')
    ->register();

gaScenario('pluming-crescendo-after-a-melody-summons-two-fledglings-and-animals-get-plus-one-power')
    ->standardDeck('Pluming Crescendo')
    ->readyFor('Pluming Crescendo')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sinfonia of Hope')
    ->seed('myHand', 'Pluming Crescendo')
    ->castSpell('Sinfonia of Hope', pay: 2, window: 'me', target: 'myField-1')
    ->castSpell('Pluming Crescendo', pay: 3, window: 'me')
    ->expectZoneCount('myField', 4, 1, 'Harmonize: the champion, the Tortoise and two Fledgling tokens')
    ->expectCardExists('myField', 'Fledgling', 'A Fledgling token was summoned')
    ->expectPower('p1Field-1', 2, 'Giant Tortoise 1 + 1 (Animals get +1 power)')
    ->tested('Pluming Crescendo')
    ->covers(['melody', 'token', 'summon', 'stat-change'], '***Harmonize*** — If you\'ve activated a Melody card this turn, summon two Fledgling tokens.', 'Animals you control get +1 [POWER] until end of turn.')
    ->register();

gaScenario('pluming-crescendo-without-a-melody-only-gives-animals-power')
    ->standardDeck('Pluming Crescendo')
    ->readyFor('Pluming Crescendo')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Pluming Crescendo')
    ->castSpell('Pluming Crescendo', pay: 3, window: 'me')
    ->expectZoneCount('myField', 2, 1, 'No Melody this turn: no Fledglings')
    ->expectPower('p1Field-1', 2, 'Giant Tortoise 1 + 1')
    ->tested('Pluming Crescendo')
    ->covers(['melody', 'condition'], '***Harmonize*** — If you\'ve activated a Melody card this turn')
    ->register();
gaScenario('wuthering-sforzando-class-bonus-after-a-melody-costs-three-less-and-gives-the-next-attack-plus-six')
    ->standardDeck('Wuthering Sforzando')
    ->readyFor('Wuthering Sforzando', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sinfonia of Hope')
    ->seed('myHand', 'Wuthering Sforzando')
    ->castSpell('Sinfonia of Hope', pay: 2, window: 'me', target: 'myField-1')
    ->play('Wuthering Sforzando')->pay(2)->pass(1)->answer(1, 'myField-1')
    ->expectZoneCount('myMemory', 4, 1, 'Cost 7, -3 (Melody activated, Class Bonus) and -2 (Sinfonia\'s Harmony discount) = 2, plus the Sinfonia 2')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","7QmyDecqkk_LIFE","Nlw3ZpjSxw"]', 'The ally\'s next attack gets +6 power (the Nlw3ZpjSxw marker; 7QmyDecqkk_LIFE is Sinfonia\'s +2 life)')
    ->tested('Wuthering Sforzando')
    ->covers(['melody', 'class-bonus', 'cost-reduction', 'stat-change', 'targeting'], '[Class Bonus] As long as you\'ve activated a Melody card this turn, this card costs 3 less to activate.', 'Target ally\'s next attack this turn gets +6[POWER].')
    ->register();

gaScenario('wuthering-sforzando-class-bonus-without-a-melody-pays-the-full-seven')
    ->standardDeck('Wuthering Sforzando')
    ->readyFor('Wuthering Sforzando', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Wuthering Sforzando')
    ->play('Wuthering Sforzando')->pay(7)->answer(1, 'myField-1')
    ->expectZoneCount('myMemory', 7, 1, 'No Melody this turn: no reduction')
    ->tested('Wuthering Sforzando')
    ->covers(['melody', 'class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] As long as you\'ve activated a Melody card this turn, this card costs 3 less to activate.')
    ->register();

gaScenario('wuthering-sforzando-without-the-class-bonus-a-melody-does-not-reduce-the-cost')
    ->standardDeck('Wuthering Sforzando')
    ->readyFor('Wuthering Sforzando')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sinfonia of Hope')
    ->seed('myHand', 'Wuthering Sforzando')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->castSpell('Sinfonia of Hope', pay: 2, window: 'me', target: 'myField-1')
    ->play('Wuthering Sforzando')->pay(7)->answer(1, 'myField-1')
    ->expectZoneCount('myMemory', 9, 1, 'No class bonus: no reduction from either card, 2 + 7')
    ->tested('Wuthering Sforzando')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] As long as you\'ve activated a Melody card this turn')
    ->register();

// ---- Summon Gale ----

gaEfficiencyCase('summon-gale-class-bonus-efficiency-returns-the-ally-to-its-owners-hand', 'Summon Gale', true, 2, 1,
    fn($s) => $s->standardDeck('Summon Gale')->seed('myField', 'Giant Tortoise', player: 2), 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The opposing ally left the field')
    ->expectZoneCount('myMemory', 1, 1, 'Efficiency at level 2: cost 3 - 2 = 1')
    ->tested('Summon Gale')
    ->covers(['efficiency', 'class-bonus', 'cost-reduction', 'zone-movement', 'targeting'], '[Class Bonus] **Efficiency**', 'Return target ally to its owner\'s hand.')
    ->register();

gaEfficiencyCase('summon-gale-without-the-class-bonus-pays-the-full-three', 'Summon Gale', false, 2, 3,
    fn($s) => $s->standardDeck('Summon Gale')->seed('myField', 'Giant Tortoise'), 'myField-1')
    ->expectCardExists('myHand', 'Giant Tortoise', 'Your own ally returned to your hand')
    ->expectZoneCount('myMemory', 3, 1, 'No class bonus: full cost')
    ->tested('Summon Gale')
    ->covers(['efficiency', 'class-bonus'], '[Class Bonus] **Efficiency**')
    ->register();

// ---- Rondo of the Wind ----

gaScenario('rondo-of-the-wind-scavenge-six-puts-the-ally-in-hand-and-the-rest-on-the-bottom')
    ->standardDeck('Rondo of the Wind')
    ->readyFor('Rondo of the Wind', classBonus: true)
    ->deckTop(['Nascent Blast', 'Nascent Blast', 'Nascent Blast', 'Gray Wolf'])
    ->seed('myHand', 'Rondo of the Wind')
    ->castSpell('Rondo of the Wind', pay: 3, window: 'me')->answer(1, 'myTempZone-2')
    ->expectCardExists('myHand', 'Gray Wolf', 'The ally 4th from the top was found within six cards (the revealed cards are offered from a temp zone)')
    ->tested('Rondo of the Wind')
    ->covers(['scavenge', 'class-bonus', 'cost-reduction', 'melody'], '[Class Bonus] This card costs 1 less to activate.', '**Scavenge 6** for an ally card.')
    ->register();

gaScenario('rondo-of-the-wind-no-ally-within-six-cards-finds-nothing')
    ->standardDeck('Rondo of the Wind')
    ->readyFor('Rondo of the Wind', classBonus: true)
    ->deckTop(['Nascent Blast', 'Nascent Blast', 'Nascent Blast', 'Nascent Blast', 'Nascent Blast', 'Nascent Blast', 'Gray Wolf'])
    ->seed('myHand', 'Rondo of the Wind')
    ->castSpell('Rondo of the Wind', pay: 3, window: 'me')
    ->expectZoneCount('myHand', 4, 1, 'The Wolf is 7th: out of range, nothing added to the hand (8 - Rondo - 3 paid)')
    ->tested('Rondo of the Wind')
    ->covers(['scavenge'], '**Scavenge 6** for an ally card.')
    ->register();

// ---- Winds of Destiny ----
gaScenario('winds-of-destiny-resting-two-fatestones-replaces-the-reserve-cost-and-suppresses')
    ->standardDeck('Winds of Destiny')
    ->readyFor('Winds of Destiny')
    ->seed('myField', 'Fatestone of Revelations')
    ->seed('myField', 'Fatestone of Revelations')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Winds of Destiny')
    ->play('Winds of Destiny')->answer(1, 'YES')->answer(1, 'myField-1')->answer(1, 'myField-2')->pass(1)->answer(1, 'theirField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The first Fatestone was rested')
    ->expectProperty('p1Field-2', 'Status', '1', 'The second Fatestone was rested')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve cost was paid')
    ->expectZoneCount('theirBanish', 1, 1, 'Suppress banished the opposing ally')
    ->tested('Winds of Destiny')
    ->covers(['alt-cost', 'suppress', 'targeting'], 'You may rest two Fatestones you control rather than pay this card\'s reserve cost.', '**Suppress** target ally, item, or weapon.')
    ->register();

gaScenario('winds-of-destiny-declining-the-alternate-cost-pays-three')
    ->standardDeck('Winds of Destiny')
    ->readyFor('Winds of Destiny')
    ->seed('myField', 'Fatestone of Revelations')
    ->seed('myField', 'Fatestone of Revelations')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Winds of Destiny')
    ->play('Winds of Destiny')->answer(1, 'NO')->pay(3)->pass(1)->answer(1, 'theirField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Fatestones stay awake')
    ->expectZoneCount('myMemory', 3, 1, 'The reserve cost was paid')
    ->tested('Winds of Destiny')
    ->covers(['alt-cost'], 'You may rest two Fatestones you control rather than pay this card\'s reserve cost.')
    ->register();

gaScenario('winds-of-destiny-one-fatestone-does-not-offer-the-alternate-cost')
    ->standardDeck('Winds of Destiny')
    ->readyFor('Winds of Destiny')
    ->seed('myField', 'Fatestone of Revelations')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Winds of Destiny')
    ->play('Winds of Destiny')->pay(3)->pass(1)->answer(1, 'theirField-1')
    ->expectZoneCount('myMemory', 3, 1, 'Only one Fatestone: no alternate cost, the reserve cost is paid straight away')
    ->tested('Winds of Destiny')
    ->covers(['alt-cost', 'condition'], 'You may rest two Fatestones you control')
    ->register();

// ---- Rapid Reload ----
gaEfficiencyCase('rapid-reload-class-bonus-efficiency-materializes-a-bullet', 'Rapid Reload', true, 2, 1,
    fn($s) => $s->deck("# Material\n1 Spirit of Fire\n1 Windpiercer\n# Main\n4 Rapid Reload\n4 Dungeon Guide\n4 Fairy Whispers\n4 Fluffy Shopkeep"))->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Windpiercer', 'The Bullet was materialized')
    ->expectZoneCount('myMemory', 1, 1, 'Efficiency at level 2: cost 3 - 2 = 1')
    ->tested('Rapid Reload')
    ->covers(['efficiency', 'class-bonus', 'cost-reduction', 'materialize'], '[Class Bonus] **Efficiency**', 'Materialize a Bullet card from your material deck.')
    ->register();

gaEfficiencyCase('rapid-reload-without-the-class-bonus-pays-the-full-three', 'Rapid Reload', false, 2, 3,
    fn($s) => $s->deck("# Material\n1 Spirit of Fire\n1 Windpiercer\n# Main\n4 Rapid Reload\n4 Dungeon Guide\n4 Fairy Whispers\n4 Fluffy Shopkeep"))->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Windpiercer', 'The Bullet was materialized')
    ->expectZoneCount('myMemory', 3, 1, 'No class bonus: no Efficiency, full cost 3')
    ->tested('Rapid Reload')
    ->covers(['efficiency', 'class-bonus'], '[Class Bonus] **Efficiency**')
    ->register();

// ---- Twingale Parry ----
gaScenario('twingale-parry-prevents-two-of-the-four-combat-damage-to-the-champion')
    ->standardDeck('Twingale Parry')
    ->readyFor('Twingale Parry')
    ->seed('myHand', 'Twingale Parry')
    ->seed('myField', 'Suzaku, Vermillion Phoenix', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->answer(1, 'myField-0')->pass(1)->pass(1)
    ->expectDamage('p1Field-0', 2, 'Suzaku deals 4 combat damage; 2 of it was prevented')
    ->tested('Twingale Parry')
    ->covers(['prevent-damage', 'combat', 'targeting'], 'Prevent the next 2 combat damage that would be dealt to target unit this turn.')
    ->register();

gaScenario('twingale-parry-protects-an-ally-that-is-being-attacked')
    ->standardDeck('Twingale Parry')
    ->readyFor('Twingale Parry')
    ->seed('myHand', 'Twingale Parry')
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 2])
    ->seed('myField', 'Suzaku, Vermillion Phoenix', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->answer(1, 'myField-1')->pass(1)->pass(1)
    ->expectDamage('p1Field-1', 2, 'The Tortoise took 4 combat damage minus the 2 prevented')
    ->tested('Twingale Parry')
    ->covers(['prevent-damage', 'combat', 'targeting'], 'target unit')
    ->register();
// The graveyard copy is activated in the main phase: the response window that opens during an opponent's attack only lists hand cards
// and field abilities, so a Reaction cannot be ephemerated in response (see ENGINE-FINDINGS).
gaScenario('twingale-parry-class-bonus-ephemerate-from-the-graveyard-with-a-sword-banishes-it-after')
    ->standardDeck('Twingale Parry')
    ->readyFor('Twingale Parry', classBonus: true)
    ->seed('myGraveyard', 'Twingale Parry')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)->pass(1)->answer(1, 'myField-0')
    ->expectCardExists('myBanish', 'Twingale Parry', 'Ephemerate: it is banished as it resolves')
    ->expectZoneCount('myGraveyard', 0, 1, 'It left the graveyard')
    ->expectZoneCount('myMemory', 2, 1, 'Ephemerate (2) was paid')
    ->tested('Twingale Parry')
    ->covers(['ephemerate', 'class-bonus', 'prevent-damage'], '[Class Bonus] **Ephemerate — **(2). Activate this card this way only if you control a Sword weapon.')
    ->register();

gaScenario('twingale-parry-ephemerate-is-refused-without-a-sword-weapon')
    ->standardDeck('Twingale Parry')
    ->readyFor('Twingale Parry', classBonus: true)
    ->seed('myGraveyard', 'Twingale Parry')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->expectDqEmpty()
    ->expectCardExists('myGraveyard', 'Twingale Parry', 'Without a Sword weapon the click does nothing: the card stays in the graveyard (the engine accepts the click and ignores it)')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->tested('Twingale Parry')
    ->covers(['ephemerate', 'condition'], 'Activate this card this way only if you control a Sword weapon.')
    ->register();

// ---- Three Visits ----
gaScenario('three-visits-rests-the-champion-glimpses-two-and-recovers-two')
    ->standardDeck('Three Visits')
    ->readyFor('Three Visits', damage: 6)
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myHand', 'Three Visits')
    ->play('Three Visits')->pass(1)->answer(1, 'Top=em6eEh9q8y,hJ2xh9lNMR;Bottom=')
    ->expectProperty('p1Field-0', 'Status', '1', 'Additional cost: the champion was rested')
    ->expectDamage('p1Field-0', 4, 'Recover 2: 6 - 2')
    ->expectCardExists('myGraveyard', 'Three Visits', 'A hand copy resolves to the graveyard')
    ->tested('Three Visits')
    ->covers(['additional-cost', 'glimpse', 'recover'], 'As an additional cost to activate this card, rest your champion.', '**Glimpse 2** and **recover 2**.')
    ->register();

gaScenario('three-visits-class-bonus-from-the-graveyard-is-banished-as-it-resolves')
    ->standardDeck('Three Visits')
    ->readyFor('Three Visits', classBonus: true, damage: 6)
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myGraveyard', 'Three Visits')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pass(1)->answer(1, 'Top=em6eEh9q8y,hJ2xh9lNMR;Bottom=')
    ->expectCardExists('myBanish', 'Three Visits', 'Activated from the graveyard: banished as it resolves')
    ->expectDamage('p1Field-0', 4, 'Recover 2')
    ->tested('Three Visits')
    ->covers(['class-bonus', 'graveyard-activation'], '[Class Bonus] You may activate this card from your graveyard. If you do, banish it as it resolves.')
    ->register();

gaScenario('three-visits-class-bonus-from-the-banishment-goes-to-the-bottom-of-the-deck')
    ->standardDeck('Three Visits')
    ->readyFor('Three Visits', classBonus: true, damage: 6)
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myBanish', 'Three Visits')
    ->action(1, 10002, 'myBanish-0!FSM!')->pass(1)->answer(1, 'Top=em6eEh9q8y,hJ2xh9lNMR;Bottom=')
    ->expectCardExists('myDeck', 'Three Visits', 'Activated from the banishment: put on the bottom of the deck')
    ->expectZoneCount('myBanish', 0, 1, 'It left the banishment')
    ->expectDamage('p1Field-0', 4, 'Recover 2')
    ->tested('Three Visits')
    ->covers(['class-bonus', 'banish-activation'], '[Class Bonus] You may activate this card from your banishment. If you do, put it on the bottom of your deck as it resolves.')
    ->register();

gaScenario('three-visits-without-the-class-bonus-the-graveyard-copy-cannot-be-activated')
    ->standardDeck('Three Visits')
    ->readyFor('Three Visits', damage: 6)
    ->seed('myGraveyard', 'Three Visits')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->expectCardExists('myGraveyard', 'Three Visits', 'No class bonus: the click does nothing')
    ->expectDamage('p1Field-0', 6, 'Nothing recovered')
    ->tested('Three Visits')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] You may activate this card from your graveyard.')
    ->register();

gaScenario('three-visits-needs-an-awake-champion-to-rest-for-its-additional-cost')
    ->standardDeck('Three Visits')
    ->readyFor('Three Visits', damage: 6)
    ->patch('myField-0', ['Status' => 1])
    ->seed('myHand', 'Three Visits')
    ->play('Three Visits')
    ->expectCardExists('myHand', 'Three Visits', 'A rested champion cannot pay the additional cost: the card stays in hand')
    ->expectDamage('p1Field-0', 6, 'Nothing recovered')
    ->tested('Three Visits')
    ->covers(['additional-cost'], 'As an additional cost to activate this card, rest your champion.')
    ->register();
