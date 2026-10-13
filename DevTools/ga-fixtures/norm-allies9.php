<?php
/**
 * Phase 3d (Norm): allies, ninth batch (Advanced Imbue angels). 'quiet' deck. The imbue YES/NO precedes payment, and the payment is the hand pick of the revealed cards:
 * Ghastly Slime is an Umbra (advanced element) card, Dungeon Guide is not.
 */

// ---- Angel Attendant ----

gaScenario('angel-attendant-advanced-imbue-one-with-an-advanced-card-becomes-imbued')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Angel Attendant')
    ->play('Angel Attendant')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'One of the two revealed cards is advanced: the Attendant is imbued')
    ->tested('Angel Attendant')
    ->covers(['imbue', 'advanced-imbue'], '**Advanced Imbue 1** *(You may reserve all cards revealed as you activate this card. If at least one of them is advanced element, this card becomes **imbued**.)*')
    ->register();

gaScenario('angel-attendant-advanced-imbue-one-without-an-advanced-card-is-not-imbued')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Angel Attendant')
    ->play('Angel Attendant')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No advanced card among the revealed ones: not imbued')
    ->tested('Angel Attendant')
    ->covers(['imbue', 'advanced-imbue', 'condition'], '**Advanced Imbue 1**')
    ->register();

gaScenario('angel-attendant-imbued-has-intercept')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myField', 'Angel Attendant', properties: ['TurnEffects' => ['IMBUED']])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
    ->expectDamage('p1Field-0', 0, 'The imbued Attendant intercepted: the champion took nothing')
    ->expectDamage('p1Field-1', 1, 'The Attendant took the redirected attack')
    ->tested('Angel Attendant')
    ->covers(['imbue', 'intercept', 'condition'], 'As long as CARDNAME is **imbued**, it has **intercept**.')
    ->register();

gaScenario('angel-attendant-not-imbued-has-no-intercept')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myField', 'Angel Attendant')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 1, 'Not imbued: no intercept, the champion took the attack')
    ->tested('Angel Attendant')
    ->covers(['imbue', 'intercept', 'condition'], 'As long as CARDNAME is **imbued**, it has **intercept**.')
    ->register();

gaScenario('angel-attendant-on-death-draws-a-card')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myField', 'Angel Attendant')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectCardExists('myGraveyard', 'Angel Attendant', 'The Steed\'s 3 power destroyed the 3-life Attendant')
    ->expectZoneCount('myHand', 8, 1, 'On Death: a card was drawn (7 + 1)')
    ->tested('Angel Attendant')
    ->covers(['on-death', 'draw'], '**On Death:** Draw a card.')
    ->register();

// ---- Angelic Vanguard ----

gaScenario('angelic-vanguard-advanced-imbue-two-with-two-advanced-cards-becomes-imbued')
    ->deckTemplate('quiet', 'Angelic Vanguard')
    ->readyFor('Angelic Vanguard')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Angelic Vanguard')
    ->play('Angelic Vanguard')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Three revealed advanced cards: imbued')
    ->tested('Angelic Vanguard')
    ->covers(['imbue', 'advanced-imbue'], '**Advanced Imbue 2** *(You may reserve all cards revealed as you activate this card. If at least two of them are advanced element, this card becomes **imbued**.)*')
    ->register();

gaScenario('angelic-vanguard-advanced-imbue-two-with-only-one-advanced-card-is-not-imbued')
    ->deckTemplate('quiet', 'Angelic Vanguard')
    ->readyFor('Angelic Vanguard')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Angelic Vanguard')
    ->play('Angelic Vanguard')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Only one advanced card of the three: not imbued')
    ->tested('Angelic Vanguard')
    ->covers(['imbue', 'advanced-imbue', 'condition'], '**Advanced Imbue 2**')
    ->register();

gaScenario('angelic-vanguard-imbued-has-intercept')
    ->deckTemplate('quiet', 'Angelic Vanguard')
    ->readyFor('Angelic Vanguard')
    ->seed('myField', 'Angelic Vanguard', properties: ['TurnEffects' => ['IMBUED']])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
    ->expectDamage('p1Field-0', 0, 'The imbued Vanguard intercepted')
    ->expectDamage('p1Field-1', 1, 'The Vanguard took the redirected attack')
    ->tested('Angelic Vanguard')
    ->covers(['imbue', 'intercept', 'condition'], 'As long as CARDNAME is **imbued**, it has **intercept** and **retort 2**.')
    ->register();

