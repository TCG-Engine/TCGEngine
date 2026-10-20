<?php
/**
 * Phase 3d (Norm): actions, ninth batch (skills and reactions). 'quiet' deck.
 */

// ---- Golden Gambit ----

gaBonus('golden-gambit-sacrificing-a-chessman-ally-draws-two-cards', 'Alice, Whim\'s Monarch')
    ->seed('myField', 'Weiss Bishop')
    ->seed('myHand', 'Golden Gambit')
    ->play('Golden Gambit')->answer(1, 'myField-1')->pay(3)->passBoth()
    ->expectCardExists('myGraveyard', 'Weiss Bishop', 'The Chessman ally was sacrificed as the additional cost')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus the spell and the three payments, plus the two cards drawn (8 - 1 - 3 + 2)')
    ->tested('Golden Gambit')
    ->covers(['additional-cost', 'sacrifice', 'draw'], 'As an additional cost to activate this card, sacrifice a Chessman ally.', 'Draw two cards.')
    ->register();

// ---- Hide in Bush ----

gaScenario('hide-in-bush-gives-the-champion-stealth-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hide in Bush')
    ->seed('myHand', 'Hide in Bush')
    ->castSpell('Hide in Bush', pay: 5)
    ->expectProperty('p1Field-0', 'TurnEffects', '["STEALTH","vz4kc558yx-ACTION_ACTIVATED"]', 'The champion gained stealth')
    ->tested('Hide in Bush')
    ->covers(['stealth', 'champion'], 'Your champion gains **stealth** until end of turn.')
    ->register();

// ---- Clash of Fates ([Guo Jia Bonus]) ----

gaScenario('clash-of-fates-guo-jia-bonus-removes-a-quest-counter-instead-of-paying-and-buffs-a-fatestone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->patch('myField-0', ['Counters' => ['quest' => 1]])
    ->seed('myField', 'Fatestone of Balance')
    ->seed('myHand', 'Clash of Fates')
    ->play('Clash of Fates')->answer(1, 'YES')->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Fatestone got a buff counter')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The quest counter was removed as the cost')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid from hand')
    ->tested('Clash of Fates')
    ->covers(['guo-jia-bonus', 'alternative-cost', 'counter'], '[Guo Jia Bonus] You may remove a **quest** counter from your champion rather than pay this card\'s reserve cost.', 'Put a **buff** counter on target Fatestone or Fatebound object.')
    ->register();

// ---- False Tidings ----

gaScenario('false-tidings-gives-a-crystal-to-the-opponent-and-draws-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('False Tidings')
    ->seed('myField', 'Gem of Sorority')
    ->seed('myHand', 'False Tidings')
    ->play('False Tidings')->pay(3)->passBoth()->target('myField-1')
    ->expectCardExists('theirField', 'Gem of Sorority', 'The opponent gained control of the Crystal')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand minus the spell and three payments, plus two drawn (8 - 1 - 3 + 2)')
    ->tested('False Tidings')
    ->covers(['control-change', 'draw', 'targeting'], 'Target opponent gains control of target Crystal object you control. If they do, you draw two cards.')
    ->register();

// ---- Infinite Scintillation ----

gaScenario('infinite-scintillation-moves-the-sheen-counters-to-fractured-memories-and-deals-one-plus-x-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Infinite Scintillation')
    ->seed('myMastery', 'Fractured Memories')
    ->patch('myField-0', ['Counters' => ['sheen' => 3]], player: 2)
    ->seed('myHand', 'Infinite Scintillation')
    ->play('Infinite Scintillation')->pay(2)->target('theirField-0')->passBoth()
    ->expectProperty('p1Mastery-0', 'Counters', '{"sheen":3}', 'All three sheen counters moved to Fractured Memories')
    ->expectDamage('p2Field-0', 4, '1 + X damage with X = 3')
    ->tested('Infinite Scintillation')
    ->covers(['sheen', 'counter-move', 'damage'], 'Move all **sheen** counters from target unit to your Fractured Memories. Then deal 1+**X** damage to that unit, where **X** is the amount of counters moved this way.')
    ->register();

// ---- Sparkling Adornment ----

gaScenario('sparkling-adornment-removes-sheen-counters-as-a-cost-and-puts-that-many-on-a-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sparkling Adornment')
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['sheen' => 2]])
    ->seed('myHand', 'Sparkling Adornment')
    ->play('Sparkling Adornment')->answer(1, 'myField-1')->answer(1, '2')->pay(2)->target('theirField-0')
    ->expectProperty('p2Field-0', 'Counters', '{"sheen":2}', 'Two sheen counters moved from the ally onto the target unit')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The ally lost its sheen counters as the cost')
    ->tested('Sparkling Adornment')
    ->covers(['additional-cost', 'sheen', 'counter-move'], 'As an additional cost to activate this card, remove one or more **sheen** counters from an object on the field.', 'Put **X** **sheen** counters on target unit, where **X** is the amount of counters removed.')
    ->register();

