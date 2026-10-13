<?php
/**
 * Phase 3d (Norm): allies, fourth batch (ranged allies and attack-conditional power). 'quiet' deck.
 */

// ---- Ranged family: the ally attacks from player 2 while distant (1 + the ranged amount, or its power + the amount) ----

gaWindRanged('hidden-longbowman-ranged-two-while-distant', 'Hidden Longbowman', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2** *(As long as this unit is distant, its attacks get +2 [POWER].)*');
gaWindRanged('horse-archer-class-bonus-ranged-two-while-distant', 'Horse Archer', 3, '[Class Bonus] Ranged 2 while distant: 1 + 2 = 3 damage', '[Class Bonus] **Ranged 2** *(As long as this unit is **distant**, its attacks get +2 [POWER].)*', true, ['class-bonus', 'keyword', 'ranged', 'combat']);
gaWindRanged('horse-archer-without-the-class-bonus-has-no-ranged', 'Horse Archer', 1, 'No Class Bonus: only its printed 1 POWER', '[Class Bonus] **Ranged 2**', false, ['class-bonus', 'ranged', 'condition']);
gaWindRanged('lone-gunslinger-ranged-one-while-distant', 'Lone Gunslinger', 2, 'Ranged 1 while distant: 1 + 1 = 2 damage', '**Ranged 1** *(As long as this unit is **distant**, its attacks get +1 [POWER].)*');
gaWindRanged('rampart-defender-class-bonus-ranged-three-while-distant', 'Rampart Defender', 5, '[Class Bonus] Ranged 3 while distant: 2 + 3 = 5 damage', '[Class Bonus] **Ranged 3** *(As long as this unit is **distant**, its attacks get +3[POWER].)*', true, ['class-bonus', 'keyword', 'ranged', 'combat']);
gaWindRanged('rampart-defender-without-the-class-bonus-has-no-ranged', 'Rampart Defender', 2, 'No Class Bonus: only its printed 2 POWER', '[Class Bonus] **Ranged 3**', false, ['class-bonus', 'ranged', 'condition']);
gaWindRanged('seasoned-archer-class-bonus-ranged-three-while-distant', 'Seasoned Archer', 4, '[Class Bonus] Ranged 3 while distant: 1 + 3 = 4 damage', '[Class Bonus] **Ranged 3** *(As long as this unit is **distant**, its attacks get +3 [POWER].)*', true, ['class-bonus', 'keyword', 'ranged', 'combat']);
gaWindRanged('signal-gunner-ranged-two-while-distant', 'Signal Gunner', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2[POWER].)*');
gaWindRanged('dyadic-fletcher-ranged-two-while-distant', 'Dyadic Fletcher', 4, 'Ranged 2 while distant: 2 + 2 = 4 damage', '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2[POWER].)*');
gaWindRanged('charged-hunter-ranged-two-while-distant', 'Charged Hunter', 4, 'Ranged 2 while distant: 2 + 2 = 4 damage', '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2 [POWER].)*');
gaScenario('marksman-captain-ranged-two-while-distant')
    ->deckTemplate('quiet', 'Marksman Captain')
    ->readyFor('Marksman Captain', player: 2)
    ->seed('myField', 'Marksman Captain', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->answer(2, 'myField-0')
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage')
    ->tested('Marksman Captain')
    ->covers(['keyword', 'ranged', 'combat'], '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2 [POWER].)*')
    ->register();

gaScenario('marksman-captain-enter-makes-another-unit-distant')
    ->deckTemplate('quiet', 'Marksman Captain')
    ->readyFor('Marksman Captain')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Marksman Captain')
    ->play('Marksman Captain')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'On Enter: the target unit became distant')
    ->tested('Marksman Captain')
    ->covers(['on-enter', 'distant', 'targeting'], '**On Enter:** Another target unit you control becomes **distant**.')
    ->register();
gaScenario('nimble-longbowman-ranged-one-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nimble Longbowman', player: 2)
    ->seed('myField', 'Nimble Longbowman', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->answer(2, 'myField-0')
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 2, 'Ranged 1 while distant: 1 + 1 = 2 damage')
    ->tested('Nimble Longbowman')
    ->covers(['keyword', 'ranged', 'combat'], '**Fast Activation**, **Ranged 1**')
    ->register();
gaWindRanged('renascent-sharpshooter-ranged-one-while-distant', 'Renascent Sharpshooter', 3, 'Ranged 1 while distant: 2 + 1 = 3 damage', '**Ranged 1** *(As long as this unit is **distant**, its attacks get +1[POWER].)*');
gaWindRanged('veltech-armiger-ranged-three-while-distant', 'VelTech Armiger', 4, 'Ranged 3 while distant: 1 + 3 = 4 damage', '**Ranged 3** *(As long as this unit is **distant**, its attacks get +3[POWER].)*');
gaWindRanged('alacritous-huntress-ranged-two-while-distant', 'Alacritous Huntress', 4, 'Ranged 2 while distant: 2 + 2 = 4 damage', '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2 [POWER].)*');

// ---- Horned Knight ----

gaScenario('horned-knight-attacking-an-ally-gets-plus-one-power')
    ->deckTemplate('quiet', 'Horned Knight')
    ->readyFor('Horned Knight')
    ->seed('myField', 'Horned Knight')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'Attacking an ally: 2 + 1 = 3 damage destroyed the 3-life Guide', 2)
    ->tested('Horned Knight')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as Horned Knight is attacking an ally, Horned Knight gets +1[POWER].')
    ->register();

