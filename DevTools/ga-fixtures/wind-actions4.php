<?php
/**
 * Phase 3b (Wind): actions, fourth file -- modal, token, multi-target and suppress actions.
 */

// ---- Soothing Disillusion ----

gaScenario('soothing-disillusion-can-destroy-an-opposing-phantasia')
    ->standardDeck('Soothing Disillusion')
    ->readyFor('Soothing Disillusion')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk', player: 2)
    ->seed('myHand', 'Soothing Disillusion')
    ->play('Soothing Disillusion')->pay(2)->pass(1)->answer(1, 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The opposing phantasia was destroyed: only their champion remains (with one legal mode the engine skips the mode prompt)')
    ->tested('Soothing Disillusion')
    ->covers(['modal-choice', 'destroy', 'targeting'], 'Choose one—', '• Destroy target phantasia.')
    ->register();

gaScenario('soothing-disillusion-puts-a-buff-counter-on-an-animal-or-beast')
    ->standardDeck('Soothing Disillusion')
    ->readyFor('Soothing Disillusion')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk')
    ->seed('myHand', 'Soothing Disillusion')
    ->play('Soothing Disillusion')->pay(2)->pass(1)->answer(1, '1')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Beast got a buff counter')
    ->expectCardExists('myField', 'Maiden of Glimmer\'s Dusk', 'The other mode did not happen: the phantasia is still there')
    ->tested('Soothing Disillusion')
    ->covers(['modal-choice', 'counter', 'targeting'], '• Put a **buff** counter on target Animal or Beast ally.')
    ->register();

// ---- Summon Retinue ----

gaScenario('summon-retinue-summons-two-vacuous-servants-rested')
    ->standardDeck('Summon Retinue')
    ->readyFor('Summon Retinue')
    ->seed('myHand', 'Summon Retinue')
    ->castSpell('Summon Retinue', pay: 4, window: 'me')
    ->expectCardExists('myField', 'Vacuous Servant', 'A Vacuous Servant token was summoned')
    ->expectZoneCount('myField', 3, 1, 'The champion and two tokens')
    ->expectProperty('p1Field-1', 'Status', '1', 'The first token is rested')
    ->expectProperty('p1Field-2', 'Status', '1', 'The second token is rested')
    ->tested('Summon Retinue')
    ->covers(['token', 'summon', 'condition'], '**Summon** two Vacuous Servant tokens rested. Then if you have three or more **omens**, wake them up.')
    ->register();

gaScenario('summon-retinue-with-three-omens-wakes-the-tokens')
    ->standardDeck('Summon Retinue')
    ->readyFor('Summon Retinue')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Summon Retinue')
    ->castSpell('Summon Retinue', pay: 4, window: 'me')
    ->expectProperty('p1Field-1', 'Status', '2', 'Three omens: the first token woke up')
    ->expectProperty('p1Field-2', 'Status', '2', 'Three omens: the second token woke up')
    ->tested('Summon Retinue')
    ->covers(['omen', 'status', 'condition'], 'Then if you have three or more **omens**, wake them up.')
    ->register();

gaScenario('summon-retinue-with-two-omens-leaves-the-tokens-rested')
    ->standardDeck('Summon Retinue')
    ->readyFor('Summon Retinue')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Summon Retinue')
    ->castSpell('Summon Retinue', pay: 4, window: 'me')
    ->expectProperty('p1Field-1', 'Status', '1', 'Two omens are not enough: the token stays rested')
    ->tested('Summon Retinue')
    ->covers(['omen', 'condition'], 'Then if you have three or more **omens**, wake them up.')
    ->register();

// ---- Reinforcing Air ----

gaScenario('reinforcing-air-class-bonus-costs-one-less-and-buffs-two-human-allies')
    ->standardDeck('Reinforcing Air')
    ->readyFor('Reinforcing Air', classBonus: true)
    ->seed('myField', 'Lone Gunslinger')
    ->seed('myField', 'Lone Gunslinger')
    ->seed('myHand', 'Reinforcing Air')
    ->castSpell('Reinforcing Air', pay: 2, target: 'myField-1&myField-2')
    ->expectPower('p1Field-1', 3, 'First Human ally: 1 + 2 POWER')
    ->expectLife('p1Field-1', 3, 'First Human ally: 1 + 2 LIFE')
    ->expectPower('p1Field-2', 3, 'Second Human ally: 1 + 2 POWER')
    ->expectLife('p1Field-2', 3, 'Second Human ally: 1 + 2 LIFE')
    ->expectZoneCount('myMemory', 2, 1, 'Class Bonus: cost 3 - 1 = 2 was paid')
    ->tested('Reinforcing Air')
    ->covers(['stat-change', 'class-bonus', 'cost-reduction', 'targeting'], '[Class Bonus] This card costs 1 less to activate. *(Apply this effect only if your champion\'s class matches this card\'s class.)*', 'Up to two target Human allies get +2[POWER] and +2[LIFE] until end of turn.')
    ->register();

gaScenario('reinforcing-air-without-the-class-bonus-costs-the-full-three')
    ->standardDeck('Reinforcing Air')
    ->readyFor('Reinforcing Air')
    ->seed('myField', 'Lone Gunslinger')
    ->seed('myHand', 'Reinforcing Air')
    ->castSpell('Reinforcing Air', pay: 3, target: 'myField-1')
    ->expectPower('p1Field-1', 3, 'The buff still applies')
    ->expectZoneCount('myMemory', 3, 1, 'No Class Bonus: the printed cost 3 was paid')
    ->tested('Reinforcing Air')
    ->covers(['class-bonus'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

// ---- Scattering Gusts ----

gaScenario('scattering-gusts-suppresses-two-allies-and-class-bonus-enlightens')
    ->standardDeck('Scattering Gusts')
    ->readyFor('Scattering Gusts', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Scattering Gusts')
    ->castSpell('Scattering Gusts', pay: 4, window: 'me', target: 'myField-1')->answer(1, 'theirField-1')
    ->expectZoneCount('myBanish', 1, 1, 'Our ally was suppressed')
    ->expectZoneCount('theirBanish', 1, 1, 'Their ally was suppressed')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"CLERIC,MAGE"},"enlighten":1}', 'Class Bonus: an enlighten counter on the champion')
    ->tested('Scattering Gusts')
    ->covers(['suppress', 'class-bonus', 'counter', 'targeting'], '**Suppress** up to two target allies. *(To **suppress** an ally, banish it and return it to the field under its owner\'s control at the beginning of the next end phase.)*', '[Class Bonus] Put an **enlighten** counter on your champion.')
    ->register();

gaScenario('scattering-gusts-without-the-class-bonus-adds-no-enlighten-counter')
    ->standardDeck('Scattering Gusts')
    ->readyFor('Scattering Gusts')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Scattering Gusts')
    ->castSpell('Scattering Gusts', pay: 4, window: 'me', target: 'myField-1')->answer(1, 'PASS')
    ->expectZoneCount('myBanish', 1, 1, 'The chosen ally was suppressed; the second target was declined')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No Class Bonus: no enlighten counter')
    ->tested('Scattering Gusts')
    ->covers(['class-bonus', 'optional-choice-decline'], '[Class Bonus] Put an **enlighten** counter on your champion.')
    ->register();

// ---- Squallsnare ----

gaScenario('squallsnare-suppresses-two-allies-with-the-same-reserve-cost')
    ->standardDeck('Squallsnare')
    ->readyFor('Squallsnare')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Squallsnare')
    ->play('Squallsnare')->pay(3)->pass(1)->answer(1, 'myField-1')->answer(1, 'theirField-1')
    ->expectZoneCount('myBanish', 1, 1, 'Our ally was suppressed')
    ->expectZoneCount('theirBanish', 1, 1, 'The opposing ally with the same reserve cost 4 was suppressed')
    ->tested('Squallsnare')
    ->covers(['suppress', 'targeting', 'condition'], '**Suppress** two target allies with the same reserve costs. *(To **suppress** an object, banish it and return it to the field under its owner’s control at the beginning of the next end phase.)*')
    ->register();

// ---- Rearing Rebound ----

gaScenario('rearing-rebound-equestrian-draws-into-memory-with-a-horse')
    ->standardDeck('Rearing Rebound')
    ->readyFor('Rearing Rebound')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: 'me', target: 'myField-1')
    ->expectZoneCount('myBanish', 1, 1, 'The Horse was suppressed')
    ->tested('Rearing Rebound')
    ->covers(['suppress', 'targeting'], '**Suppress** target ally. *(To **suppress** an ally, banish it and return it to the field under its owner\'s control at the beginning of the next end phase.)*')
    ->register();

// ---- Potion Infusion: Growth ----

gaScenario('potion-infusion-growth-rests-the-potion-and-puts-lv-age-counters')
    ->standardDeck('Potion Infusion: Growth')
    ->readyFor('Potion Infusion: Growth', level: 2)
    ->seed('myField', 'Distilled Water')
    ->seed('myHand', 'Potion Infusion: Growth')
    ->castSpell('Potion Infusion: Growth', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Potion was rested')
    ->expectProperty('p1Field-1', 'Counters', '{"age":2}', 'LV 2: two age counters')
    ->tested('Potion Infusion: Growth')
    ->covers(['counter', 'status', 'scaling'], 'Rest target Potion. If you do, put **LV age** counters on it. *(**LV** refers to your champion’s level.)*')
    ->register();

gaScenario('potion-infusion-growth-a-potion-that-is-already-rested-gets-no-counters')
    ->standardDeck('Potion Infusion: Growth')
    ->readyFor('Potion Infusion: Growth', level: 2)
    ->seed('myField', 'Distilled Water', properties: ['Status' => 1])
    ->seed('myHand', 'Potion Infusion: Growth')
    ->castSpell('Potion Infusion: Growth', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Resting failed ("If you do"): no age counters')
    ->tested('Potion Infusion: Growth')
    ->covers(['condition'], 'Rest target Potion. If you do, put **LV age** counters on it.')
    ->register();

// ---- Spellshield: Wind ----

gaScenario('spellshield-wind-class-bonus-prevents-the-next-damage-and-buffs-an-ally')
    ->standardDeck('Spellshield: Wind')
    ->readyFor('Spellshield: Wind', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Spellshield: Wind')
    ->seed('myHand', 'Nascent Blast')
    ->castSpell('Spellshield: Wind', pay: 3, window: 'me')
    ->castSpell('Nascent Blast', pay: 3, window: '', target: 'myField-0')->answer(1, 'myField-1')
    ->expectDamage('p1Field-0', 0, 'The 3 damage to the champion was prevented')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '3 or more damage was prevented: a buff counter on an ally')
    ->expectZoneCount('myMemory', 6, 1, 'Class Bonus: cost 4 - 1 = 3, plus 3 for the Nascent Blast')
    ->tested('Spellshield: Wind')
    ->covers(['prevent-damage', 'class-bonus', 'cost-reduction', 'counter'], '[Class Bonus] This card costs 1 less to activate.', 'The next time damage would be dealt to your champion this turn, prevent that damage. If 3 or more damage was prevented this way, put a **buff** counter on an ally you control.')
    ->register();