// ---- Recurring Invocation ----

gaScenario('recurring-invocation-empower-two-marks-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Recurring Invocation')
    ->seed('myHand', 'Recurring Invocation')
    ->castSpell('Recurring Invocation', pay: 2, window: '')
    ->expectProperty('p1Field-0', 'TurnEffects', '["iyhlctxcrq","EMPOWERED","EMPOWER_PLUS_2","vz4kc558yx-ACTION_ACTIVATED"]', 'Empowered by 2: the next Spell activates as if the champion had +2 level')
    ->tested('Recurring Invocation')
    ->covers(['empower'], '**Empower 2**.')
    ->register();

gaScenario('recurring-invocation-class-bonus-champion-level-up-banishes-it-from-the-graveyard-and-pays-one-to-empower')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'MAGE')
    ->seed('myGraveyard', 'Recurring Invocation')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-1')->answer(1, 'YES')->pay(1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["ENTERED_THIS_TURN","iyhlctxcrq","EMPOWERED","EMPOWER_PLUS_2"]', 'The champion levelled up, the card was banished from the graveyard for (1), and the champion is empowered by 2')
    ->expectCardExists('myBanish', 'Recurring Invocation', 'Banished from the graveyard')
    ->tested('Recurring Invocation')
    ->covers(['class-bonus', 'trigger', 'level-up', 'banish', 'empower'], '[Class Bonus] Whenever your champion levels up, you may banish this card from your graveyard and pay (1). If you do, **empower 2**.')
    ->register();

// ---- Resonant Aether ----

gaScenario('resonant-aether-loads-itself-into-an-aetherwing-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Resonant Aether')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Resonant Aether')
    ->castSpell('Resonant Aether', pay: 1, window: '')
    ->expectProperty('p1Field-1', 'Subcards', '["3ppahdhe7g"]', 'Resonant Aether is loaded into the Aetherwing weapon')
    ->tested('Resonant Aether')
    ->covers(['load', 'aetherwing'], 'Load Resonant Aether into an Aetherwing weapon you control.')
    ->register();

// ---- Stand Fast ----

gaScenario('stand-fast-wakes-an-ally-in-your-end-phase-and-gives-it-taunt-and-plus-one-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stand Fast')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'Stand Fast')
    ->endTurn()->answer(1, 'myHand-7')->pay(2)->target('myField-1')->passBoth()
    ->expectProperty('p1Field-1', 'Status', '2', 'woke up')->expectProperty('p1Field-1', 'TurnEffects', '["TAUNT_NEXT_TURN","ao1cfkhbp6"]', 'It will have taunt from the next turn on')
    ->expectLife('p1Field-1', 4, 'Dungeon Guide\'s 3 life + 1')
    ->tested('Stand Fast')
    ->covers(['wake-up', 'taunt', 'stat-change'], 'Wake up target ally you control. It gains **taunt** and gets +1 [LIFE] until the beginning of your next turn.')
    ->register();

// ---- Trump Set ----

gaScenario('trump-set-class-bonus-redirects-an-attack-to-a-suited-ally-which-gets-plus-three-power-and-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Trump Set', classBonus: true)
    ->seed('myField', 'Noire, Ace of Spades')
    ->seed('myHand', 'Trump Set')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(1)->target('myField-1')->passBoth()
    ->expectDamage('p1Field-0', 0, 'The champion was no longer the target')
    ->expectDamage('p1Field-1', 1, 'Noire (1 life + 3) took the Dungeon Guide\'s 1 damage and survived')
    ->expectPower('p1Field-1', 4, 'Noire got +3 power')
    ->expectLife('p1Field-1', 4, 'Noire got +3 life')
    ->tested('Trump Set')
    ->covers(['reaction', 'class-bonus', 'cost-reduction', 'retarget', 'stat-change'], 'Change the target of an attack to a Suited ally you control. If you do, that ally gets +3[POWER] and +3[LIFE] until end of turn.')
    ->register();

// ---- Beckon Attention ----

