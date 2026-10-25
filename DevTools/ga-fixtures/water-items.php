<?php
/**
 * Phase 3b (Water): items, regalia, phantasias, domains and weapons. 'Giant Tortoise' is the vanilla water ally used as a link target / filler.
 */

gaScenario('aquatech-shell-links-to-an-ally-for-plus-two-life')
    ->standardDeck('AquaTech Shell')
    ->readyFor('AquaTech Shell')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'AquaTech Shell')
    ->play('AquaTech Shell')->answer(1, 'myField-1')->pay(3)->passBoth()
    ->expectLife('p1Field-1', 8, 'The linked 1/6 ally gets +2 LIFE')
    ->expectProperty('p1Field-2', 'Counters', '{"linkedToAlly":"L0RmNaDzhk"}', 'The Shell is linked to the Giant Tortoise')
    ->tested('AquaTech Shell')
    ->covers(['ally-link', 'stat-change'], '**Ally Link**', 'Linked ally gets +2[LIFE] and has "**On Attack: Recover 2.**"')
    ->register();

gaScenario('fluvial-fatestone-on-enter-gives-an-ally-plus-two-life')
    ->standardDeck('Fluvial Fatestone')
    ->readyFor('Fluvial Fatestone')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Fluvial Fatestone')
    ->play('Fluvial Fatestone')->pay(2)->answer(1, 'myField-1')->passBoth()
    ->expectLife('p1Field-1', 8, 'On Enter: the Giant Tortoise gets +2 LIFE until end of turn')
    ->tested('Fluvial Fatestone')
    ->covers(['on-enter', 'stat-change', 'fast-activation'], '**On Enter**: Target ally you control gets +2 [LIFE] until end of turn.')
    ->register();

gaScenario('pelagic-fatestone-on-enter-draws-a-card-into-memory')
    ->standardDeck('Pelagic Fatestone')
    ->readyFor('Pelagic Fatestone')
    ->seed('myHand', 'Pelagic Fatestone')
    ->play('Pelagic Fatestone')->pay(4)->passBoth()
    ->expectZoneCount('myMemory', 5, 1, 'Four paid cards plus the card drawn into memory')
    ->tested('Pelagic Fatestone')
    ->covers(['on-enter', 'draw-into-memory'], '**On Enter**: Draw a card into your memory.')
    ->register();

gaScenario('wavekeepers-bond-recovers-two-when-your-champion-levels-up')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(damage: 5)
    ->seed('myField', 'Wavekeeper\'s Bond')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myMemory-0')
    ->expectProperty('p1Field-1', 'CardID', 'DpHDGaX2Pn', 'The champion levelled up')
    ->expectDamage('p1Field-1', 3, 'Whenever your champion levels up, recover 2: 5 damage became 3')
    ->tested('Wavekeeper\'s Bond')
    ->covers(['triggered-ability', 'level-up', 'recover'], 'Whenever your champion levels up, **recover 2**.')
    ->register();

gaScenario('soothing-potion-sacrifice-draws-a-card-and-recovers-three')
    ->standardDeck('Soothing Potion')
    ->readyFor('Soothing Potion', damage: 5)
    ->seed('myField', 'Soothing Potion')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 2, 'Recover 3: 5 damage became 2')
    ->expectZoneCount('myHand', 8, 1, 'Drew a card (not brewed: only one)')
    ->expectZoneCount('myField', 1, 1, 'The potion was sacrificed')
    ->tested('Soothing Potion')
    ->covers(['sacrifice', 'draw', 'recover'], '**Sacrifice CARDNAME:** Draw a card and **recover 3**.')
    ->register();

// ---- phantasias ----
gaScenario('deep-sea-fractal-on-enter-mills-the-top-card-of-each-deck')
    ->standardDeck('Deep Sea Fractal')
    ->readyFor('Deep Sea Fractal')
    ->seed('myHand', 'Deep Sea Fractal')
    ->castSpell('Deep Sea Fractal', pay: 2, window: 'both')
    ->expectZoneCount('myGraveyard', 1, 1, 'On Enter: our top card was milled')
    ->expectZoneCount('theirGraveyard', 1, 1, 'On Enter: their top card was milled too')
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it entered rested')
    ->tested('Deep Sea Fractal')
    ->covers(['on-enter', 'mill', 'hindered'], '**On Enter:** Each player puts the top card of their deck into their graveyard.')
    ->register();