gaScenario('horned-knight-attacking-a-champion-has-its-printed-power')
    ->deckTemplate('quiet', 'Horned Knight')
    ->readyFor('Horned Knight')
    ->seed('myField', 'Horned Knight')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 2, 'Attacking a champion: the printed 2 power')
    ->tested('Horned Knight')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as Horned Knight is attacking an ally, Horned Knight gets +1[POWER].')
    ->register();

// ---- Golden Rook ----

gaScenario('golden-rook-attacking-a-unit-with-even-life-gets-plus-one-power')
    ->deckTemplate('quiet', 'Golden Rook')
    ->readyFor('Golden Rook')
    ->seed('myField', 'Golden Rook')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('myGraveyard', 'Gray Wolf', 'The Wolf has 2 life (even): 1 + 1 = 2 damage destroyed it', 2)
    ->tested('Golden Rook')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as CARDNAME is attacking a unit with an even life stat, CARDNAME gets +1[POWER].')
    ->register();

gaScenario('golden-rook-attacking-a-unit-with-odd-life-has-its-printed-power')
    ->deckTemplate('quiet', 'Golden Rook')
    ->readyFor('Golden Rook')
    ->seed('myField', 'Golden Rook')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 1, 'The Guide has 3 life (odd): only the printed 1 damage')
    ->tested('Golden Rook')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as CARDNAME is attacking a unit with an even life stat, CARDNAME gets +1[POWER].')
    ->register();

// ---- Deadly Opportunist ----

gaScenario('deadly-opportunist-attacking-a-rested-ally-gets-plus-three-power')
    ->deckTemplate('quiet', 'Deadly Opportunist')
    ->readyFor('Deadly Opportunist')
    ->seed('myField', 'Deadly Opportunist')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 1])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 4, 'Attacking a rested ally: 1 + 3 = 4 damage')
    ->tested('Deadly Opportunist')
    ->covers(['condition', 'stat-change', 'combat'], 'Deadly Opportunist gets +3[POWER] as long as it\'s attacking a rested ally.')
    ->register();

gaScenario('deadly-opportunist-attacking-an-awake-ally-has-its-printed-power')
    ->deckTemplate('quiet', 'Deadly Opportunist')
    ->readyFor('Deadly Opportunist')
    ->seed('myField', 'Deadly Opportunist')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 1, 'Attacking an awake ally: the printed 1 damage')
    ->tested('Deadly Opportunist')
    ->covers(['condition', 'stat-change', 'combat'], 'Deadly Opportunist gets +3[POWER] as long as it\'s attacking a rested ally.')
    ->register();

// ---- Port Smuggler ----

gaScenario('port-smuggler-class-bonus-attack-cannot-be-intercepted')
    ->deckTemplate('quiet', 'Port Smuggler')
    ->readyFor('Port Smuggler', classBonus: true)
    ->seed('myField', 'Port Smuggler')
    ->seed('myField', 'Fatebound Caracal', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 1, '[Class Bonus] The attack on the champion could not be intercepted')
    ->expectDamage('p2Field-1', 0, 'The Caracal was never offered the intercept')
    ->tested('Port Smuggler')
    ->covers(['class-bonus', 'intercept', 'combat'], '[Class Bonus] CARDNAME\'s attacks can\'t be intercepted.')
    ->register();

gaScenario('port-smuggler-without-the-class-bonus-can-be-intercepted')
    ->deckTemplate('quiet', 'Port Smuggler')
    ->readyFor('Port Smuggler')
    ->seed('myField', 'Port Smuggler')
    ->seed('myField', 'Fatebound Caracal', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')->answer(1, 'theirField-0')->target('myField-1', 2)->answer(2, '-')
    ->expectDamage('p2Field-0', 0, 'No class bonus: the Caracal intercepted')
    ->expectDamage('p2Field-1', 1, 'The Caracal took the redirected attack')
    ->tested('Port Smuggler')
    ->covers(['class-bonus', 'intercept', 'condition'], '[Class Bonus] CARDNAME\'s attacks can\'t be intercepted.')
    ->register();

// ---- Nimble Longbowman ----

gaScenario('nimble-longbowman-enter-makes-the-champion-distant-and-the-class-bonus-enters-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nimble Longbowman', classBonus: true)
    ->seed('myHand', 'Nimble Longbowman')
    ->play('Nimble Longbowman')->pay(2)->pass(2)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","DISTANT"]', 'On Enter: the champion became distant')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', '[Class Bonus] The Longbowman entered the field distant')
    ->tested('Nimble Longbowman')
    ->covers(['class-bonus', 'on-enter', 'distant'], '[Class Bonus] CARDNAME enters the field **distant**.', '**On Enter:** Your champion becomes **distant**.')
    ->register();

gaScenario('nimble-longbowman-without-the-class-bonus-enters-not-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nimble Longbowman')
    ->seed('myHand', 'Nimble Longbowman')
    ->play('Nimble Longbowman')->pay(2)->pass(2)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","DISTANT"]', 'On Enter: the champion still became distant')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No class bonus: the Longbowman is not distant')
    ->tested('Nimble Longbowman')
    ->covers(['class-bonus', 'condition', 'distant'], '[Class Bonus] CARDNAME enters the field **distant**.')
    ->register();
