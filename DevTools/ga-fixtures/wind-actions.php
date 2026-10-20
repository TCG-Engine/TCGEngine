<?php
/**
 * Phase 3b (Wind): actions, first file -- plain and class-bonus actions without a mechanic of their own.
 */

// ---- Aenean Reclaim ----

gaScenario('aenean-reclaim-level-3-returns-the-ally-and-draws-into-memory')
    ->standardDeck('Aenean Reclaim')
    ->readyFor('Aenean Reclaim', classBonus: true, level: 3)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Aenean Reclaim')
    ->castSpell('Aenean Reclaim', pay: 2, window: 'me', target: 'myField-1')
    ->expectCardExists('myHand', 'Giant Tortoise', 'The ally returned to its owner\'s hand')
    ->expectZoneCount('myField', 1, 1, 'Only the champion is left on the field')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid cards plus the card drawn into memory at Level 3+')
    ->tested('Aenean Reclaim')
    ->covers(['zone-movement', 'draw-into-memory', 'class-bonus', 'level-gate'], 'Return target ally you control to its owner’s hand.', '[Class Bonus] [Level 3+] Draw a card into your memory.')
    ->register();

gaScenario('aenean-reclaim-below-level-3-returns-the-ally-without-drawing')
    ->standardDeck('Aenean Reclaim')
    ->readyFor('Aenean Reclaim', classBonus: true, level: 2)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Aenean Reclaim')
    ->castSpell('Aenean Reclaim', pay: 2, window: 'me', target: 'myField-1')
    ->expectCardExists('myHand', 'Giant Tortoise', 'The ally still returned to hand')
    ->expectZoneCount('myMemory', 2, 1, 'Level 2: no card was drawn into memory')
    ->tested('Aenean Reclaim')
    ->covers(['level-gate', 'class-bonus'], '[Class Bonus] [Level 3+] Draw a card into your memory.')
    ->register();

// ---- Byakko's Command ----

gaScenario('byakkos-command-puts-a-buff-counter-on-a-beast')
    ->standardDeck('Byakko\'s Command')
    ->readyFor('Byakko\'s Command')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Byakko\'s Command')
    ->castSpell('Byakko\'s Command', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Beast got a buff counter')
    ->expectPower('p1Field-1', 3, 'A 2/2 Beast with a buff counter has 3 POWER')
    ->tested('Byakko\'s Command')
    ->covers(['targeting', 'counter'], 'Put a **buff** counter on target Beast ally. If that ally is a Shenju, wake it up.')
    ->register();

gaScenario('byakkos-command-wakes-a-rested-shenju')
    ->standardDeck('Byakko\'s Command')
    ->readyFor('Byakko\'s Command')
    ->seed('myField', 'Byakko, White Tiger', properties: ['Status' => 1])
    ->seed('myHand', 'Byakko\'s Command')
    ->castSpell('Byakko\'s Command', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Shenju woke up')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Shenju also got the buff counter')
    ->tested('Byakko\'s Command')
    ->covers(['status', 'counter'], 'Put a **buff** counter on target Beast ally. If that ally is a Shenju, wake it up.')
    ->register();

gaScenario('byakkos-command-does-not-wake-a-rested-non-shenju-beast')
    ->standardDeck('Byakko\'s Command')
    ->readyFor('Byakko\'s Command')
    ->seed('myField', 'Gray Wolf', properties: ['Status' => 1])
    ->seed('myHand', 'Byakko\'s Command')
    ->castSpell('Byakko\'s Command', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'A Beast that is not a Shenju stays rested')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'It still got the buff counter')
    ->tested('Byakko\'s Command')
    ->covers(['status', 'condition'], 'If that ally is a Shenju, wake it up.')
    ->register();

// ---- Displace ----

