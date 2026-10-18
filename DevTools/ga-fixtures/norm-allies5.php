<?php
/**
 * Phase 3d (Norm): allies, fifth batch (cost reductions, vigor, foster and counters). 'quiet' deck unless noted.
 */

// ---- Adorned Stag ----

gaScenario('adorned-stag-class-bonus-costs-one-less')
    ->deckTemplate('quiet', 'Adorned Stag')
    ->readyFor('Adorned Stag', classBonus: true)
    ->seed('myHand', 'Adorned Stag')
    ->play('Adorned Stag')->pay(1)
    ->expectCardExists('myField', 'Adorned Stag', '[Class Bonus] Paying 1 instead of 2 played it')
    ->expectZoneCount('myMemory', 1, 1, 'Only one card went to memory')
    ->tested('Adorned Stag')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

gaScenario('adorned-stag-without-the-class-bonus-costs-the-printed-two')
    ->deckTemplate('quiet', 'Adorned Stag')
    ->readyFor('Adorned Stag')
    ->seed('myHand', 'Adorned Stag')
    ->play('Adorned Stag')->pay(1)
    ->expectZoneCount('myField', 1, 1, 'No class bonus: one card paid is not enough, the Stag is not played yet')
    ->tested('Adorned Stag')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

// ---- Acolyte of Cultivation ----

gaScenario('acolyte-of-cultivation-class-bonus-after-a-spell-costs-three-less')
    ->deckTemplate('quiet', 'Acolyte of Cultivation')
    ->readyFor('Acolyte of Cultivation', classBonus: true)
    ->seed('myHand', 'Purification')
    ->seed('myHand', 'Acolyte of Cultivation')
    ->castSpell('Purification', pay: 1, window: '')
    ->play('Acolyte of Cultivation')->pay(1)
    ->expectCardExists('myField', 'Acolyte of Cultivation', '[Class Bonus] A Spell was activated this turn: the Acolyte cost 4 - 3 = 1')
    ->expectZoneCount('myMemory', 2, 1, 'One for the Purification spell and one for the Acolyte')
    ->tested('Acolyte of Cultivation')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] As long as you\'ve activated a Spell card this turn, this card costs 3 less to activate.')
    ->register();

gaScenario('acolyte-of-cultivation-class-bonus-without-a-spell-costs-the-printed-four')
    ->deckTemplate('quiet', 'Acolyte of Cultivation')
    ->readyFor('Acolyte of Cultivation', classBonus: true)
    ->seed('myHand', 'Acolyte of Cultivation')
    ->play('Acolyte of Cultivation')->pay(1)
    ->expectZoneCount('myField', 1, 1, 'No Spell activated this turn: one card is not enough, the Acolyte is not played yet')
    ->tested('Acolyte of Cultivation')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] As long as you\'ve activated a Spell card this turn, this card costs 3 less to activate.')
    ->register();

// ---- Hulking Rearguard ----

gaScenario('hulking-rearguard-class-bonus-with-a-taunt-unit-costs-two-less')
    ->deckTemplate('quiet', 'Hulking Rearguard')
    ->readyFor('Hulking Rearguard', classBonus: true)
    ->seed('myField', 'Palace Guard')
    ->seed('myHand', 'Hulking Rearguard')
    ->play('Hulking Rearguard')->pay(3)
    ->expectCardExists('myField', 'Hulking Rearguard', '[Class Bonus] A unit with taunt is controlled: 5 - 2 = 3')
    ->tested('Hulking Rearguard')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] As long as you control a unit with **taunt**, this card costs 2 less to activate.')
    ->register();

gaScenario('hulking-rearguard-class-bonus-without-a-taunt-unit-costs-the-printed-five')
    ->deckTemplate('quiet', 'Hulking Rearguard')
    ->readyFor('Hulking Rearguard', classBonus: true)
    ->seed('myHand', 'Hulking Rearguard')
    ->play('Hulking Rearguard')->pay(3)
    ->expectZoneCount('myField', 1, 1, 'No taunt unit: three cards are not enough, the Rearguard is not played yet')
    ->tested('Hulking Rearguard')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] As long as you control a unit with **taunt**, this card costs 2 less to activate.')
    ->register();

// ---- Determined Spearman ----

gaScenario('determined-spearman-equestrian-with-a-horse-costs-one-less')
    ->deckTemplate('quiet', 'Determined Spearman')
    ->readyFor('Determined Spearman')
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Determined Spearman')
    ->play('Determined Spearman')->pay(2)
    ->expectCardExists('myField', 'Determined Spearman', 'Equestrian: a Horse ally is controlled, 3 - 1 = 2')
    ->tested('Determined Spearman')
    ->covers(['equestrian', 'cost-reduction', 'condition'], '***Equestrian*** — As long as you control a Horse ally, this card costs 1 less to activate.')
    ->register();

gaSingleStatMatrix('Determined Spearman', 'Determined Spearman', 'life', ['level-gate', 'stat-change'], '[Level 1+] CARDNAME gets +1 [LIFE].', [
    'determined-spearman-level-one-gets-plus-one-life' => [['level' => 1], 3, '[Level 1+] 2 + 1 life'],
    'determined-spearman-below-level-one-has-its-printed-life' => [['level' => 0], 2, 'Level 0: the printed 2 life'],
]);

// ---- Eight of Spades ----

