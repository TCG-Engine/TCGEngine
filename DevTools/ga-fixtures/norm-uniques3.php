<?php
/**
 * Phase 3d (Norm): unique allies, third batch. 'quiet' deck.
 */

// ---- Sun Jian, Wolvesbane ----

gaScenario('sun-jian-attacking-a-beast-gets-plus-two-power-and-cannot-be-retaliated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sun Jian, Wolvesbane')
    ->seed('myField', 'Sun Jian, Wolvesbane')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('myGraveyard', 'Gray Wolf', 'A Beast: 2 + 2 = 4 damage destroyed the Wolf', 2)
    ->expectDamage('p1Field-1', 0, 'The attack could not be retaliated')
    ->tested('Sun Jian, Wolvesbane')
    ->covers(['condition', 'stat-change', 'restriction', 'combat'], 'As long as Sun Jian is attacking a Beast unit, Sun Jian gets +2 [POWER] and her attacks can\'t be retaliated.')
    ->register();

// ---- Zhang Jiao, Way of Peace ----

gaScenario('zhang-jiao-enter-with-one-to-three-cards-in-memory-recovers-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zhang Jiao, Way of Peace', damage: 6)
    ->seed('myHand', 'Zhang Jiao, Way of Peace')
    ->play('Zhang Jiao, Way of Peace')->pay(3)
    ->expectDamage('p1Field-0', 2, 'The three paid cards are in memory (1 to 3): Recover 4, 6 - 4')
    ->tested('Zhang Jiao, Way of Peace')
    ->covers(['on-enter', 'condition', 'draw'], '**On Enter**: Depending on the amount of cards in your memory—')
    ->register();

// ---- Sun Quan, Sealbearer ----

gaScenario('sun-quan-level-one-enter-puts-a-buff-counter-on-another-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sun Quan, Sealbearer', level: 1)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Sun Quan, Sealbearer')
    ->play('Sun Quan, Sealbearer')->pay(4)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Level 1+] On Enter: the other ally got a buff counter')
    ->tested('Sun Quan, Sealbearer')
    ->covers(['level-gate', 'on-enter', 'counter', 'targeting'], '[Level 1+] **On Enter:** Put a **buff** counter on another target ally you control.')
    ->register();

gaScenario('sun-quan-level-two-buffed-allies-lose-pride')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sun Quan, Sealbearer', level: 2)
    ->seed('myField', 'Sun Quan, Sealbearer')
    ->seed('myField', 'Royal Bear', properties: ['Counters' => ['buff' => 1]])
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-0')->pass(2)
    ->expectProperty('p1Field-2', 'Status', '1', '[Level 2+] The buffed Bear lost pride 2... and attacked at level 2 anyway')
    ->tested('Sun Quan, Sealbearer')
    ->covers(['level-gate', 'static', 'pride'], '[Level 2+] Allies you control with a **buff** counter on them lose **pride**.')
    ->register();

// ---- Virgil, Altered Future ----

gaScenario('virgil-level-one-gets-plus-one-power-and-life-for-each-of-up-to-two-powercells')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Virgil, Altered Future', level: 1)
    ->seed('myField', 'Virgil, Altered Future')
    ->seed('myField', 'Powercell')->seed('myField', 'Powercell')->seed('myField', 'Powercell')
    ->observe()
    ->expectPower('p1Field-1', 4, '[Level 1+] Three Powercells counted up to two: 2 + 2')
    ->expectLife('p1Field-1', 4, '[Level 1+] 2 + 2 life')
    ->tested('Virgil, Altered Future')
    ->covers(['level-gate', 'stat-change', 'condition'], '[Level 1+]CARDNAME gets +1[POWER] and +1[LIFE] for each of up to two Powercell items you control.')
    ->register();

// ---- Zhou Yu, Enlightened Sage ----

gaScenario('zhou-yu-class-bonus-enter-materializes-a-book-from-the-material-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zhou Yu, Enlightened Sage', classBonus: true)
    ->seed('myMaterial', 'Worn Diary')
    ->seed('myHand', 'Zhou Yu, Enlightened Sage')
    ->play('Zhou Yu, Enlightened Sage')->pay(3)->answer(1, 'myMaterial-4')
    ->expectCardExists('myField', 'Worn Diary', '[Class Bonus] On Enter: the Book was materialized')
    ->tested('Zhou Yu, Enlightened Sage')
    ->covers(['class-bonus', 'on-enter', 'materialize'], '[Class Bonus] **On Enter**: Materialize a Book or Scripture card from your material deck.')
    ->register();