gaScenario('dissonant-fractal-on-enter-glimpses-four')
    ->standardDeck('Dissonant Fractal')
    ->readyFor('Dissonant Fractal')
    ->seed('myHand', 'Dissonant Fractal')
    ->play('Dissonant Fractal')->pay(1)->answer(1, 'Top=px60u5n1do,px60u5n1do;Bottom=em6eEh9q8y,2d7rgchttu')->passBoth()
    ->expectProperty('myDeck-0', 'CardID', 'px60u5n1do', 'Glimpse 4: the rearranged order put two cards on the bottom')
    ->tested('Dissonant Fractal')
    ->covers(['on-enter', 'glimpse'], '**On Enter:** **Glimpse 4**.')
    ->register();

gaScenario('tidal-fractal-on-enter-mills-two-from-the-target-player')
    ->standardDeck('Tidal Fractal')
    ->readyFor('Tidal Fractal')
    ->seed('myHand', 'Tidal Fractal')
    ->play('Tidal Fractal')->pay(3)->answer(1, '1')->passBoth()
    ->expectZoneCount('theirGraveyard', 2, 1, 'The opponent put the top two cards of their deck into their graveyard')
    ->expectZoneCount('myGraveyard', 0, 1, 'Ours is untouched')
    ->tested('Tidal Fractal')
    ->covers(['on-enter', 'mill', 'targeting'], '**On Enter: **Target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('fractal-of-intrusion-on-enter-banishes-a-floating-memory-card-to-discard-from-their-memory')
    ->standardDeck('Fractal of Intrusion')
    ->readyFor('Fractal of Intrusion')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Fractal of Intrusion')
    ->play('Fractal of Intrusion')->pay(3)->answer(1, 'myGraveyard-0')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectZoneCount('theirMemory', 0, 1, 'The chosen card left their memory')
    ->expectZoneCount('theirGraveyard', 1, 1, 'It was discarded')
    ->tested('Fractal of Intrusion')
    ->covers(['on-enter', 'floating-memory', 'banish', 'discard'], '**On Enter:** You may banish a card with **floating memory** from your graveyard. When you do, look at target opponent\'s memory and discard a card from it.')
    ->register();

gaScenario('hydrating-fractal-recovers-one-when-your-champion-levels-up')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(damage: 3)
    ->seed('myField', 'Hydrating Fractal')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myMemory-0')
    ->expectProperty('p1Field-1', 'CardID', 'DpHDGaX2Pn', 'The champion levelled up')
    ->expectDamage('p1Field-1', 2, 'Whenever your champion levels up, recover 1: 3 damage became 2')
    ->tested('Hydrating Fractal')
    ->covers(['triggered-ability', 'level-up', 'recover'], 'Whenever your champion levels up, **recover 1**.')
    ->register();

gaScenario('fractal-of-snow-class-bonus-sacrifice-makes-allies-enter-rested')
    ->standardDeck('Fractal of Snow')
    ->readyFor('Fractal of Snow', classBonus: true)
    ->seed('myField', 'Fractal of Snow')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectGlobalEffect(1, 'uhuy4xippo', 'The next allies to enter the field this turn enter rested')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Fractal was sacrificed')
    ->tested('Fractal of Snow')
    ->covers(['sacrifice', 'enters-rested', 'class-bonus'], '[Class Bonus] **Sacrifice CARDNAME:** The next time one or more allies would enter the field this turn, they enter the field rested instead.')
    ->register();

gaScenario('relic-of-sunken-past-recollection-sacrifices-itself-to-draw-with-three-water-cards')
    ->standardDeck('Relic of Sunken Past')
    ->readyFor('Relic of Sunken Past')
    ->seed('myField', 'Relic of Sunken Past')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->reachTurnThree()->answer(1, 'YES')
    ->expectCardExists('myGraveyard', 'Relic of Sunken Past', 'Three water cards: the Relic was sacrificed')
    ->expectZoneCount('myHand', 9, 1, 'Drew a card: 8 + turn draw 1 + 1')
    ->tested('Relic of Sunken Past')
    ->covers(['triggered-ability', 'recollection', 'sacrifice', 'draw'], 'At the beginning of your recollection phase, if there are three or more water element cards in your graveyard, you may sacrifice CARDNAME. If you do, draw a card.')
    ->register();

gaScenario('oceans-blessing-links-to-an-ally-for-plus-one-life-and-taunt')
    ->standardDeck('Ocean\'s Blessing')
    ->readyFor('Ocean\'s Blessing')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Ocean\'s Blessing')
    ->play('Ocean\'s Blessing')->answer(1, 'myField-1')->pay(2)->passBoth()
    ->expectLife('p1Field-1', 7, 'The linked 1/6 ally gets +1 LIFE')
    ->expectProperty('p1Field-2', 'Counters', '{"linkedToAlly":"L0RmNaDzhk"}', 'Linked to the Giant Tortoise')
    ->tested('Ocean\'s Blessing')
    ->covers(['ally-link', 'stat-change', 'taunt'], 'Linked ally gets +1 [LIFE] and has **taunt**.')
    ->register();

