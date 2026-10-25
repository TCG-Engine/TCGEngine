<?php
/**
 * Phase 3b (Wind): unique allies. Written on the 'quiet' deck.
 */

// ---- Cato, Meadow's Channeler ----

gaScenario('cato-class-bonus-on-enter-empowers-two-and-buffs-another-ally')
    ->deckTemplate('quiet', 'Cato, Meadow\'s Channeler')
    ->readyFor('Cato, Meadow\'s Channeler', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Cato, Meadow\'s Channeler')
    ->play('Cato, Meadow\'s Channeler')->pay(3)->answer(1, 'myField-1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","Nn4NzegwdX","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 on the champion')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Whenever you empower: a buff counter on another ally')
    ->tested('Cato, Meadow\'s Channeler')
    ->covers(['on-enter', 'class-bonus', 'empower', 'trigger', 'counter'], '[Class Bonus] **On Enter:** **Empower 2**.', '[Class Bonus] Whenever you **empower**, put a **buff** counter on another target ally. Trigger this ability only once per turn.')
    ->register();

gaScenario('cato-without-the-class-bonus-does-not-empower')
    ->deckTemplate('quiet', 'Cato, Meadow\'s Channeler')
    ->readyFor('Cato, Meadow\'s Channeler')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Cato, Meadow\'s Channeler')
    ->play('Cato, Meadow\'s Channeler')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no empower, no counter')
    ->tested('Cato, Meadow\'s Channeler')
    ->covers(['on-enter', 'class-bonus', 'empower', 'condition'], '[Class Bonus] **On Enter:** **Empower 2**.')
    ->register();

// ---- Zhang Fei, Spirited Steel ----

gaScenario('zhang-fei-class-bonus-vigor-wakes-him-at-the-end-of-the-turn')
    ->deckTemplate('quiet', 'Zhang Fei, Spirited Steel')
    ->readyFor('Zhang Fei, Spirited Steel', classBonus: true)
    ->seed('myField', 'Zhang Fei, Spirited Steel', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Class Bonus vigor: Zhang Fei woke at the end phase')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Not the only ally: no counter')
    ->tested('Zhang Fei, Spirited Steel')
    ->covers(['vigor', 'class-bonus'], '[Class Bonus] **Vigor**')
    ->register();