gaScenario('displace-banishes-and-returns-the-ally-rested-and-class-bonus-enlightens')
    ->standardDeck('Displace')
    ->readyFor('Displace', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Displace')
    ->castSpell('Displace', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The ally came back rested')
    ->expectCardExists('myField', 'Giant Tortoise', 'The ally is back on the field')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing stays banished')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"CLERIC,MAGE"},"enlighten":1}', 'Class Bonus: an enlighten counter on the champion')
    ->tested('Displace')
    ->covers(['zone-movement', 'class-bonus', 'counter'], 'Banish target ally, then return it to the field under its owner\'s control rested.', '[Class Bonus] Put an **enlighten** counter on your champion.')
    ->register();

gaScenario('displace-without-the-class-bonus-adds-no-enlighten-counter')
    ->standardDeck('Displace')
    ->readyFor('Displace')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Displace')
    ->castSpell('Displace', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The ally still came back rested')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No Class Bonus: no enlighten counter')
    ->tested('Displace')
    ->covers(['class-bonus'], '[Class Bonus] Put an **enlighten** counter on your champion.')
    ->register();

gaScenario('byakkos-command-can-target-an-opposing-beast')
    ->standardDeck('Byakko\'s Command')
    ->readyFor('Byakko\'s Command')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Byakko\'s Command')
    ->castSpell('Byakko\'s Command', pay: 2, window: 'me', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"buff":1}', 'The printed text says "target Beast ally", not "you control": an opposing Beast is a legal target')
    ->tested('Byakko\'s Command')
    ->covers(['targeting'], 'Put a **buff** counter on target Beast ally. If that ally is a Shenju, wake it up.')
    ->register();

// ---- Draught Dodge ----