// ---- domains ----
gaScenario('krustallan-ruins-the-controller-pays-one-and-the-ally-stays-awake')
    ->standardDeck('Krustallan Ruins')
    ->readyFor('Krustallan Ruins')
    ->seed('myField', 'Krustallan Ruins')
    ->seed('myHand', 'Giant Tortoise')
    ->play('Giant Tortoise')->pay(4)->answer(1, 'YES')->pay(1)->passBoth()
    ->expectProperty('p1Field-2', 'Status', '2', 'Paid (1): the ally stays awake')
    ->expectZoneCount('myMemory', 5, 1, 'Four for the ally and one to keep it awake')
    ->tested('Krustallan Ruins')
    ->covers(['triggered-ability', 'enters-rested', 'pay-or-else', 'domain'], 'Whenever an ally enters the field under a player\'s control, rest that ally unless that player pays (1).')
    ->register();

gaScenario('hidden-enclave-mills-the-turn-players-top-card-at-their-recollection')
    ->standardDeck('Hidden Enclave')
    ->readyFor('Hidden Enclave')
    ->seed('myField', 'Hidden Enclave')
    ->reachTurnThree()
    ->expectZoneCount('myGraveyard', 1, 1, 'Our recollection phase: we put the top card of our deck into our graveyard', 3)
    ->endTurn(1)->pass(2)
    ->expectZoneCount('myGraveyard', 1, 2, 'Their recollection phase: they put the top card of their deck into their graveyard')
    ->expectZoneCount('myGraveyard', 1, 1, 'Ours is unchanged by their recollection')
    ->tested('Hidden Enclave')
    ->covers(['triggered-ability', 'recollection', 'mill', 'domain'], 'At the beginning of each player\'s recollection phase, that player puts the top card of their deck into their graveyard.')
    ->register();

gaScenario('krustallan-ruins-rests-an-entering-ally-unless-its-controller-pays-one')
    ->standardDeck('Krustallan Ruins')
    ->readyFor('Krustallan Ruins')
    ->seed('myField', 'Krustallan Ruins')
    ->seed('myHand', 'Giant Tortoise')
    ->play('Giant Tortoise')->pay(4)->answer(1, 'NO')->passBoth()
    ->expectProperty('p1Field-2', 'Status', '1', 'The controller declined to pay (1): the entering ally was rested')
    ->tested('Krustallan Ruins')
    ->covers(['triggered-ability', 'enters-rested', 'pay-or-else', 'domain'], 'Whenever an ally enters the field under a player\'s control, rest that ally unless that player pays (1).')
    ->register();

// ---- weapons ----
gaScenario('hoarfrost-spine-on-enter-looks-at-the-top-card-and-may-mill-it')
    ->standardDeck('Hoarfrost Spine')
    ->readyFor('Hoarfrost Spine')
    ->seed('myHand', 'Hoarfrost Spine')
    ->play('Hoarfrost Spine')->pay(2)->answer(1, 'YES')
    ->expectZoneCount('myGraveyard', 1, 1, 'The looked-at top card was put into the graveyard')
    ->expectZoneCount('myDeck', 8, 1, 'The deck is one card shorter')
    ->tested('Hoarfrost Spine')
    ->covers(['on-enter', 'look', 'mill'], '**On Enter:** Look at the top card of your deck. You may put that card into your graveyard.')
    ->register();

gaScenario('hoarfrost-spine-declining-keeps-the-top-card-on-the-deck')
    ->standardDeck('Hoarfrost Spine')
    ->readyFor('Hoarfrost Spine')
    ->seed('myHand', 'Hoarfrost Spine')
    ->play('Hoarfrost Spine')->pay(2)->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 0, 1, 'Declined: nothing was milled')
    ->expectZoneCount('myDeck', 9, 1, 'The deck is untouched')
    ->tested('Hoarfrost Spine')
    ->covers(['on-enter', 'look', 'optional'], '**On Enter:** Look at the top card of your deck. You may put that card into your graveyard.')
    ->register();