gaScenario('zhang-fei-class-bonus-the-only-ally-gets-a-buff-counter-at-the-end-phase')
    ->deckTemplate('quiet', 'Zhang Fei, Spirited Steel')
    ->readyFor('Zhang Fei, Spirited Steel', classBonus: true)
    ->seed('myField', 'Zhang Fei, Spirited Steel')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The only ally you control: a buff counter at the beginning of the end phase')
    ->tested('Zhang Fei, Spirited Steel')
    ->covers(['class-bonus', 'end-phase', 'counter', 'condition'], '[Class Bonus] At the beginning of your end phase, if CARDNAME is the only ally you control, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('zhang-fei-without-the-class-bonus-has-no-vigor-and-no-counter')
    ->deckTemplate('quiet', 'Zhang Fei, Spirited Steel')
    ->readyFor('Zhang Fei, Spirited Steel')
    ->seed('myField', 'Zhang Fei, Spirited Steel', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '1', 'No class bonus: still rested')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no counter')
    ->tested('Zhang Fei, Spirited Steel')
    ->covers(['vigor', 'class-bonus', 'condition'], '[Class Bonus] **Vigor**')
    ->register();

// ---- Ma Chao, Lupine Huntress ----

gaScenario('ma-chao-class-bonus-level-two-gets-plus-one-power-and-vigor')
    ->deckTemplate('quiet', 'Ma Chao, Lupine Huntress')
    ->readyFor('Ma Chao, Lupine Huntress', classBonus: true, level: 2)
    ->seed('myField', 'Ma Chao, Lupine Huntress', properties: ['Status' => 1])
    ->endTurn()
    ->expectPower('p1Field-1', 2, 'Class Bonus and level 2: 1 + 1 power')
    ->expectProperty('p1Field-1', 'Status', '2', 'Vigor: Ma Chao woke at the end phase')
    ->tested('Ma Chao, Lupine Huntress')
    ->covers(['class-bonus', 'level-gate', 'stat-change', 'vigor'], '[Class Bonus] [Level 2+] Ma Chao gets +1 [POWER] and has **vigor**.')
    ->register();

gaScenario('ma-chao-below-level-two-has-neither-the-power-nor-vigor')
    ->deckTemplate('quiet', 'Ma Chao, Lupine Huntress')
    ->readyFor('Ma Chao, Lupine Huntress', classBonus: true, level: 1)
    ->seed('myField', 'Ma Chao, Lupine Huntress', properties: ['Status' => 1])
    ->endTurn()
    ->expectPower('p1Field-1', 1, 'Level 1: printed 1 power')
    ->expectProperty('p1Field-1', 'Status', '1', 'Level 1: no vigor, still rested')
    ->tested('Ma Chao, Lupine Huntress')
    ->covers(['class-bonus', 'level-gate', 'condition'], '[Class Bonus] [Level 2+]')
    ->register();

// ---- Zhang Fei / Chrysalis / Rose fast activation, Perse ranged ----

gaWindRanged('perse-relentless-raptor-ranged-two-while-distant', 'Perse, Relentless Raptor', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');

gaScenario('perse-rests-to-suppress-an-ally-while-distant')
    ->deckTemplate('quiet', 'Perse, Relentless Raptor')
    ->readyFor('Perse, Relentless Raptor')
    ->seed('myField', 'Perse, Relentless Raptor', properties: ['TurnEffects' => ['DISTANT']])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->expectCardExists('theirBanish', 'Dungeon Guide', 'The ally was suppressed', 1)
    ->expectProperty('p1Field-1', 'Status', '1', 'Perse rested for the ability')
    ->tested('Perse, Relentless Raptor')
    ->covers(['activated-ability', 'rest', 'suppress', 'distant', 'targeting'], '[REST]**: Suppress** target ally, item, or weapon. Activate this ability only if CARDNAME is **distant**.')
    ->register();

gaScenario('perse-cannot-suppress-while-not-distant')
    ->deckTemplate('quiet', 'Perse, Relentless Raptor')
    ->readyFor('Perse, Relentless Raptor')
    ->seed('myField', 'Perse, Relentless Raptor')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '2', 'Not distant: the activation was refused and Perse did not rest')
    ->tested('Perse, Relentless Raptor')
    ->covers(['activated-ability', 'distant', 'condition'], 'Activate this ability only if CARDNAME is **distant**.')
    ->register();

// ---- Ma Chao, Lupine Huntress (On Enter) ----

gaScenario('ma-chao-on-enter-the-opponent-banishes-two-cards-from-their-graveyard')
    ->deckTemplate('quiet', 'Ma Chao, Lupine Huntress')
    ->readyFor('Ma Chao, Lupine Huntress')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Ma Chao, Lupine Huntress')
    ->play('Ma Chao, Lupine Huntress')->pay(3)->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-1')
    ->expectZoneCount('theirGraveyard', 1, 1, 'Two of three graveyard cards were banished')
    ->expectZoneCount('theirBanish', 2, 1, 'Two cards in the opponent\'s banishment')
    ->tested('Ma Chao, Lupine Huntress')
    ->covers(['on-enter', 'banish', 'graveyard'], '**On Enter**: Target opponent banishes two cards from their graveyard.')
    ->register();

gaScenario('ma-chao-on-enter-with-a-beast-ally-the-opponent-banishes-four-cards')
    ->deckTemplate('quiet', 'Ma Chao, Lupine Huntress')
    ->readyFor('Ma Chao, Lupine Huntress')
    ->seed('myField', 'Inferno Slime')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Ma Chao, Lupine Huntress')
    ->play('Ma Chao, Lupine Huntress')->pay(3)->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 1, 1, 'Four of five graveyard cards were banished')
    ->expectZoneCount('theirBanish', 4, 1, 'Four cards in the opponent\'s banishment')
    ->tested('Ma Chao, Lupine Huntress')
    ->covers(['on-enter', 'banish', 'graveyard', 'condition'], 'If you control an Animal or Beast ally, that player banishes four cards from their graveyard instead.')
    ->register();

// ---- Guan Yu, Prime Exemplar ----

gaScenario('guan-yu-costs-two-less-after-a-human-ally-died-this-turn')
    ->deckTemplate('quiet', 'Guan Yu, Prime Exemplar')
    ->readyFor('Guan Yu, Prime Exemplar')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Nascent Blast')
    ->seed('myHand', 'Guan Yu, Prime Exemplar')
    ->castSpell('Nascent Blast', pay: 3, window: '', target: 'myField-1')
    ->play('Guan Yu, Prime Exemplar')->pay(3)
    ->expectCardExists('myField', 'Guan Yu, Prime Exemplar', 'A Human ally died this turn: Guan Yu cost 5 - 2 = 3')
    ->tested('Guan Yu, Prime Exemplar')
    ->covers(['cost-reduction', 'condition'], 'As long as a Human ally you controlled has died this turn, this card costs 2 less to activate')
    ->register();