// ---- Hua Xiong, Insurgent's Fang ----

gaScenario('hua-xiong-the-first-card-an-opponent-activates-each-turn-costs-one-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hua Xiong, Insurgent\'s Fang')
    ->seed('myField', 'Hua Xiong, Insurgent\'s Fang')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->play('Dungeon Guide', 2)->pay(3, 2)
    ->expectZoneCount('myField', 1, 2, 'Paying the printed 3 is one short: the first card an opponent activates costs 1 more')
    ->tested('Hua Xiong, Insurgent\'s Fang')
    ->covers(['static', 'cost-increase'], 'The first card your opponents activate each turn costs 1 more to activate.')
    ->register();

// ---- Galahad, Court Knight ----

gaScenario('galahad-class-bonus-can-attack-using-a-sword-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Galahad, Court Knight', classBonus: true)
    ->seed('myField', 'Galahad, Court Knight')
    ->seed('myField', 'Training Sword')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')->answer(1, 'myField-2')->answer(1, 'theirField-0')->pass(2)
    ->expectProperty('p1Field-1', 'Status', '1', '[Class Bonus] Galahad attacked using the Sword and rested')
    ->expectDamage('p2Field-0', 2, 'The attack used the Sword\'s 1 power plus Galahad\'s 1')
    ->tested('Galahad, Court Knight')
    ->covers(['class-bonus', 'weapon', 'combat'], '[Class Bonus] CARDNAME can attack using Sword weapons you control.')
    ->register();

// ---- Lucca, Gateway Manager ----

gaVigorCase('lucca-class-bonus-vigor-wakes-her-at-the-end-of-the-turn', 'Lucca, Gateway Manager', 'Dungeon Guide', ['classBonus' => true],
    ['class-bonus', 'vigor'], '[Class Bonus] **Vigor**', '2', '[Class Bonus] Vigor woke Lucca at the end phase');

gaScenario('lucca-rest-cascades')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lucca, Gateway Manager')
    ->seed('myField', 'Lucca, Gateway Manager')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '1', 'Lucca rested')
    ->tested('Lucca, Gateway Manager')
    ->covers(['activated-ability', 'rest', 'cascade'], '**[REST]: ****Cascade**— ')
    ->register();

// ---- Sinon, Babelia's Companion ----

gaScenario('sinon-rest-and-sacrificing-a-powercell-cascades')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sinon, Babelia\'s Companion')
    ->seed('myField', 'Sinon, Babelia\'s Companion')
    ->seed('myField', 'Powercell')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-2')
    ->expectZoneCount('myField', 2, 1, 'The Powercell was sacrificed')
    ->expectProperty('p1Field-1', 'Status', '1', 'Sinon rested')
    ->tested('Sinon, Babelia\'s Companion')
    ->covers(['activated-ability', 'rest', 'sacrifice', 'cascade'], '**[REST], Sacrifice a Powercell:** **Cascade**— ')
    ->register();

// ---- Freydis, Master Tactician ----

gaScenario('freydis-class-bonus-recollection-puts-a-tactic-counter-on-her-and-glimpses-that-many')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Freydis, Master Tactician', classBonus: true)
    ->seed('myField', 'Freydis, Master Tactician')
    ->reachTurnThree()->answer(1, 'Top=px60u5n1do;Bottom=')
    ->expectProperty('p1Field-1', 'Counters', '{"tactic":1}', '[Class Bonus] A tactic counter at the recollection phase')
    ->tested('Freydis, Master Tactician')
    ->covers(['class-bonus', 'trigger', 'counter', 'glimpse'], '[Class Bonus] At the beginning of your recollection phase, put a **tactic** counter on CARDNAME, then **glimpse X** where **X** is the amount of **tactic** counters on CARDNAME.')
    ->register();

// ---- Mad Hatter, Morose Heritor ----

gaScenario('mad-hatter-ranged-three-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mad Hatter, Morose Heritor', player: 2)
    ->seed('myField', 'Mad Hatter, Morose Heritor', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->answer(2, 'NO')
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 4, 'Ranged 3 while distant: 1 + 3 = 4 damage')
    ->tested('Mad Hatter, Morose Heritor')
    ->covers(['keyword', 'ranged', 'combat'], '**Ranged 3** *(As long as this unit is **distant**, its attacks get +3[POWER].)*')
    ->register();
