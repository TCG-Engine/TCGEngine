<?php
/**
 * Phase 3e (Tera). readyFor() enables the tera element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

// ---- Flower tokens (phantasia tokens seeded onto the field; recollection triggers observed at turn three) ----

gaScenario('acerbica-token-champions-you-control-get-minus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Wandering Warrior')
    ->seed('myField', 'Acerbica')
    ->observe()
    ->expectLevel('p1Field-0', 0, 'Lorraine, Wandering Warrior level 1 minus 1')
    ->tested('Acerbica')
    ->covers(['static', 'level', 'token'], 'Champions you control get -1 level.')
    ->register();

gaScenario('baihua-token-recollection-makes-each-opponent-recover-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Damage' => 4], player: 2)
    ->seed('myField', 'Baihua')
    ->reachTurnThree()
    ->expectDamage('p2Field-0', 3, 'Each opponent recovers 1: 4 down to 3')
    ->tested('Baihua')
    ->covers(['recollection', 'recover', 'token'], 'At the beginning of your recollection phase, each opponent **recovers 1**.')
    ->register();

gaScenario('floodbloom-token-recollection-banishes-the-top-two-cards-of-your-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Floodbloom')
    ->reachTurnThree()
    ->expectZoneCount('myBanish', 2, 1, 'The top two cards of the deck were banished')
    ->tested('Floodbloom')
    ->covers(['recollection', 'banish', 'deck', 'token'], 'At the beginning of your recollection phase, banish the top two cards of your deck.')
    ->register();

gaScenario('lycoria-token-recollection-deals-one-unpreventable-damage-to-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Lycoria')
    ->reachTurnThree()
    ->expectDamage('p1Field-0', 1, '1 unpreventable damage to my own champion')
    ->tested('Lycoria')
    ->covers(['recollection', 'self-damage', 'token'], 'At the beginning of your recollection phase, deal 1 unpreventable damage to your champion.')
    ->register();

gaScenario('washuru-token-recollection-banishes-a-card-from-your-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Washuru')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->reachTurnThree()
    ->expectZoneCount('myGraveyard', 0, 1, 'The graveyard card was banished')
    ->expectZoneCount('myBanish', 1, 1, 'It sits in the banishment')
    ->tested('Washuru')
    ->covers(['recollection', 'banish', 'graveyard', 'token'], 'At the beginning of your recollection phase, banish a card from your graveyard.')
    ->register();

gaScenario('nightshade-token-recollection-puts-a-wither-counter-on-a-non-champion-non-token-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Nightshade')
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()->target('myField-2')
    ->expectProperty('p1Field-2', 'Counters', '{"wither":1}', 'A wither counter on the Dungeon Guide')
    ->tested('Nightshade')
    ->covers(['recollection', 'counter', 'wither', 'token'], 'At the beginning of your recollection phase, put a **wither** counter on target non-champion non-token object you control.')
    ->register();

// ---- Full Bloom ([Diao Chan Bonus]) ----

gaScenario('full-bloom-diao-chan-bonus-enter-makes-the-opponent-summon-four-flowerbuds-each-dealing-two-and-recovering-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage', damage: 10)
    ->seed('myHand', 'Full Bloom')
    ->play('Full Bloom')->pay(7)
    ->expectZoneCount('theirField', 5, 1, 'The opponent\'s champion and four Flowerbud tokens')
    ->expectDamage('p2Field-0', 8, 'Each Flowerbud they summoned dealt 2 damage to their champion')
    ->expectDamage('p1Field-0', 2, 'And I recovered 2 four times (10 down to 2)')
    ->tested('Full Bloom')
    ->covers(['diao-chan-bonus', 'on-enter', 'summon', 'token', 'trigger', 'damage', 'recover'], '[Diao Chan Bonus] **On Enter:** Target opponent **summons** four Flowerbud tokens.', '[Diao Chan Bonus] Whenever an opponent **summons** a Flowerbud token, deal 2 damage to each champion that opponent controls and you **recover 2**.')
    ->register();

gaScenario('flowerbud-token-sits-on-the-field-as-a-vanilla-phantasia')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Flowerbud')
    ->observe()
    ->expectZoneCount('myField', 2, 1, 'The champion and the Flowerbud token')
    ->tested('Flowerbud')
    ->covers(['token', 'vanilla'], 'Flowerbud token (no text)')
    ->register();

// ---- Maiden of Waning Bloom ([Diao Chan Bonus]) ----

gaScenario('maiden-of-waning-bloom-diao-chan-bonus-enter-makes-the-opponent-summon-two-flowerbuds')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myHand', 'Maiden of Waning Bloom')
    ->play('Maiden of Waning Bloom')->pay(2)->answer(1, 'YES')
    ->expectZoneCount('theirField', 3, 1, 'The opponent\'s champion and two Flowerbud tokens')
    ->tested('Maiden of Waning Bloom')
    ->covers(['diao-chan-bonus', 'on-enter', 'summon', 'token'], '[Diao Chan Bonus] **On Enter: **Up to one target opponent **summons** two Flowerbud tokens.')
    ->register();

// ---- Perishing Florets ----

gaScenario('perishing-florets-enter-destroys-a-non-champion-object-with-reserve-cost-three-or-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Perishing Florets', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Perishing Florets')
    ->play('Perishing Florets')->pay(2)->target('theirField-2')
    ->expectZoneCount('theirField', 2, 1, 'Dungeon Guide (reserve 3) was destroyed; the reserve-4 Giant Tortoise was not a legal target')
    ->expectCardExists('theirField', 'Giant Tortoise', 'The reserve-4 ally survives')
    ->tested('Perishing Florets')
    ->covers(['on-enter', 'destroy', 'cost-limit'], '**On Enter:** Destroy target non-champion object with reserve cost 3 or less or memory cost 0.')
    ->register();

// ---- Saprotrophy ----

gaScenario('saprotrophy-enter-recovers-two-plus-the-tera-cards-in-the-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Saprotrophy', classBonus: true, damage: 9)
    ->seed('myBanish', 'Perishing Florets')->seed('myBanish', 'Saprotrophy')
    ->seed('myHand', 'Saprotrophy')
    ->play('Saprotrophy')->pay(2)
    ->expectDamage('p1Field-0', 5, 'Recover 2 + 2 tera cards in the banishment: 9 down to 5')
    ->tested('Saprotrophy')
    ->covers(['on-enter', 'recover', 'banishment'], '**On Enter: ** **Recover 2+X**, where **X** is the amount of tera element cards in your banishment.')
    ->register();

// ---- Maiden of Primal Virtue ----

gaScenario('maiden-of-primal-virtue-gets-plus-one-power-and-life-for-each-phantasia-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maiden of Primal Virtue', classBonus: true)
    ->seed('myField', 'Maiden of Primal Virtue')
    ->seed('myField', 'Flowerbud')->seed('myField', 'Flowerbud')
    ->observe()
    ->expectPower('p1Field-1', 3, '0 power + 3 phantasias (itself and two Flowerbuds)')
    ->expectLife('p1Field-1', 3, '0 life + 3')
    ->tested('Maiden of Primal Virtue')
    ->covers(['static', 'stat-change', 'phantasia'], 'Maiden of Primal Virtue gets +1 [POWER] and +1 [LIFE] for each phantasia you control.')
    ->register();

// ---- Tera actions, allies and items ----

gaScenario('call-the-pack-class-bonus-puts-a-beast-ally-from-hand-onto-the-field-for-each-animal-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Call the Pack', classBonus: true)
    ->seed('myField', 'Woodland Squirrels')
    ->seed('myHand', 'Gray Wolf')
    ->seed('myHand', 'Call the Pack')
    ->play('Call the Pack')->pay(2)->answer(1, 'myHand-5')
    ->expectCardExists('myField', 'Gray Wolf', 'The Beast ally card came from hand onto the field')
    ->tested('Call the Pack')
    ->covers(['class-bonus', 'cost-reduction', 'zone-movement', 'for-each'], '[Class Bonus] This card costs 2 less to activate. For each Animal ally you control, you may put a Beast ally card from your hand onto the field.')
    ->register();

gaScenario('command-the-hunt-gives-allies-plus-two-power-and-vigor-for-two-less-if-no-unit-attacked')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Command the Hunt')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Command the Hunt')
    ->play('Command the Hunt')->pay(2)
    ->expectPower('p1Field-1', 3, '1 + 2')
    ->expectGlobalEffect(1, 'rxxwQT054x_VIGOR', 'Allies gain vigor')
    ->tested('Command the Hunt')
    ->covers(['cost-reduction', 'stat-change', 'vigor'], 'This card costs 2 less to activate if no units have attacked this turn. Allies you control get +2 [POWER] and gain **vigor** until end of turn.')
    ->register();

gaScenario('command-the-hunt-costs-full-price-after-a-unit-has-attacked')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Command the Hunt')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Command the Hunt')
    ->reachTurnThree()
    ->attack('myField-0', 'theirField-0')
    ->play('Command the Hunt')->pay(4)
    ->expectGlobalEffect(1, 'rxxwQT054x_VIGOR', 'Paid the full four after an attack')
    ->tested('Command the Hunt')
    ->covers(['cost-reduction', 'condition'], 'This card costs 2 less to activate if no units have attacked this turn.')
    ->register();

gaScenario('invigoration-class-bonus-puts-lv-buff-counters-on-a-rested-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Invigoration', classBonus: true, level: 2)
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Status' => 1])
    ->seed('myHand', 'Invigoration')
    ->play('Invigoration')->pay(2)->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Level 2: two buff counters')
    ->tested('Invigoration')
    ->covers(['class-bonus', 'cost-reduction', 'counter', 'targeting'], '[Class Bonus] This card costs 2 less to activate. Put **LV buff** counters on target rested ally.')
    ->register();

gaScenario('flourishing-restoration-recovers-three-and-three-more-for-each-of-two-opposing-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage', damage: 12)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Flourishing Restoration')
    ->play('Flourishing Restoration')->pay(2)
    ->expectDamage('p1Field-0', 3, 'Recover 3, then 3 for each of two allies: 12 down to 3')
    ->tested('Flourishing Restoration')
    ->covers(['recover', 'for-each', 'diao-chan-bonus'], '**Recover 3**. Then for each of up to two allies you don\'t control, **recover 3** again.')
    ->register();

gaScenario('sempiternal-sage-class-bonus-on-attack-recovers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sempiternal Sage', classBonus: true, damage: 8)
    ->seed('myField', 'Sempiternal Sage')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->expectDamage('p1Field-0', 5, 'On Attack: recover 3, 8 down to 5')
    ->tested('Sempiternal Sage')
    ->covers(['class-bonus', 'on-attack', 'recover'], '[Class Bonus] **On Attack:** You may **recover 3**. If you don\'t, **empower 3**.')
    ->register();

gaNormItem('horn-of-beastcalling-banish-makes-the-next-beast-ally-cost-three-less-and-class-bonus-draws', 'Horn of Beastcalling', 0, 0,
    fn($s) => $s->seed('myHand', 'Gray Wolf'),
    fn($s) => $s->play('Gray Wolf')->expectCardExists('myField', 'Gray Wolf', 'The Beast ally (reserve 2) entered with nothing paid: 3 less'),
    ['activated-ability', 'cost-reduction', 'banish-self'], '**Banish CARDNAME:** The next Beast ally card you activate this turn costs 3 less to activate.');

// ---- Shifting Currents cards (the mastery is seeded facing the wanted direction) ----

gaScenario('bagua-of-vital-demise-deals-four-damage-to-a-unit-and-goes-to-the-material-deck-preserved-facing-east')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bagua of Vital Demise')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Bagua of Vital Demise')
    ->castSpell('Bagua of Vital Demise', pay: 2)->target('theirField-1')
    ->expectDamage('p2Field-1', 4, 'Four damage to the unit')
    ->expectCardExists('myMaterial', 'Bagua of Vital Demise', 'Facing East it went to the material deck preserved')
    ->tested('Bagua of Vital Demise')
    ->covers(['damage', 'shifting-currents', 'preserve', 'targeting'], 'Deal 4 damage to target unit. If your Shifting Currents face East, put this card into its owner\'s material deck **preserved**.')
    ->register();

gaScenario('conduit-of-seasons-facing-west-prevents-two-damage-dealt-to-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'WEST'])
    ->seed('myField', 'Conduit of Seasons')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p1Field-1', 0, 'Dungeon Guide deals 1, Shifting Currents face West: prevent 2 of it, so all of it')
    ->tested('Conduit of Seasons')
    ->covers(['prevention', 'shifting-currents', 'condition'], 'If damage would be dealt to CARDNAME while your Shifting Currents face West, prevent 2 of that damage.')
    ->register();

gaScenario('conduit-of-seasons-facing-east-takes-the-full-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myField', 'Conduit of Seasons')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p1Field-1', 1, 'Facing East nothing is prevented: Dungeon Guide deals its 1')
    ->tested('Conduit of Seasons')
    ->covers(['prevention', 'shifting-currents', 'condition'], 'If damage would be dealt to CARDNAME while your Shifting Currents face West, prevent 2 of that damage.')
    ->register();

gaScenario('conduit-of-seasons-on-attack-facing-east-recovers-one-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage', damage: 5)
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'EAST'])
    ->seed('myField', 'Conduit of Seasons')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p1Field-0', 4, 'Recover 1: 5 down to 4')
    ->expectZoneCount('myMemory', 1, 1, 'A card was drawn into memory')
    ->tested('Conduit of Seasons')
    ->covers(['on-attack', 'recover', 'draw-into-memory', 'shifting-currents', 'condition'], '**On Attack:** If your Shifting Currents face East, **recover 1** and draw a card into your memory.')
    ->register();

// ---- Tera allies ----

gaScenario('arima-gaias-wings-recollection-puts-three-buff-counters-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arima, Gaia\'s Wings', level: 5)
    ->seed('myField', 'Arima, Gaia\'s Wings')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":3}', 'Three buff counters at the recollection phase')
    ->tested('Arima, Gaia\'s Wings')
    ->covers(['recollection', 'counter'], 'At the beginning of your recollection phase, put three **buff** counters on Arima.')
    ->register();

gaScenario('arima-gaias-wings-dying-puts-it-into-its-owners-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arima, Gaia\'s Wings', level: 5)
    ->seed('myField', 'Arima, Gaia\'s Wings')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectCardExists('myMemory', 'Arima, Gaia\'s Wings', 'On Death: Arima went into its owner\'s memory')
    ->tested('Arima, Gaia\'s Wings')
    ->covers(['on-death', 'zone-movement'], '**On Death:** Put Arima into its owner\'s memory.')
    ->register();

gaScenario('arima-gaias-wings-pride-five-will-not-attack-below-champion-level-five')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arima, Gaia\'s Wings')
    ->seed('myField', 'Arima, Gaia\'s Wings')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 0, 'Pride 5 at champion level 0: Arima did not obey, no damage dealt')
    ->tested('Arima, Gaia\'s Wings')
    ->covers(['pride', 'condition'], '**Pride 5** *(This ally won\'t obey you unless your champion is level 5 or higher.)*')
    ->register();

gaScenario('arima-gaias-wings-pride-five-obeys-at-champion-level-five')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arima, Gaia\'s Wings', level: 5)
    ->seed('myField', 'Arima, Gaia\'s Wings')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 6, 'Champion level 5: Arima obeys; 3 power plus the three buff counters from the recollection phase')
    ->tested('Arima, Gaia\'s Wings')
    ->covers(['pride', 'condition'], '**Pride 5** *(This ally won\'t obey you unless your champion is level 5 or higher.)*')
    ->register();

gaScenario('avatar-of-gaia-ally-link-gives-the-linked-ally-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Avatar of Gaia', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Avatar of Gaia')
    ->play('Avatar of Gaia')->answer(1, 'myField-1')->pay(1)
    ->expectCardExists('myField', 'Avatar of Gaia', 'The Avatar entered linked to the Dungeon Guide')
    ->expectProperty('p1Field-2', 'Counters', '{"linkedToAlly":"em6eEh9q8y"}', 'The Avatar is linked to the Dungeon Guide')
    ->tested('Avatar of Gaia')
    ->covers(['ally-link', 'taunt', 'preserve'], '**Ally Link** *(This object enters the field linked to target ally. If the link is broken, sacrifice this object.)* Linked ally has **taunt** and loses **pride**.')
    ->register();

gaScenario('avatar-of-gaia-the-linked-ally-has-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Avatar of Gaia')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 2, 'Subcards' => [GaScenario::id('Avatar of Gaia')]])
    ->seed('myField', 'Avatar of Gaia', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while the linked ally is awake')
    ->tested('Avatar of Gaia')
    ->covers(['ally-link', 'taunt'], 'Linked ally has **taunt** and loses **pride**.')
    ->register();

gaScenario('artificers-opus-class-bonus-efficiency-and-enters-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Artificer\'s Opus', classBonus: true, level: 3)
    ->seed('myHand', 'Artificer\'s Opus')
    ->play('Artificer\'s Opus')->pay(5)
    ->expectProperty('p1Field-1', 'Status', '1', 'Enters the field rested')
    ->tested('Artificer\'s Opus')
    ->covers(['class-bonus', 'efficiency', 'enters-rested'], '[Class Bonus] **Efficiency** CARDNAME enters the field rested.')
    ->register();

gaScenario('dong-zhou-false-liege-class-bonus-enter-rested-deals-three-damage-to-other-allies-and-gains-vigor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dong Zhou, False Liege', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Dong Zhou, False Liege')
    ->play('Dong Zhou, False Liege')->pay(3)->answer(1, 'YES')->answer(1, '2')
    ->expectDamage('p2Field-1', 3, 'Deal 3 damage to all other allies')
    ->expectProperty('p1Field-1', 'Status', '1', 'Dong Zhou rested')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","VIGOR"]', 'Dong Zhou gained vigor')
    ->tested('Dong Zhou, False Liege')
    ->covers(['class-bonus', 'on-enter', 'rest', 'modal', 'damage', 'vigor'], '[Class Bonus] **On Enter:** You may rest CARDNAME. If you do, choose two— • Deal 3 damage to all other allies. • **Empower 3**. • CARDNAME gains **vigor** until end of turn.')
    ->register();

gaScenario('dong-zhou-false-liege-class-bonus-enter-rested-empowers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dong Zhou, False Liege', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Dong Zhou, False Liege')
    ->play('Dong Zhou, False Liege')->pay(3)->answer(1, 'YES')->answer(1, '1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","lrbcgpny3d","EMPOWERED","EMPOWER_PLUS_3"]', 'Empowered by 3')
    ->expectDamage('p2Field-1', 0, 'Damage mode skipped')
    ->tested('Dong Zhou, False Liege')
    ->covers(['class-bonus', 'on-enter', 'rest', 'modal', 'empower'], '**Empower 3**.')
    ->register();

gaScenario('dong-zhou-false-liege-declining-the-rest-does-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dong Zhou, False Liege', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Dong Zhou, False Liege')
    ->play('Dong Zhou, False Liege')->pay(3)->answer(1, 'NO')
    ->expectDamage('p2Field-1', 0, 'No rest: no modes')
    ->expectProperty('p1Field-1', 'Status', '2', 'Dong Zhou stays awake')
    ->tested('Dong Zhou, False Liege')
    ->covers(['class-bonus', 'on-enter', 'optional'], 'You may rest CARDNAME. If you do, choose two')
    ->register();

// ---- Tera uniques, phantasias and regalia ----

gaScenario('lu-bu-indomitable-titan-first-attack-pays-two-to-wake-up')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lu Bu, Indomitable Titan')
    ->seed('myField', 'Lu Bu, Indomitable Titan')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->pay(2)
    ->expectProperty('p1Field-1', 'Status', '2', 'Paid two: Lu Bu woke up after attacking')
    ->tested('Lu Bu, Indomitable Titan')
    ->covers(['on-attack', 'wake-up', 'payment'], '**On Attack**: If this is CARDNAME\'s first attack this turn, you may pay (2). If you do, wake up CARDNAME.')
    ->register();

gaScenario('lu-bu-indomitable-titan-has-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lu Bu, Indomitable Titan')
    ->seed('myField', 'Lu Bu, Indomitable Titan', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while Lu Bu is awake')
    ->tested('Lu Bu, Indomitable Titan')
    ->covers(['keyword', 'taunt'], '**Taunt**, **Vigor**')
    ->register();

gaScenario('yudi-gossamer-jade-empowering-puts-a-root-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Yudi, Gossamer Jade')
    ->seed('myField', 'Yudi, Gossamer Jade')
    ->seed('myField', 'Gem of Sorority')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Counters', '{"root":1}', 'Empower 2 is greater than zero root counters: one root counter')
    ->tested('Yudi, Gossamer Jade')
    ->covers(['trigger', 'empower', 'counter'], 'Whenever you **empower** an amount, if that amount is greater than the amount of **root** counters on CARDNAME, put a **root** counter on it.')
    ->register();

gaScenario('yudi-gossamer-jade-class-bonus-non-tera-units-pay-per-root-counter-to-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Yudi, Gossamer Jade', classBonus: true)
    ->seed('myField', 'Yudi, Gossamer Jade', properties: ['Counters' => ['root' => 2]])
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-2!FSM!')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 1, 'Paid two (one per root counter) to declare the attack, which then landed')
    ->expectZoneCount('myHand', 6, 1, 'Two cards were paid (7 + the turn draw - 2)')
    ->tested('Yudi, Gossamer Jade')
    ->covers(['class-bonus', 'attack-tax', 'condition'], '[Class Bonus] Players can\'t declare attacks with non-tera element units unless they pay (X) for each attack declaration, where **X** is the amount of **root** counters on CARDNAME.')
    ->register();

gaScenario('bairui-resplendent-barrier-prevents-two-damage-to-the-champion-and-gains-a-charge-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bairui, Resplendent Barrier')
    ->seed('myField', 'Bairui, Resplendent Barrier')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 1, 'Three damage, two prevented')
    ->expectProperty('p1Field-1', 'Counters', '{"charge":1}', 'A charge counter')
    ->tested('Bairui, Resplendent Barrier')
    ->covers(['prevention', 'counter', 'damage'], 'If damage would be dealt to your champion while CARDNAME has two or less **charge** counters on it, prevent 2 of that damage and put a **charge** counter on CARDNAME instead.')
    ->register();

gaScenario('bairui-resplendent-barrier-with-three-charge-counters-prevents-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bairui, Resplendent Barrier')
    ->seed('myField', 'Bairui, Resplendent Barrier', properties: ['Counters' => ['charge' => 3]])
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 3, 'Charged: the full three damage')
    ->tested('Bairui, Resplendent Barrier')
    ->covers(['prevention', 'condition'], 'while CARDNAME has two or less **charge** counters on it')
    ->register();

gaScenario('bairui-resplendent-barrier-kongming-bonus-sacrifice-empowers-seven-with-three-charge-counters-facing-south')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bairui, Resplendent Barrier')
    ->champion(cardName: 'Kongming, Fel Eidolon')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Bairui, Resplendent Barrier', properties: ['Counters' => ['charge' => 3]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectCardExists('myGraveyard', 'Bairui, Resplendent Barrier', 'Sacrificed')
    ->expectProperty('p1Field-0', 'TurnEffects', '["sqGcyYocLW","EMPOWERED","EMPOWER_PLUS_7"]', 'Empowered by 7')
    ->tested('Bairui, Resplendent Barrier')
    ->covers(['kongming-bonus', 'sacrifice', 'empower', 'condition', 'shifting-currents'], '[Kongming Bonus] **Sacrifice CARDNAME:** **Empower 7**. Activate this ability only if there are three **charge** counters on CARDNAME and only if your Shifting Currents face South.')
    ->register();

gaScenario('covenant-of-thorns-linked-ally-takes-the-damage-dealt-to-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Covenant of Thorns')
    ->seed('myField', 'Giant Tortoise', properties: ['Subcards' => [GaScenario::id('Covenant of Thorns')]])
    ->seed('myField', 'Covenant of Thorns', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Giant Tortoise')]])
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 0, 'Champion damage is redirected')
    ->expectDamage('p1Field-1', 3, 'The linked Giant Tortoise took the three damage instead')
    ->tested('Covenant of Thorns')
    ->covers(['ally-link', 'redirect', 'damage'], 'If damage would be dealt to your champion, that damage is dealt to linked ally instead.')
    ->register();

gaScenario('enthralling-chime-diao-chan-bonus-gains-control-of-an-ally-with-three-wither-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Enthralling Chime')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Counters' => ['wither' => 3]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')->pay(3)
    ->expectProperty('p2Field-1', 'Controller', '1', 'The withered Giant Tortoise is now controlled by the caster')
    ->tested('Enthralling Chime')
    ->covers(['diao-chan-bonus', 'control', 'spell', 'banish-self', 'condition'], '[Diao Chan Bonus] (3), **Banish Enthralling Chime**: As a Spell, gain control of target ally with three or more **wither** counters on it.')
    ->register();

gaScenario('scepter-of-awakening-makes-a-phantasia-an-ally-with-power-and-life-equal-to-its-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myField', 'Scepter of Awakening')
    ->seed('myField', 'Saprotrophy')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-2')
    ->expectPower('p1Field-2', 3, 'Base power 2 (its reserve cost) plus the buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'A buff counter')
    ->tested('Scepter of Awakening')
    ->covers(['activated-ability', 'rest', 'type-change', 'stat-change', 'counter', 'targeting'], '*(2)*, [REST]: Target phantasia you control becomes an ally in addition to its other types with base power and life equal to its reserve cost until end of turn. Put a **buff** counter on it.')
    ->register();

gaScenario('scepter-of-awakening-ability-needs-no-diao-chan-bonus')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Scepter of Awakening')
    ->seed('myField', 'Scepter of Awakening')
    ->seed('myField', 'Saprotrophy')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-2')
    ->expectPower('p1Field-2', 3, 'Another champion: the (2),[REST] ability is unconditional')
    ->tested('Scepter of Awakening')
    ->covers(['activated-ability', 'condition'], '[Diao Chan Bonus] As long as you control four or more phantasias, you may activate this card from your material deck.')
    ->register();

gaScenario('natural-order-puts-hand-and-memory-on-the-bottom-of-the-deck-and-preserved-cards-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Natural Order', classBonus: true, level: 10)
    ->seed('myMemory', 'Gray Wolf')
    ->seed('myMaterial', 'Sempiternal Sage')
    ->seed('myHand', 'Natural Order')
    ->play('Natural Order')
    ->expectZoneCount('myHand', 0, 1, 'The hand went to the bottom of the deck')
    ->expectZoneCount('myMemory', 1, 1, 'Only the preserved card from the material deck is in memory')
    ->expectCardExists('myMemory', 'Sempiternal Sage', 'The preserved Sempiternal Sage came from the material deck')
    ->expectZoneCount('myDeck', 17, 1, '9 + the 7 hand cards + the memory card')
    ->tested('Natural Order')
    ->covers(['zone-movement', 'preserve', 'efficiency'], 'Put all cards from your hand and memory on the bottom of your deck in any order. Then put all **preserved** cards from your material deck into your memory.')
    ->register();

gaScenario('heartsong-reclamation-destroys-regalia-you-do-not-control-and-silvie-bonus-puts-five-buff-counters-on-animal-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, Loved by All')
    ->seed('myField', 'Woodland Squirrels')
    ->seed('myField', 'Gem of Sorority', player: 2)
    ->seed('myHand', 'Heartsong Reclamation')->seed('myHand', 'Gray Wolf')
    ->play('Heartsong Reclamation')->pay(8)->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The opposing regalia was destroyed')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":5}', 'Silvie Bonus: five buff counters')
    ->tested('Heartsong Reclamation')
    ->covers(['destroy', 'regalia', 'cleave', 'silvie-bonus', 'counter'], 'Destroy all regalia you don\'t control. Until end of turn, Animal and/or Beast allies you control gain **cleave**. [Silvie Bonus] Put five **buff** counters on each Animal and/or Beast ally you control.')
    ->register();

gaScenario('heartsong-reclamation-gives-animal-allies-cleave-and-leaves-other-allies-alone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Heartsong Reclamation')
    ->seed('myField', 'Woodland Squirrels')->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Heartsong Reclamation')->seed('myHand', 'Gray Wolf')
    ->play('Heartsong Reclamation')->pay(8)
    ->expectGlobalEffect(1, 'd253WtyIXr_CLEAVE', 'Cleave global effect')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No Silvie: no buff counters')
    ->tested('Heartsong Reclamation')
    ->covers(['cleave', 'keyword'], 'Until end of turn, Animal and/or Beast allies you control gain **cleave**.')
    ->register();

gaScenario('heartsong-reclamation-without-a-silvie-champion-puts-no-buff-counters-even-with-the-class-bonus')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Heartsong Reclamation', classBonus: true)
    ->seed('myField', 'Woodland Squirrels')
    ->seed('myHand', 'Heartsong Reclamation')->seed('myHand', 'Gray Wolf')
    ->play('Heartsong Reclamation')->pay(8)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Tamer class bonus but not Silvie: no buff counters')
    ->tested('Heartsong Reclamation')
    ->covers(['silvie-bonus', 'condition'], '[Silvie Bonus] Put five **buff** counters on each Animal and/or Beast ally you control.')
    ->register();

gaScenario('hymn-of-gaias-grace-puts-an-animal-ally-from-hand-onto-the-field')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hymn of Gaia\'s Grace', level: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Hymn of Gaia\'s Grace')->seed('myHand', 'Woodland Squirrels')
    ->play('Hymn of Gaia\'s Grace')->pay(3)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(1, 'myHand-4')
    ->expectCardExists('myField', 'Woodland Squirrels', 'The Animal ally (reserve 0, LV 2 or less) came from the hand onto the field')
    ->expectZoneCount('myHand', 5, 1, 'Hymn and the Squirrels left the hand; one card drawn')
    ->tested('Hymn of Gaia\'s Grace')
    ->covers(['glimpse', 'draw', 'zone-movement'], '**Glimpse 3**. Draw a card. You may put an Animal or Beast ally card with reserve cost **LV** or less from your hand onto the field.')
    ->register();

gaScenario('channel-manifold-desire-banishes-a-preserved-card-and-empowers-by-its-reserve-cost-plus-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Channel Manifold Desire')
    ->seed('myMaterial', 'Sempiternal Sage')
    ->seed('myHand', 'Channel Manifold Desire')
    ->play('Channel Manifold Desire')->pay(2)->answer(1, 'myMaterial-4')
    ->expectCardExists('myBanish', 'Sempiternal Sage', 'The preserved card was banished as an additional cost')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","kywpjf1b4k-5"]', 'Empower X+2: the banished Sempiternal Sage has reserve cost 3, so 5')
    ->tested('Channel Manifold Desire')
    ->covers(['additional-cost', 'banish', 'preserve', 'empower'], 'As an additional cost to activate this card, banish a **preserved** card from your material deck. **Empower X+2**, where **X** is the reserve cost of the banished card.')
    ->register();

gaScenario('channel-manifold-desire-facing-north-puts-a-preserved-card-into-hand')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myMaterial', 'Sempiternal Sage')->seed('myMaterial', 'Sempiternal Sage')
    ->seed('myHand', 'Channel Manifold Desire')
    ->play('Channel Manifold Desire')->pay(2)->answer(1, 'myMaterial-4')->answer(1, 'myMaterial-4')
    ->expectCardExists('myHand', 'Sempiternal Sage', 'North: the second preserved card went to hand')
    ->tested('Channel Manifold Desire')
    ->covers(['shifting-currents', 'zone-movement', 'preserve'], 'Then if your Shifting Currents face North, you may put a **preserved** card from your material deck into your hand.')
    ->register();

gaScenario('bloom-autumns-fall-class-bonus-opponent-sacrifices-flowerbuds-and-summons-the-chosen-tokens')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloom: Autumn\'s Fall', classBonus: true)
    ->seed('myField', 'Flowerbud', player: 2)->seed('myField', 'Flowerbud', player: 2)
    ->seed('myHand', 'Bloom: Autumn\'s Fall')
    ->play('Bloom: Autumn\'s Fall')->pay(3)->answer(1, '1')
    ->expectZoneCount('theirField', 3, 1, 'Both Flowerbuds were sacrificed and replaced by two tokens')
    ->expectCardExists('theirField', 'Acerbica', 'One Acerbica of the chosen split')
    ->expectCardExists('theirField', 'Washuru', 'And one Washuru')
    ->tested('Bloom: Autumn\'s Fall')
    ->covers(['class-bonus', 'sacrifice', 'summon', 'token'], '[Class Bonus] Each opponent sacrifices all Flowerbuds. For each Flowerbud sacrificed this way, their controller **summons** your choice of an Acerbica or a Washuru token.')
    ->register();

gaScenario('lu-bu-indomitable-titan-wake-up-option-is-only-offered-on-its-first-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lu Bu, Indomitable Titan')
    ->seed('myField', 'Lu Bu, Indomitable Titan')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->pay(2)
    ->attack('myField-1', 'theirField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '1', 'The second attack offered no wake up: Lu Bu stays rested')
    ->tested('Lu Bu, Indomitable Titan')
    ->covers(['on-attack', 'condition'], 'If this is CARDNAME\'s first attack this turn')
    ->register();

gaScenario('lu-bu-indomitable-titan-first-attack-counts-per-unit-not-per-player')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lu Bu, Indomitable Titan')
    ->seed('myField', 'Lu Bu, Indomitable Titan')
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-0')
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->pay(2)
    ->expectProperty('p1Field-1', 'Status', '2', 'It was Lu Bu\'s own first attack: it woke up even though the Dungeon Guide attacked before')
    ->tested('Lu Bu, Indomitable Titan')
    ->covers(['on-attack', 'condition'], 'If this is CARDNAME\'s first attack this turn, you may pay (2). If you do, wake up CARDNAME.')
    ->register();