gaScenario('krustallan-longsword-class-bonus-gets-plus-one-power-with-four-water-cards')
    ->standardDeck('Krustallan Longsword')
    ->readyFor('Krustallan Longsword', classBonus: true)
    ->seed('myField', 'Krustallan Longsword')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->observe()
    ->expectPower('p1Field-1', 2, 'Four water cards: 1 + 1 = 2 POWER')
    ->tested('Krustallan Longsword')
    ->covers(['static-ability', 'class-bonus', 'stat-change'], '[Class Bonus] As long as you have four or more water element cards in your graveyard, Krustallan Longsword gets +1 [POWER].')
    ->register();

gaScenario('krustallan-longsword-with-three-water-cards-keeps-its-printed-power')
    ->standardDeck('Krustallan Longsword')
    ->readyFor('Krustallan Longsword', classBonus: true)
    ->seed('myField', 'Krustallan Longsword')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->observe()
    ->expectPower('p1Field-1', 1, 'Three water cards: the printed 1 POWER')
    ->tested('Krustallan Longsword')
    ->covers(['static-ability', 'class-bonus', 'condition'], '[Class Bonus] As long as you have four or more water element cards in your graveyard, Krustallan Longsword gets +1 [POWER].')
    ->register();

// ---- Floating Memory on items / phantasias / domains ----
$gaFloatClause = '**Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost.)*';
$gaFloatCbClause = '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion\'s class matches this card\'s class.)*';
foreach (['AquaTech Shield' => 'aquatech-shield', 'Pelagic Fatestone' => 'pelagic-fatestone', 'Glacier Remnants' => 'glacier-remnants'] as $gaFloatCard => $gaFloatSlug) {
    gaFloatingPays($gaFloatSlug . '-floating-memory-pays-a-memory-cost', $gaFloatCard, $gaFloatClause);
}
foreach (['AquaTech Shell' => ['aquatech-shell', 'WARRIOR'], 'Freezing Round' => ['freezing-round', 'RANGER'], 'Martial Flowstate' => ['martial-flowstate', 'WARRIOR'], "Ocean's Blessing" => ['oceans-blessing', 'TAMER'], 'Relic of Sunken Past' => ['relic-of-sunken-past', 'TAMER']] as $gaFloatCard => [$gaFloatSlug, $gaFloatClass]) {
    gaFloatingPays($gaFloatSlug . '-class-bonus-floating-memory-pays-a-memory-cost', $gaFloatCard, $gaFloatCbClause, $gaFloatClass);
    gaFloatingPays($gaFloatSlug . '-without-the-class-bonus-cannot-pay-a-memory-cost', $gaFloatCard, $gaFloatCbClause, 'MAGE', false);
}

// ---- regalia and more phantasias ----
gaScenario('wand-of-frost-class-bonus-on-enter-draws-a-card')
    ->standardDeck('Wand of Frost')
    ->readyFor('Wand of Frost', classBonus: true)
    ->seed('myField', 'Wand of Frost')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: drew a card')
    ->tested('Wand of Frost')
    ->covers(['on-enter', 'class-bonus', 'draw'], '[Class Bonus] **On Enter:** Draw a card.')
    ->register();

gaScenario('wand-of-frost-banish-gives-a-units-attacks-minus-three-power')
    ->standardDeck('Wand of Frost')
    ->readyFor('Wand of Frost')
    ->seed('myField', 'Wand of Frost')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'theirField-1')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","n0wpbhigka"]', 'The target unit\'s attacks get -3 POWER until end of turn')
    ->expectZoneCount('myBanish', 1, 1, 'The Wand banished itself as its cost')
    ->tested('Wand of Frost')
    ->covers(['activated-ability', 'banish-self', 'stat-change'], '**Banish CARDNAME:** Target unit\'s attacks get -3 [POWER] until end of turn.')
    ->register();