gaScenario('beckon-attention-class-bonus-costs-nothing-and-retargets-the-attack-to-the-casters-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Beckon Attention', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Beckon Attention')
    ->reachTurnThree(fastCardInHand: true)
    ->attack('myField-1', 'theirField-0')->answer(1, 'myHand-7')->passBoth()
    ->expectGlobalEffect(1, 'gpmJdGYqoC', 'The caster gained the Crowd\'s Favor status')
    ->expectDamage('p2Field-0', 0, 'The attack no longer targets the opposing champion (a two-player game: the new target is the caster\'s own champion, so the attack deals no damage)')
    ->expectZoneCount('myMemory', 0, 1, 'Costs 2 less: nothing was paid')
    ->tested('Beckon Attention')
    ->covers(['reaction', 'class-bonus', 'cost-reduction', 'retarget', 'crowds-favor'], '[Class Bonus] This card costs 2 less to activate.', 'Change the target of an attack that targets a unit you don\'t control to your champion. If you do, you gain the Crowd\'s Favor status.')
    ->register();

// ---- Crowd's Favor (status) ----

gaScenario('crowds-favor-the-holders-own-attack-declaration-is-free')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->globalEffect('gpmJdGYqoC', 1)
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 1, 'Attacking while holding the status costs nothing: the Dungeon Guide hit the opposing champion')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->tested('Crowd\'s Favor')
    ->covers(['status', 'attack-tax', 'condition'], 'Players can\'t declare attacks targeting an object you control unless they pay (1) for each attack declaration.')
    ->register();

gaScenario('crowds-favor-an-attack-targeting-the-holder-costs-one-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->globalEffect('gpmJdGYqoC', 1)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->pay(1, 2)->answer(2, 'theirField-0')->pass(1)
    ->expectZoneCount('theirMemory', 1, 1, 'Declaring an attack at the status holder cost player 2 one payment')
    ->tested('Crowd\'s Favor')
    ->covers(['status', 'attack-tax'], 'Players can\'t declare attacks targeting an object you control unless they pay (1) for each attack declaration.')
    ->register();

// ---- Break the Line ----

gaScenario('break-the-line-banishes-a-warrior-champion-from-the-material-deck-to-end-the-combat-phase')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Break the Line')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->answer(1, 'myMaterial-0')->passBoth()
    ->expectDamage('p1Field-0', 0, 'The combat phase ended: the attack dealt no damage')
    ->expectCardExists('myBanish', 'Lorraine, Wandering Warrior', 'The Warrior champion card was banished from the material deck')
    ->tested('Break the Line')
    ->covers(['reaction', 'banish', 'end-combat'], 'You may banish a Warrior champion card from your material deck. If you do, end the combat phase.')
    ->register();

// ---- Counter Interference ----

gaScenario('counter-interference-negates-an-action-that-targets-an-automaton-ally-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Charged Mannequin')
    ->seed('myHand', 'Counter Interference')
    ->seed('myHand', 'Aenean Ward', player: 2)
    ->endTurn()->answer(2, 'myHand-7')->pay(1, 2)->target('theirField-1', 2)
    ->answer(1, 'myHand-7')->pay(2)->pass(2)->pass(1)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Aenean Ward was negated: the Automaton ally has no damage prevention')
    ->expectCardExists('myGraveyard', 'Counter Interference', 'The reaction resolved')
    ->tested('Counter Interference')
    ->covers(['reaction', 'negate', 'targeting', 'automaton'], '**Negate** target trigger or action card activation that targets an Automaton ally you control.')
    ->register();

gaScenario('without-counter-interference-the-action-targeting-the-automaton-ally-resolves')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Charged Mannequin')
    ->seed('myHand', 'Aenean Ward', player: 2)
    ->endTurn()->answer(2, 'myHand-7')->pay(1, 2)->target('theirField-1', 2)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'Nothing negated it: the Automaton ally has the damage prevention')
    ->tested('Counter Interference')
    ->covers(['reaction', 'negate', 'condition'], '**Negate** target trigger or action card activation that targets an Automaton ally you control.')
    ->register();

// ---- Royal Line Defense ----

gaScenario('royal-line-defense-first-mode-gives-a-chessman-ally-plus-three-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Golden Pawn')
    ->seed('myHand', 'Royal Line Defense')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(3)->answer(1, 'YES')->target('myField-1')->passBoth()->pass(2)->pass(1)
    ->expectLife('p1Field-1', 4, 'Golden Pawn\'s 1 life + 3')
    ->expectDamage('p1Field-1', 1, 'It survived the Dungeon Guide\'s attack with the extra life')
    ->tested('Royal Line Defense')
    ->covers(['reaction', 'modal', 'stat-change'], 'Target Chessman ally gets +3[LIFE] until end of turn.')
    ->register();

gaScenario('royal-line-defense-second-mode-with-no-other-card-activation-to-negate-the-attack-still-hits')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myHand', 'Royal Line Defense')
    ->seed('myField', 'Weiss Bishop', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(3)->answer(1, 'NO')->passBoth()
    ->expectDamage('p1Field-0', 1, 'The attack itself is not a card activation: there was nothing to negate and the Weiss Bishop dealt its 1 damage')
    ->tested('Royal Line Defense')
    ->covers(['reaction', 'modal', 'condition'], '**Negate** target card activation if a Chessman ally is attacking.')
    ->register();