gaNormRetort('angelic-vanguard-imbued-has-retort-two', 'Angelic Vanguard', 4, 'Imbued retort 2: retaliating with 2 + 2 power', 'As long as CARDNAME is **imbued**, it has **intercept** and **retort 2**.', false, ['imbue', 'retort', 'combat'], ['TurnEffects' => ['IMBUED']]);

// ---- Benediction Angel ----

gaScenario('benediction-angel-imbued-enter-draws-into-memory-and-recovers-two')
    ->deckTemplate('quiet', 'Benediction Angel')
    ->readyFor('Benediction Angel', damage: 5)
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Benediction Angel')
    ->play('Benediction Angel')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectDamage('p1Field-0', 3, 'Imbued On Enter: recover 2 (5 - 2)')
    ->expectZoneCount('myMemory', 3, 1, 'Imbued On Enter: two revealed cards reserved plus one drawn into memory')
    ->tested('Benediction Angel')
    ->covers(['imbue', 'on-enter', 'draw-into-memory', 'recover', 'condition'], '**On Enter:** If CARDNAME is **imbued**, draw a card into your memory and **recover 2.**')
    ->register();

gaScenario('benediction-angel-not-imbued-enter-does-nothing')
    ->deckTemplate('quiet', 'Benediction Angel')
    ->readyFor('Benediction Angel', damage: 5)
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Benediction Angel')
    ->play('Benediction Angel')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectDamage('p1Field-0', 5, 'Not imbued: no recovery')
    ->expectZoneCount('myMemory', 2, 1, 'Not imbued: only the two reserved cards')
    ->tested('Benediction Angel')
    ->covers(['imbue', 'on-enter', 'condition'], '**On Enter:** If CARDNAME is **imbued**')
    ->register();

// ---- Reverent Seraphim ----

gaScenario('reverent-seraphim-imbued-other-allies-get-minus-one-power')
    ->deckTemplate('quiet', 'Reverent Seraphim')
    ->readyFor('Reverent Seraphim')
    ->seed('myField', 'Reverent Seraphim', properties: ['TurnEffects' => ['IMBUED']])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->observe()
    ->expectPower('p1Field-2', 0, 'Imbued: your other ally gets -1 power')
    ->expectPower('p2Field-1', 0, 'Imbued: "other allies" includes the opponent\'s ally')
    ->expectPower('p1Field-1', 0, 'The Seraphim itself keeps its printed 0 power')
    ->tested('Reverent Seraphim')
    ->covers(['imbue', 'stat-change', 'static'], 'As long as CARDNAME is **imbued**, other allies get -1[POWER].')
    ->register();

gaScenario('reverent-seraphim-not-imbued-leaves-other-allies-alone')
    ->deckTemplate('quiet', 'Reverent Seraphim')
    ->readyFor('Reverent Seraphim')
    ->seed('myField', 'Reverent Seraphim')
    ->seed('myField', 'Dungeon Guide')
    ->observe()
    ->expectPower('p1Field-2', 1, 'Not imbued: the Guide keeps its 1 power')
    ->tested('Reverent Seraphim')
    ->covers(['imbue', 'stat-change', 'condition'], 'As long as CARDNAME is **imbued**, other allies get -1[POWER].')
    ->register();

// ---- Cardinal of Divine Rite ----

gaScenario('cardinal-of-divine-rite-angel-cards-cannot-become-imbued')
    ->deckTemplate('quiet', 'Angel Attendant')
    ->readyFor('Angel Attendant')
    ->seed('myField', 'Cardinal of Divine Rite')
    ->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Angel Attendant')
    ->play('Angel Attendant')->pay(2)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'The Cardinal stops the Angel Attendant from becoming imbued (the imbue is not even offered)')
    ->tested('Cardinal of Divine Rite')
    ->covers(['imbue', 'restriction', 'static'], 'Angel cards and objects can\'t become **imbued**.')
    ->register();
