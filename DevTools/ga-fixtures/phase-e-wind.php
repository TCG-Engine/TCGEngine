<?php
/**
 * Phase E (Wind): rules clauses recorded as unexercised: Stifling Gyre pay-or-negate, Diablerie relic theft,
 * Tempestuous Seraphim imbued On Enter, Ranger-champion targets for Sleety Retreat / Tinderflare Pivot.
 */

const GA_GYRE_TEXT = 'Whenever an **on enter** ability of an ally with the chosen name triggers, **negate** that trigger unless its controller pays (4).';

// The opponent's Gyre names Dungeon Guide; the entering player declines the (4): the On Enter is negated.
gaScenario('stifling-gyre-declined-payment-negates-the-named-allys-on-enter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dungeon Guide')
    ->seed('theirField', 'Stifling Gyre')->answer(2, 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'NO')
    ->expectDqEmpty()
    ->expectZoneCount('myMemory', 3, 1, 'Negated: the Dungeon Guide On Enter (banish two from memory) never offered')
    ->expectCardExists('myField', 'Dungeon Guide', 'The ally still entered; only the trigger was negated')
    ->tested('Stifling Gyre')
    ->covers(['on-enter', 'negate', 'pay-or-negate'], GA_GYRE_TEXT)
    ->register();

gaScenario('stifling-gyre-paid-four-lets-the-named-allys-on-enter-resolve')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dungeon Guide')
    ->seed('theirField', 'Stifling Gyre')->answer(2, 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'YES')->answer(1, 'myHand-0')->answer(1, 'myHand-0')->answer(1, 'myHand-0')->answer(1, 'myHand-0')->answer(1, 'YES')->answer(1, 'myMaterial-0')
    ->expectZoneCount('myBanish', 2, 1, 'Paid (4): the On Enter resolved and banished two cards from memory')
    ->expectCardExists('myField', 'Lorraine, Wandering Warrior', 'The On Enter levelled the champion up')
    ->expectDqEmpty()
    ->tested('Stifling Gyre')
    ->covers(['on-enter', 'negate', 'pay-or-negate'], GA_GYRE_TEXT)
    ->register();

const GA_DIABLERIE_TEXT = 'The next time a regalia with **divine relic** would enter the field this turn, it enters the field under your control instead.';

gaScenario('diablerie-effect-expires-at-the-end-of-the-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Diablerie')
    ->seed('myHand', 'Diablerie')
    ->play('Diablerie')->pay(2)
    ->endTurn()
    ->expectGlobalEffect(1, '0plqbtjuxz', 'Expired with the turn', 0)
    ->tested('Diablerie')
    ->covers(['divine-relic', 'replacement-effect', 'duration'], 'The next time a regalia with **divine relic** would enter the field this turn')
    ->register();

const GA_SERAPHIM_ENTER_TEXT = '**On Enter**: As a Spell, if CARDNAME is **imbued**, deal 4 damage to target attacking ally.';

gaScenario('tempestuous-seraphim-imbued-enter-deals-four-damage-to-the-attacking-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tempestuous Seraphim')
    ->seed('myHand', 'Ghastly Slime')->seed('myHand', 'Ghastly Slime')->seed('myHand', 'Ghastly Slime')
    ->seed('myHand', 'Tempestuous Seraphim')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-10')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'theirField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Three advanced cards revealed: imbued')
    ->expectDamage('p2Field-1', 4, 'The attacking ally took 4')
    ->expectDqEmpty()
    ->tested('Tempestuous Seraphim')
    ->covers(['on-enter', 'imbue', 'damage'], GA_SERAPHIM_ENTER_TEXT)
    ->register();

gaScenario('tempestuous-seraphim-not-imbued-enter-deals-no-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tempestuous Seraphim')
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Tempestuous Seraphim')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-10')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No advanced card revealed: not imbued')
    ->expectDamage('p2Field-1', 0, 'Not imbued: the attacking ally took no damage')
    ->expectDqEmpty()
    ->tested('Tempestuous Seraphim')
    ->covers(['on-enter', 'imbue', 'condition'], GA_SERAPHIM_ENTER_TEXT)
    ->register();

const GA_SLEETY_TEXT = 'Target Ranger unit becomes **distant**. If that unit is defending, end the combat phase unless an opponent pays (2).';

gaScenario('sleety-retreat-can-target-a-ranger-champion')
    ->standardDeck('Sleety Retreat')
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myHand', 'Sleety Retreat')
    ->castSpell('Sleety Retreat', pay: 2, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The Ranger champion became distant')
    ->expectDqEmpty()
    ->tested('Sleety Retreat')
    ->covers(['distant', 'targeting', 'champion'], GA_SLEETY_TEXT)
    ->register();

gaScenario('sleety-retreat-cannot-target-a-non-ranger-champion')
    ->standardDeck('Sleety Retreat')
    ->champion(lineage: ['Spirit of Water'], classes: 'MAGE')
    ->seed('myHand', 'Sleety Retreat')
    ->castSpell('Sleety Retreat', pay: 2, window: 'both')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]', 'A Mage champion is not a Ranger unit: no distant')
    ->tested('Sleety Retreat')
    ->covers(['distant', 'targeting', 'condition'], 'Target Ranger unit becomes **distant**.')
    ->register();

gaScenario('tinderflare-pivot-can-target-a-ranger-champion')
    ->standardDeck('Tinderflare Pivot')
    ->champion(lineage: ['Spirit of Fire'], classes: 'RANGER')
    ->seed('myHand', 'Tinderflare Pivot')
    ->castSpell('Tinderflare Pivot', pay: 2, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT","RANGED_1"]', 'The Ranger champion became distant and gained ranged 1')
    ->expectDqEmpty()
    ->tested('Tinderflare Pivot')
    ->covers(['distant', 'targeting', 'champion'], 'Target Ranger unit becomes **distant** and gains **ranged 1** until end of turn.')
    ->register();

gaScenario('sleety-retreat-defending-ranger-opponent-declines-to-pay-and-combat-ends')
    ->standardDeck('Sleety Retreat')
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myHand', 'Sleety Retreat')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->pass(2)->answer(1, 'myField-0')->answer(2, 'NO')->pass(1)
    ->expectDamage('p1Field-0', 0, 'P2 declined to pay (2): the combat phase ended before damage')
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The defending Ranger champion became distant')
    ->expectDqEmpty()
    ->tested('Sleety Retreat')
    ->covers(['distant', 'combat', 'pay-or-end'], GA_SLEETY_TEXT)
    ->register();

gaScenario('sleety-retreat-defending-ranger-opponent-pays-two-and-combat-continues')
    ->standardDeck('Sleety Retreat')
    ->champion(lineage: ['Spirit of Water'], classes: 'RANGER')
    ->seed('myHand', 'Sleety Retreat')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(2)->pass(1)->pass(2)->answer(1, 'myField-0')->answer(2, 'YES')->answer(2, 'myHand-0')->answer(2, 'myHand-0')->pass(1)
    ->expectDamage('p1Field-0', 1, 'P2 paid (2): combat continued and the Tortoise dealt damage')
    ->expectZoneCount('theirMemory', 2, 1, 'P2 reserved two cards to pay')
    ->expectDqEmpty()
    ->tested('Sleety Retreat')
    ->covers(['distant', 'combat', 'pay-or-end'], GA_SLEETY_TEXT)
    ->register();