gaScenario('eight-of-spades-level-one-costs-four-less')
    ->deckTemplate('quiet', 'Eight of Spades')
    ->readyFor('Eight of Spades', level: 1)
    ->seed('myHand', 'Eight of Spades')
    ->play('Eight of Spades')->pay(4)
    ->expectCardExists('myField', 'Eight of Spades', '[Level 1+] 8 - 4 = 4')
    ->tested('Eight of Spades')
    ->covers(['level-gate', 'cost-reduction'], '[Level 1+] This card costs 4 less to activate.')
    ->register();

// ---- Eager Page ----

gaScenario('eager-page-recollection-without-materializing-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Eager Page')
    ->readyFor('Eager Page')
    ->seed('myField', 'Eager Page')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Nothing was materialized this turn: a buff counter')
    ->tested('Eager Page')
    ->covers(['trigger', 'condition', 'counter'], 'At the beginning of your recollection phase, if you haven\'t materialized a card this turn, put a **buff** counter on CARDNAME.')
    ->register();

// ---- Promising Recruit ----

gaScenario('promising-recruit-level-two-end-phase-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Promising Recruit')
    ->readyFor('Promising Recruit', level: 2)
    ->seed('myField', 'Promising Recruit')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Level 2+] End phase: a buff counter')
    ->tested('Promising Recruit')
    ->covers(['level-gate', 'trigger', 'counter'], '[Level 2+] At the beginning of your end phase, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('promising-recruit-below-level-two-gets-no-counter')
    ->deckTemplate('quiet', 'Promising Recruit')
    ->readyFor('Promising Recruit', level: 1)
    ->seed('myField', 'Promising Recruit')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Level 1: no buff counter')
    ->tested('Promising Recruit')
    ->covers(['level-gate', 'trigger', 'condition'], '[Level 2+] At the beginning of your end phase, put a **buff** counter on CARDNAME.')
    ->register();

// ---- Vigor family ----

gaVigorCase('fervent-beastmaster-class-bonus-vigor-wakes-it-at-the-end-of-the-turn', 'Fervent Beastmaster', 'Fervent Beastmaster', ['classBonus' => true],
    ['class-bonus', 'vigor'], '[Class Bonus] **Vigor**', '2', '[Class Bonus] Vigor woke the Beastmaster');

gaScenario('fervent-beastmaster-with-a-beast-ally-gets-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Fervent Beastmaster')
    ->readyFor('Fervent Beastmaster')
    ->seed('myField', 'Fervent Beastmaster')
    ->seed('myField', 'Gray Wolf')
    ->observe()
    ->expectPower('p1Field-1', 4, 'A Beast ally is controlled: 3 + 1 power')
    ->expectLife('p1Field-1', 4, 'A Beast ally is controlled: 3 + 1 life')
    ->tested('Fervent Beastmaster')
    ->covers(['condition', 'stat-change'], 'As long as you control a Beast ally, CARDNAME gets +1 [POWER] and +1 [LIFE].')
    ->register();

gaVigorMatrix('Sturdy Droid', 'Sturdy Droid', ['class-bonus', 'vigor'], '[Class Bonus] **Vigor**', [
    'sturdy-droid-class-bonus-vigor-wakes-it-at-the-end-of-the-turn' => [['classBonus' => true], '2', '[Class Bonus] Vigor woke the Droid'],
    'sturdy-droid-without-the-class-bonus-stays-rested' => [[], '1', 'No class bonus: no vigor', ['class-bonus', 'vigor', 'condition'], '[Class Bonus] **Vigor**'],
]);

// ---- Lumbering Steed ----

gaHinderedCase('lumbering-steed-hindered-enters-rested', 'Lumbering Steed', 'Lumbering Steed', [],
    ['keyword', 'hindered'], '**Hindered** *(This ally enters the field rested.)*', 4, 'Hindered: the Steed entered rested');

// ---- Banner Slime / Baby Silver Slime ----

gaScenario('baby-silver-slime-with-another-slime-has-taunt')
    ->deckTemplate('quiet', 'Baby Silver Slime')
    ->readyFor('Baby Silver Slime')
    ->seed('myField', 'Baby Silver Slime', properties: ['Status' => 2])
    ->seed('myField', 'Simple Slime')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Another Slime ally is controlled: taunt protects the champion')
    ->tested('Baby Silver Slime')
    ->covers(['condition', 'taunt'], 'As long as you control another Slime ally, Baby Silver Slime has **taunt**.')
    ->register();

gaScenario('baby-silver-slime-alone-has-no-taunt')
    ->deckTemplate('quiet', 'Baby Silver Slime')
    ->readyFor('Baby Silver Slime')
    ->seed('myField', 'Baby Silver Slime', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 1, 'No other Slime: no taunt, the champion could be attacked')
    ->tested('Baby Silver Slime')
    ->covers(['condition', 'taunt'], 'As long as you control another Slime ally, Baby Silver Slime has **taunt**.')
    ->register();

gaScenario('banner-slime-class-bonus-rest-gives-other-slimes-plus-one-power')
    ->deckTemplate('quiet', 'Banner Slime')
    ->readyFor('Banner Slime', classBonus: true)
    ->seed('myField', 'Banner Slime')
    ->seed('myField', 'Simple Slime')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectPower('p1Field-2', 1, '[Class Bonus] [REST]: the other Slime got +1 power until end of turn')
    ->tested('Banner Slime')
    ->covers(['class-bonus', 'activated-ability', 'rest', 'stat-change'], '[Class Bonus] [REST]: Other Slime allies you control get +1[POWER] until end of turn.')
    ->register();