gaScenario('guan-yu-without-a-human-ally-death-costs-the-printed-five')
    ->deckTemplate('quiet', 'Guan Yu, Prime Exemplar')
    ->readyFor('Guan Yu, Prime Exemplar')
    ->seed('myHand', 'Guan Yu, Prime Exemplar')
    ->play('Guan Yu, Prime Exemplar')->pay(3)
    ->expectZoneCount('myField', 1, 1, 'Three payments are not enough: Guan Yu has not entered')
    ->tested('Guan Yu, Prime Exemplar')
    ->covers(['cost-reduction', 'condition'], 'As long as a Human ally you controlled has died this turn')
    ->register();

gaScenario('guan-yu-retaliates-with-retort-two')
    ->deckTemplate('quiet', 'Guan Yu, Prime Exemplar')
    ->readyFor('Guan Yu, Prime Exemplar')
    ->seed('myField', 'Guan Yu, Prime Exemplar')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 4, 'Retort 2: 2 + 2 = 4 damage, lethal for the Dungeon Guide')
    ->tested('Guan Yu, Prime Exemplar')
    ->covers(['retort', 'keyword'], '**Ambush**, **Retort 2**')
    ->register();

// ---- Andronika, Eternal Herald ----

gaScenario('andronika-imbued-gets-plus-one-power-and-life-and-vigor')
    ->deckTemplate('quiet', 'Andronika, Eternal Herald')
    ->readyFor('Andronika, Eternal Herald')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Andronika, Eternal Herald')
    ->play('Andronika, Eternal Herald')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectPower('p1Field-1', 2, 'Imbued: 1 + 1 power')
    ->expectLife('p1Field-1', 2, 'Imbued: 1 + 1 life')
    ->tested('Andronika, Eternal Herald')
    ->covers(['imbue', 'stat-change', 'vigor', 'condition'], '**Imbue 3**', 'As long as CARDNAME is **imbued**, CARDNAME gets +1 [POWER] and +1 [LIFE], and has **vigor**.')
    ->register();

gaScenario('andronika-not-imbued-has-its-printed-stats')
    ->deckTemplate('quiet', 'Andronika, Eternal Herald')
    ->readyFor('Andronika, Eternal Herald')
    ->seed('myHand', 'Andronika, Eternal Herald')
    ->play('Andronika, Eternal Herald')->answer(1, 'NO')->pay(3)
    ->expectPower('p1Field-1', 1, 'Not imbued: 1 power')
    ->expectLife('p1Field-1', 1, 'Not imbued: 1 life')
    ->tested('Andronika, Eternal Herald')
    ->covers(['imbue', 'stat-change', 'condition'], 'As long as CARDNAME is **imbued**')
    ->register();

gaScenario('andronika-class-bonus-on-enter-buffs-up-to-two-automaton-allies')
    ->deckTemplate('quiet', 'Andronika, Eternal Herald')
    ->readyFor('Andronika, Eternal Herald', classBonus: true)
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Andronika, Eternal Herald')
    ->play('Andronika, Eternal Herald')->answer(1, 'NO')->pay(3)->answer(1, 'myField-1')->answer(1, 'myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The first Drone got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The second Drone got a buff counter')
    ->tested('Andronika, Eternal Herald')
    ->covers(['on-enter', 'class-bonus', 'counter', 'targeting'], '[Class Bonus] **On Enter:** Put a **buff** counter on up to two Automaton allies you control.')
    ->register();

// ---- Rose, Eternal Paragon / Chrysalis, Hazy Caterpillar (Fast Activation) ----

gaScenario('rose-eternal-paragon-class-bonus-fast-activation-can-be-played-from-a-response-window')
    ->deckTemplate('quiet', 'Rose, Eternal Paragon')
    ->readyFor('Rose, Eternal Paragon', classBonus: true)
    ->seed('myHand', 'Rose, Eternal Paragon')
    ->endTurn()->answer(1, 'myHand-7')->pay(4)
    ->expectCardExists('myField', 'Rose, Eternal Paragon', 'Class Bonus Fast Activation: Rose was activated from a response window')
    ->tested('Rose, Eternal Paragon')
    ->covers(['keyword', 'fast-activation', 'class-bonus'], '[Class Bonus] **Fast Activation**')
    ->register();

gaScenario('chrysalis-hazy-caterpillar-fast-activation-can-be-played-from-a-response-window')
    ->deckTemplate('quiet', 'Chrysalis, Hazy Caterpillar')
    ->readyFor('Chrysalis, Hazy Caterpillar')
    ->seed('myHand', 'Chrysalis, Hazy Caterpillar')
    ->endTurn()->answer(1, 'myHand-7')->pay(2)
    ->expectCardExists('myField', 'Chrysalis, Hazy Caterpillar', 'Fast Activation: Chrysalis was activated from a response window')
    ->tested('Chrysalis, Hazy Caterpillar')
    ->covers(['keyword', 'fast-activation'], '**Fast Activation**')
    ->register();