gaScenario('diamond-ribbon-banish-generates-two-bolts-of-diamonds-into-memory')
    ->deck("# Material\n1 Spirit of Water\n# Main\n14 Dungeon Guide")
    ->readyFor('Diamond Ribbon')
    ->seed('myField', 'Diamond Ribbon')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->answer(1, 'YES')->answer(1, 'YES')
    ->expectCardExists('myMemory', 'Bolt of Diamonds', 'A Bolt of Diamonds was generated into memory')
    ->expectZoneCount('myMemory', 6, 1, 'Four paid cards plus two generated Bolts of Diamonds')
    ->expectZoneCount('myBanish', 1, 1, 'The Ribbon banished itself')
    ->tested('Diamond Ribbon')
    ->covers(['activated-ability', 'banish-self', 'generate', 'cost-reduction'], '(4)**, Banish CARDNAME:** **Generate** up to two Bolt of Diamonds cards and put them into your memory.')
    ->register();

gaScenario('temptations-facade-on-enter-draws-a-card')
    ->standardDeck('Temptation\'s Facade')
    ->readyFor('Temptation\'s Facade')
    ->seed('myField', 'Temptation\'s Facade')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: drew a card')
    ->tested('Temptation\'s Facade')
    ->covers(['on-enter', 'draw'], '**On Enter:** Draw a card.')
    ->register();

gaScenario('magebane-lash-class-bonus-on-enter-puts-a-lash-counter-on-your-champion')
    ->standardDeck('Magebane Lash')
    ->readyFor('Magebane Lash', classBonus: true)
    ->seed('myField', 'Magebane Lash')
    ->observe()
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"GUARDIAN"},"lash":1}', 'On Enter: a lash counter went on the champion')
    ->expectPower('p1Field-1', 1, 'Magebane Lash gets +1 POWER for the lash counter: 0 + 1')
    ->tested('Magebane Lash')
    ->covers(['on-enter', 'class-bonus', 'lash-counter', 'stat-change'], '[Class Bonus] **On Enter:** Put a **lash** counter on your champion.', 'CARDNAME gets +1 [POWER] for each **lash** counter on your champion.')
    ->register();

gaScenario('fractal-of-polar-depths-class-bonus-sacrifice-mills-the-target-player')
    ->standardDeck('Fractal of Polar Depths')
    ->readyFor('Fractal of Polar Depths', classBonus: true)
    ->seed('myField', 'Fractal of Polar Depths')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'NO')
    ->expectZoneCount('theirGraveyard', 3, 1, 'X = 3 water cards in our graveyard (two seeded and the sacrificed Fractal): the opponent milled three')
    ->expectCardExists('myGraveyard', 'Fractal of Polar Depths', 'The Fractal was sacrificed')
    ->tested('Fractal of Polar Depths')
    ->covers(['sacrifice', 'class-bonus', 'mill', 'targeting'], '[Class Bonus] **Sacrifice CARDNAME:** Target player puts the top **X** cards from their deck into their graveyard, where **X** is the amount of water element cards in your graveyard.')
    ->register();

gaScenario('glacier-remnants-recollection-removes-durability-counters-and-recovers')
    ->standardDeck('Glacier Remnants')
    ->readyFor('Glacier Remnants', damage: 5)
    ->seed('myField', 'Glacier Remnants')
    ->reachTurnThree()->answer(1, '2')
    ->expectDamage('p1Field-0', 3, 'Two durability counters removed: recover 2, so 5 damage became 3')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'Two of the six durability counters were removed')
    ->tested('Glacier Remnants')
    ->covers(['triggered-ability', 'recollection', 'durability', 'recover', 'domain'], 'At the beginning of your recollection phase, remove up to two **durability** counters from CARDNAME. Then **recover X**, where **X** is the amount of counters removed this way.')
    ->register();

gaScenario('martial-flowstate-class-bonus-sacrifices-itself-when-your-champion-levels-up')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Martial Flowstate')
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myMemory-0')
    ->expectProperty('p1Field-0', 'CardID', 'DpHDGaX2Pn', 'The champion levelled up and is still on the field')
    ->expectCardExists('myGraveyard', 'Martial Flowstate', 'Martial Flowstate sacrificed itself, not the champion')
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","fekR8D4FpB_NEXT_ATTACK"]', 'The champion\'s next attack gets +2 POWER')
    ->tested('Martial Flowstate')
    ->covers(['triggered-ability', 'class-bonus', 'level-up', 'sacrifice', 'draw-into-memory'], '[Class Bonus] Whenever your champion levels up, sacrifice CARDNAME, draw a card into your memory, and your champion\'s next attack this turn gets +2[POWER].')
    ->register();