gaScenario('draught-dodge-class-bonus-prevents-two-and-makes-a-non-distant-unit-distant')
    ->standardDeck('Draught Dodge')
    ->readyFor('Draught Dodge', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Draught Dodge')
    ->castSpell('Draught Dodge', pay: 1, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2","DISTANT"]', 'The next 2 damage is prevented and the non-distant unit became distant')
    ->expectZoneCount('myMemory', 1, 1, 'Not distant before: nothing was drawn into memory')
    ->tested('Draught Dodge')
    ->covers(['prevent-damage', 'distant', 'class-bonus', 'condition'], 'Prevent the next 2 damage that would be dealt to target unit this turn.', '[Class Bonus] If that unit is **distant**, draw a card into your memory. Otherwise, it becomes **distant**.')
    ->register();

gaScenario('draught-dodge-class-bonus-draws-into-memory-when-the-unit-is-already-distant')
    ->standardDeck('Draught Dodge')
    ->readyFor('Draught Dodge', classBonus: true)
    ->seed('myField', 'Giant Tortoise', properties: ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Draught Dodge')
    ->castSpell('Draught Dodge', pay: 1, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["DISTANT","PREVENT_ALL_2"]', 'Already distant: damage prevention only, no second DISTANT')
    ->expectZoneCount('myMemory', 2, 1, 'One paid card plus the card drawn into memory')
    ->tested('Draught Dodge')
    ->covers(['draw-into-memory', 'distant', 'condition'], '[Class Bonus] If that unit is **distant**, draw a card into your memory. Otherwise, it becomes **distant**.')
    ->register();

gaScenario('draught-dodge-without-the-class-bonus-only-prevents-damage')
    ->standardDeck('Draught Dodge')
    ->readyFor('Draught Dodge')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Draught Dodge')
    ->castSpell('Draught Dodge', pay: 1, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'No Class Bonus: not made distant')
    ->expectZoneCount('myMemory', 1, 1, 'No card drawn into memory')
    ->tested('Draught Dodge')
    ->covers(['class-bonus', 'prevent-damage'], '[Class Bonus] If that unit is **distant**, draw a card into your memory. Otherwise, it becomes **distant**.')
    ->register();

// ---- Enchaining Gale / Extortion Scheme (Suppress) ----

gaScenario('enchaining-gale-class-bonus-costs-one-less-and-suppresses-the-ally')
    ->standardDeck('Enchaining Gale')
    ->readyFor('Enchaining Gale', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Enchaining Gale')
    ->castSpell('Enchaining Gale', pay: 1, window: 'me', target: 'myField-1')
    ->expectZoneCount('myBanish', 1, 1, 'Suppress banishes the ally now')
    ->expectZoneCount('myField', 1, 1, 'Only the champion remains on the field')
    ->expectZoneCount('myMemory', 1, 1, 'Class Bonus: cost 2 - 1 = 1 was paid')
    ->tested('Enchaining Gale')
    ->covers(['suppress', 'class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate. *(Apply this effect only if your champion\'s class matches this card\'s class.)*', '**Suppress** target ally. *(To **suppress** an ally, banish it and return it to the field under its owner\'s control at the beginning of the next end phase.)*')
    ->register();

gaScenario('enchaining-gale-suppressed-ally-returns-at-the-end-phase')
    ->standardDeck('Enchaining Gale')
    ->readyFor('Enchaining Gale', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Enchaining Gale')
    ->castSpell('Enchaining Gale', pay: 1, window: 'me', target: 'myField-1')
    ->endTurn()->pass(1)->pass(1)
    ->expectCardExists('myField', 'Giant Tortoise', 'The suppressed ally came back under its owner\'s control')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing is left in the banish zone')
    ->tested('Enchaining Gale')
    ->covers(['suppress'], '**Suppress** target ally. *(To **suppress** an ally, banish it and return it to the field under its owner\'s control at the beginning of the next end phase.)*')
    ->register();

gaScenario('enchaining-gale-without-the-class-bonus-costs-the-full-two')
    ->standardDeck('Enchaining Gale')
    ->readyFor('Enchaining Gale')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Enchaining Gale')
    ->castSpell('Enchaining Gale', pay: 2, window: 'me', target: 'myField-1')
    ->expectZoneCount('myMemory', 2, 1, 'No Class Bonus: the printed cost 2 was paid')
    ->expectZoneCount('myBanish', 1, 1, 'The ally was still suppressed')
    ->tested('Enchaining Gale')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate. *(Apply this effect only if your champion\'s class matches this card\'s class.)*')
    ->register();

gaScenario('extortion-scheme-suppresses-an-opposing-ally-and-class-bonus-adds-a-preparation-counter')
    ->standardDeck('Extortion Scheme')
    ->readyFor('Extortion Scheme', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Extortion Scheme')
    ->castSpell('Extortion Scheme', pay: 2, window: 'me', target: 'theirField-1')
    ->expectZoneCount('theirBanish', 1, 1, 'The opposing ally was banished')
    ->expectZoneCount('theirField', 1, 1, 'Only the opposing champion remains')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":1}', 'Class Bonus: a preparation counter on the champion')
    ->tested('Extortion Scheme')
    ->covers(['suppress', 'class-bonus', 'counter'], '**Suppress** up to one target ally an opponent controls. *(To **suppress** an ally, banish it and return it to the field under its owner\'s control at the beginning of the next end phase.)*', '[Class Bonus] Put a **preparation** counter on your champion.')
    ->register();

gaScenario('extortion-scheme-without-the-class-bonus-adds-no-preparation-counter')
    ->standardDeck('Extortion Scheme')
    ->readyFor('Extortion Scheme')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Extortion Scheme')
    ->castSpell('Extortion Scheme', pay: 2, window: 'me', target: 'theirField-1')
    ->expectZoneCount('theirBanish', 1, 1, 'The opposing ally was still suppressed')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No Class Bonus: no preparation counter')
    ->tested('Extortion Scheme')
    ->covers(['class-bonus'], '[Class Bonus] Put a **preparation** counter on your champion.')
    ->register();

// ---- Fortifying Aroma ----

gaScenario('fortifying-aroma-costs-one-less-per-herb-and-puts-two-buff-counters')
    ->standardDeck('Fortifying Aroma')
    ->readyFor('Fortifying Aroma')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Fraysia')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Fortifying Aroma')
    ->castSpell('Fortifying Aroma', pay: 3, window: 'me', target: 'myField-3')
    ->expectZoneCount('myMemory', 3, 1, 'Two Herbs: cost 5 - 2 = 3 was paid')
    ->expectProperty('p1Field-3', 'Counters', '{"buff":2}', 'Two buff counters on the target ally')
    ->expectPower('p1Field-3', 3, 'Each buff counter is +1 POWER')
    ->tested('Fortifying Aroma')
    ->covers(['cost-reduction', 'counter'], 'This card costs 1 less to activate for each Herb you control.', 'Put two **buff** counters on target ally. *(Allies get +1 [POWER] and +1 [LIFE] for each **buff** counter on them.)*')
    ->register();

gaScenario('fortifying-aroma-without-herbs-costs-the-full-five')
    ->standardDeck('Fortifying Aroma')
    ->readyFor('Fortifying Aroma')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Fortifying Aroma')
    ->castSpell('Fortifying Aroma', pay: 5, window: 'me', target: 'myField-1')
    ->expectZoneCount('myMemory', 5, 1, 'No Herbs: the printed cost 5 was paid')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Two buff counters on the target ally')
    ->tested('Fortifying Aroma')
    ->covers(['cost-reduction'], 'This card costs 1 less to activate for each Herb you control.')
    ->register();

// ---- Oath of the Sakura ----

gaScenario('oath-of-the-sakura-three-unique-allies-get-a-buff-counter-and-plus-two-power')
    ->standardDeck('Oath of the Sakura')
    ->readyFor('Oath of the Sakura')
    ->seed('myField', 'Noire, Ace of Spades')
    ->seed('myField', 'Rouge, Ace of Hearts')
    ->seed('myField', 'Bleu, Ace of Diamonds')
    ->seed('myHand', 'Oath of the Sakura')
    ->castSpell('Oath of the Sakura', pay: 3, window: 'me')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A buff counter on each ally')
    ->expectPower('p1Field-1', 4, 'Exactly three unique allies: 1 base + 1 buff + 2 until end of turn')
    ->expectPower('p1Field-3', 4, 'The third unique ally gets the same bonus')
    ->tested('Oath of the Sakura')
    ->covers(['counter', 'stat-change', 'condition'], 'Put a **buff** counter on each ally you control.', 'If you control exactly three allies that are all unique, they each get +2 [POWER] until end of turn.')
    ->register();

gaScenario('oath-of-the-sakura-three-allies-that-are-not-all-unique-only-get-buff-counters')
    ->standardDeck('Oath of the Sakura')
    ->readyFor('Oath of the Sakura')
    ->seed('myField', 'Noire, Ace of Spades')
    ->seed('myField', 'Rouge, Ace of Hearts')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Oath of the Sakura')
    ->castSpell('Oath of the Sakura', pay: 3, window: 'me')
    ->expectPower('p1Field-1', 2, 'One ally is not unique: only the buff counter (1 base + 1)')
    ->expectProperty('p1Field-3', 'Counters', '{"buff":1}', 'The non-unique ally still got a buff counter')
    ->tested('Oath of the Sakura')
    ->covers(['condition'], 'If you control exactly three allies that are all unique, they each get +2 [POWER] until end of turn.')
    ->register();

gaScenario('oath-of-the-sakura-two-unique-allies-are-not-exactly-three')
    ->standardDeck('Oath of the Sakura')
    ->readyFor('Oath of the Sakura')
    ->seed('myField', 'Noire, Ace of Spades')
    ->seed('myField', 'Rouge, Ace of Hearts')
    ->seed('myHand', 'Oath of the Sakura')
    ->castSpell('Oath of the Sakura', pay: 3, window: 'me')
    ->expectPower('p1Field-1', 2, 'Two allies are not "exactly three": only the buff counter')
    ->tested('Oath of the Sakura')
    ->covers(['condition'], 'If you control exactly three allies that are all unique, they each get +2 [POWER] until end of turn.')
    ->register();

// ---- Galvanizing Gale ----

gaScenario('galvanizing-gale-gives-the-next-attack-plus-three-power')
    ->standardDeck('Red Slime')
    ->readyFor('Galvanizing Gale', player: 2)
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Galvanizing Gale', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->castSpell('Galvanizing Gale', pay: 2, window: 'me', target: 'myField-1', player: 2)
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 4, 'The next attack: 1 POWER + 3 = 4 damage')
    ->expectZoneCount('myMemory', 2, 2, 'Not empowered: nothing was drawn into memory')
    ->tested('Galvanizing Gale')
    ->covers(['stat-change', 'targeting', 'combat'], 'Target ally\'s next attack this turn gets +3 [POWER].')
    ->register();

gaScenario('galvanizing-gale-empowered-draws-a-card-into-memory')
    ->standardDeck('Galvanizing Gale')
    ->readyFor('Galvanizing Gale')
    ->champion()
    ->patch('myField-0', ['TurnEffects' => ['EMPOWERED']])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Galvanizing Gale')
    ->castSpell('Galvanizing Gale', pay: 2, window: 'me', target: 'myField-1')
    ->expectZoneCount('myMemory', 3, 1, 'Empowered: two paid cards plus the card drawn into memory')
    ->tested('Galvanizing Gale')
    ->covers(['empower', 'draw-into-memory', 'condition'], 'If CARDNAME was **empowered**, draw a card into your memory.')
    ->register();

// ---- Calming Breeze (a Reaction: cast in response to an attack on the champion) ----

gaScenario('calming-breeze-prevents-three-or-less-damage-to-the-champion')
    ->standardDeck('Calming Breeze')
    ->readyFor('Calming Breeze')
    ->seed('myHand', 'Calming Breeze')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->pass(1)
    ->expectProperty('p1Field-0', 'Damage', '0', '1 damage is 3 or less: prevented')
    ->tested('Calming Breeze')
    ->covers(['prevent-damage', 'combat'], 'Until end of turn, if 3 or less damage would be dealt to your champion, prevent that damage.')
    ->register();

gaScenario('calming-breeze-does-not-prevent-four-damage')
    ->standardDeck('Calming Breeze')
    ->readyFor('Calming Breeze')
    ->seed('myHand', 'Calming Breeze')
    ->seed('myField', 'Suzaku, Vermillion Phoenix', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->pass(1)
    ->expectProperty('p1Field-0', 'Damage', '4', '4 damage is more than 3: not prevented')
    ->tested('Calming Breeze')
    ->covers(['prevent-damage', 'condition'], 'Until end of turn, if 3 or less damage would be dealt to your champion, prevent that damage.')
    ->register();

// ---- Aenean Tailwind Boost ----

gaScenario('aenean-tailwind-boost-level-6-wakes-the-ally-and-gives-its-next-attack-plus-four')
    ->standardDeck('Aenean Tailwind Boost')
    ->readyFor('Aenean Tailwind Boost', classBonus: true, level: 6)
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myHand', 'Aenean Tailwind Boost')
    ->castSpell('Aenean Tailwind Boost', pay: 3, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The rested ally woke up')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","vNrkfKLlt6"]', 'Class Bonus at Level 6+: the next attack this turn gets +4 POWER')
    ->tested('Aenean Tailwind Boost')
    ->covers(['status', 'stat-change', 'class-bonus', 'level-gate'], 'Wake up target ally.', '[Class Bonus] [Level 6+] That ally\'s next attack this turn gets +4[POWER]. *(Apply this effect only if your champion’s class matches this card’s class and only if your champion is level 6 or higher.)*')
    ->register();

gaScenario('aenean-tailwind-boost-below-level-6-only-wakes-the-ally')
    ->standardDeck('Aenean Tailwind Boost')
    ->readyFor('Aenean Tailwind Boost', classBonus: true, level: 5)
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myHand', 'Aenean Tailwind Boost')
    ->castSpell('Aenean Tailwind Boost', pay: 3, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The ally still woke up')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Level 5: no +4 POWER effect')
    ->tested('Aenean Tailwind Boost')
    ->covers(['level-gate', 'class-bonus'], '[Class Bonus] [Level 6+] That ally\'s next attack this turn gets +4[POWER]. *(Apply this effect only if your champion’s class matches this card’s class and only if your champion is level 6 or higher.)*')
    ->register();

// ---- Expeditious Opening ----

gaScenario('expeditious-opening-draws-into-memory-and-waits-for-an-ally')
    ->standardDeck('Expeditious Opening')
    ->readyFor('Expeditious Opening')
    ->seed('myHand', 'Expeditious Opening')
    ->castSpell('Expeditious Opening', pay: 2, window: 'me')
    ->expectGlobalEffect(1, 'w1wgpeifd0', 'The fast-activation effect waits for the next ally card', 1)
    ->expectZoneCount('myMemory', 3, 1, 'Two paid cards plus the card drawn into memory')
    ->tested('Expeditious Opening')
    ->covers(['draw-into-memory', 'fast-activation'], 'The next ally card you activate this turn can be activated as though it had **fast activation**.', 'Draw a card into your memory.')
    ->register();

// ---- Fairy Whispers ----

gaScenario('fairy-whispers-reveals-a-wind-card-and-puts-it-into-hand')
    ->standardDeck('Fairy Whispers')
    ->readyFor('Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->deckTop(['Aenean Reclaim', 'Dungeon Guide', 'Dungeon Guide'])
    ->castSpell('Fairy Whispers', pay: 1, window: 'me')
    ->answer(1, 'Top=DCzUre54F9,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectCardExists('myHand', 'Aenean Reclaim', 'The revealed top card is wind element: it went into hand')
    ->tested('Fairy Whispers')
    ->covers(['glimpse', 'look', 'condition'], '**Glimpse 3**. *(To **glimpse**, look at that many cards from the top of your deck. Put those cards back on the top or on the bottom of your deck in any order.)*', 'Reveal the top card of your deck. If that card is wind element, put it into your hand.')
    ->register();

gaScenario('fairy-whispers-leaves-a-non-wind-top-card-on-the-deck')
    ->standardDeck('Fairy Whispers')
    ->readyFor('Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->deckTop(['Dungeon Guide', 'Aenean Reclaim', 'Dungeon Guide'])
    ->castSpell('Fairy Whispers', pay: 1, window: 'me')
    ->answer(1, 'Top=em6eEh9q8y,DCzUre54F9,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 6, 1, 'Nothing was added to the hand (8 - 1 cast - 1 paid)')
    ->tested('Fairy Whispers')
    ->covers(['condition'], 'Reveal the top card of your deck. If that card is wind element, put it into your hand.')
    ->register();

// ---- Favorable Omens ----

gaScenario('favorable-omens-gives-allies-plus-one-life-per-wind-omen')
    ->standardDeck('Favorable Omens')
    ->readyFor('Favorable Omens')
    ->seed('myBanish', 'Aenean Reclaim', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Aenean Reclaim', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Favorable Omens')
    ->castSpell('Favorable Omens', pay: 1, window: 'me')
    ->expectLife('p1Field-1', 8, 'Two wind omens: a 1/6 ally gets +2 LIFE')
    ->tested('Favorable Omens')
    ->covers(['omen', 'stat-change'], 'For each wind element **omen** you have, allies you control get +1[LIFE] until end of turn.')
    ->register();

gaScenario('favorable-omens-ignores-an-omen-that-is-not-wind')
    ->standardDeck('Favorable Omens')
    ->readyFor('Favorable Omens')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Favorable Omens')
    ->castSpell('Favorable Omens', pay: 1, window: 'me')
    ->expectLife('p1Field-1', 6, 'A non-wind omen does not count: the ally keeps its printed 6 LIFE')
    ->tested('Favorable Omens')
    ->covers(['omen', 'condition'], 'For each wind element **omen** you have, allies you control get +1[LIFE] until end of turn.')
    ->register();

// ---- Evanescent Winds ----

gaScenario('evanescent-winds-gives-phantasia-allies-plus-two-life')
    ->standardDeck('Evanescent Winds')
    ->readyFor('Evanescent Winds')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Evanescent Winds')
    ->castSpell('Evanescent Winds', pay: 2, window: 'me')
    ->expectLife('p1Field-1', 4, 'The Phantasia ally (2 LIFE) gets +2 LIFE')
    ->expectLife('p1Field-2', 6, 'A non-Phantasia ally is unchanged')
    ->tested('Evanescent Winds')
    ->covers(['stat-change', 'condition'], 'Phantasia allies you control get +2 [LIFE] until end of turn.')
    ->register();

// ---- Luster's Shroud (damage taken from a Nascent Blast cast on the shrouded champion) ----

gaScenario('lusters-shroud-prevents-three-damage-to-the-target-unit')
    ->standardDeck('Luster\'s Shroud')
    ->readyFor('Luster\'s Shroud')
    ->seed('myHand', 'Luster\'s Shroud')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Luster\'s Shroud', pay: 1, window: 'me', target: 'myField-0')
    ->castSpell('Nascent Blast', pay: 3, window: 'both', target: 'myField-0')
    ->expectDamage('p1Field-0', 0, '3 damage would be dealt: 3 of it is prevented')
    ->tested('Luster\'s Shroud')
    ->covers(['prevent-damage', 'targeting'], 'The next time target unit would be dealt damage this turn, prevent 3 of that damage.')
    ->register();

gaScenario('lusters-shroud-without-the-spell-the-same-blast-deals-three')
    ->standardDeck('Nascent Blast')
    ->readyFor('Luster\'s Shroud')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Nascent Blast', pay: 3, window: 'both', target: 'myField-0')
    ->expectDamage('p1Field-0', 3, 'Control: with no Luster\'s Shroud the Nascent Blast deals its full 3 damage')
    ->tested('Nascent Blast')
    ->covers(['damage'], 'Deal 3 damage to target unit.')
    ->register();

// ---- Channel the Wind ----

gaScenario('channel-the-wind-puts-an-enlighten-counter-on-the-champion')
    ->standardDeck('Channel the Wind')
    ->readyFor('Channel the Wind')
    ->seed('myHand', 'Channel the Wind')
    ->castSpell('Channel the Wind', pay: 2, window: 'me')
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}', 'One enlighten counter on the champion')
    ->tested('Channel the Wind')
    ->covers(['counter'], 'Put an **enlighten** counter on your champion. *(You may remove three **enlighten** counters from your champion to draw a card.)*')
    ->register();

// ---- Galed Erasure ----

gaScenario('galed-erasure-destroys-an-ephemeral-ally')
    ->standardDeck('Galed Erasure')
    ->readyFor('Galed Erasure')
    ->seed('myField', 'Giant Tortoise', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myHand', 'Galed Erasure')
    ->castSpell('Galed Erasure', pay: 3, window: 'me', target: 'myField-1')
    ->expectZoneCount('myField', 1, 1, 'The ephemeral ally was destroyed: only the champion remains')
    ->tested('Galed Erasure')
    ->covers(['destroy', 'targeting', 'ephemeral'], 'Destroy target **ephemeral** object.')
    ->register();

gaScenario('galed-erasure-cannot-target-an-object-that-is-not-ephemeral')
    ->standardDeck('Galed Erasure')
    ->readyFor('Galed Erasure')
    ->seed('myField', 'Giant Tortoise', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Galed Erasure')
    ->play('Galed Erasure')->pay(3)->pass(1)
    ->rejects(1, 'myField-2', 'A Giant Tortoise that is not ephemeral is not a legal target')
    ->tested('Galed Erasure')
    ->covers(['targeting', 'ephemeral', 'restriction'], 'Destroy target **ephemeral** object.')
    ->register();

// ---- Infusion of Crescent Jade ----

gaScenario('infusion-of-crescent-jade-gives-a-polearm-plus-one-power-per-wind-non-champion-object')
    ->standardDeck('Infusion of Crescent Jade')
    ->readyFor('Infusion of Crescent Jade')
    ->seed('myField', 'Sundering Moon')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk')
    ->seed('myHand', 'Infusion of Crescent Jade')
    ->castSpell('Infusion of Crescent Jade', pay: 2, window: 'me', target: 'myField-1')
    ->expectPower('p1Field-1', 3, 'Sundering Moon (1 POWER) + 2 wind non-champion objects (itself and the Maiden)')
    ->tested('Infusion of Crescent Jade')
    ->covers(['stat-change', 'targeting'], 'Target Polearm weapon gets +1 [POWER] until end of turn for each wind element non-champion object you control.')
    ->register();
