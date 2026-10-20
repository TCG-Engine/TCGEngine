<?php
/**
 * Phase 3e (Arcane). readyFor() enables the arcane element (and the class bonus where asked) through the champion lineage; LV is the champion's level. 'quiet' deck.
 */

// ---- Charge Static ----

gaScenario('charge-static-puts-lv-static-counters-on-an-arcane-element-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Charge Static', level: 2)
    ->seed('myField', 'Conduit of the Mad Mage')
    ->seed('myHand', 'Charge Static')
    ->play('Charge Static')->pay(2)->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"static":2}', 'LV 2: two static counters')
    ->tested('Charge Static')
    ->covers(['counter', 'targeting', 'lv'], 'Put **LV** **static** counters on target arcane element object you control.')
    ->register();

// ---- Erratic Bolt ----

gaScenario('erratic-bolt-deals-lv-damage-to-a-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Erratic Bolt', level: 3)
    ->seed('myHand', 'Erratic Bolt')
    ->play('Erratic Bolt')->pay(4)->target('theirField-0')
    ->expectDamage('p2Field-0', 3, 'LV 3 damage')
    ->tested('Erratic Bolt')
    ->covers(['damage', 'targeting', 'lv'], 'Deal **LV** damage to target unit.')
    ->register();

gaScenario('erratic-bolt-class-bonus-banishes-two-random-memory-cards-to-draw-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Erratic Bolt', classBonus: true, level: 3)
    ->seed('myHand', 'Erratic Bolt')
    ->play('Erratic Bolt')->pay(4)->target('theirField-0')->answer(1, 'YES')
    ->expectZoneCount('myBanish', 2, 1, 'Two random memory cards were banished')
    ->expectZoneCount('myMemory', 2, 1, 'Four paid minus the two banished')
    ->expectZoneCount('myHand', 5, 1, 'Eight in hand minus the spell and four payments, plus two drawn (8 - 1 - 4 + 2)')
    ->tested('Erratic Bolt')
    ->covers(['class-bonus', 'banish', 'random', 'draw'], '[Class Bonus] You may banish two cards at random from your memory. If you do, draw two cards.')
    ->register();

// ---- Fulminating Storm ----

gaScenario('fulminating-storm-removes-enlighten-counters-to-damage-the-opposing-champion-and-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fulminating Storm', classBonus: true)
    ->patch('myField-0', ['Counters' => ['enlighten' => 6, '_overrides' => ['classes' => 'MAGE']]])
    ->seed('myHand', 'Fulminating Storm')
    ->play('Fulminating Storm')->pay(3)->passBoth()->answer(1, '6')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"}}', 'All six enlighten counters were removed (only the class override remains)')
    ->expectDamage('p2Field-0', 6, 'X = 6 damage to the opposing champion')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand minus the spell and three payments, plus two cards (one per three counters) (8 - 1 - 3 + 2)')
    ->tested('Fulminating Storm')
    ->covers(['counter-cost', 'damage', 'class-bonus', 'draw'], 'Remove up to nine **enlighten** counters from your champion. Deal **X** damage to each champion you don\'t control, where **X** is the amount of counters removed this way.', '[Class Bonus] For every three **enlighten** counters removed this way, draw a card.')
    ->register();

// ---- Shock Therapy ----

gaScenario('shock-therapy-deals-damage-equal-to-the-enlighten-counters-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shock Therapy')
    ->patch('myField-0', ['Counters' => ['enlighten' => 2]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Shock Therapy')
    ->play('Shock Therapy')->pay(2)->target('theirField-1')->passBoth()
    ->expectDamage('p2Field-1', 2, 'Two enlighten counters: 2 damage to the ally')
    ->tested('Shock Therapy')
    ->covers(['damage', 'counter', 'targeting'], 'Deal an amount of damage to target ally equal to the amount of **enlighten** counters on your champion.')
    ->register();

// ---- Triboelectric Fortification ----

gaScenario('triboelectric-fortification-removes-three-static-counters-to-deal-that-much-damage-and-add-durability')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Triboelectric Fortification', classBonus: true)
    ->seed('myField', 'Training Sword')
    ->seed('myField', 'Conduit of the Mad Mage', properties: ['Counters' => ['static' => 3]])
    ->seed('myHand', 'Triboelectric Fortification')
    ->play('Triboelectric Fortification')->pay(1)->answer(1, 'myField-2')->answer(1, 'myField-2')->answer(1, 'myField-2')->target('theirField-0')->target('myField-1')
    ->expectProperty('p1Field-2', 'Counters', '[]', 'All three static counters were removed from the one object')
    ->expectDamage('p2Field-0', 3, 'X = 3 damage to the unit')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":5}', 'The Training Sword\'s 2 durability counters plus X = 3')
    ->tested('Triboelectric Fortification')
    ->covers(['counter-cost', 'damage', 'durability'], 'Remove up to three **static** counters from among objects you control. Deal **X** damage to target unit, where **X** is the amount of counters removed this way. Then put **X** **durability** counters on a Warrior weapon you control.')
    ->register();

// ---- Conduit of the Mad Mage ----

gaScenario('conduit-of-the-mad-mage-activating-a-mage-spell-wakes-it-and-gives-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Conduit of the Mad Mage', classBonus: true)
    ->seed('myField', 'Conduit of the Mad Mage', properties: ['Status' => 1])
    ->seed('myHand', 'Break Apart')
    ->seed('myField', 'Training Sword', player: 2)
    ->play('Break Apart')->pay(5)->target('theirField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'Activating the Mage Spell woke the Conduit')
    ->expectPower('p1Field-1', 1, '0 power + 1 until end of turn')
    ->tested('Conduit of the Mad Mage')
    ->covers(['trigger', 'wake-up', 'stat-change'], 'Whenever you activate a Mage Spell action, wake up CARDNAME, and it gets +1 [POWER] until end of turn.')
    ->register();

gaScenario('conduit-of-the-mad-mage-is-sacrificed-at-the-beginning-of-your-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Conduit of the Mad Mage')
    ->endTurn()->pass(1)->pass(1)
    ->expectCardExists('myGraveyard', 'Conduit of the Mad Mage', 'Sacrificed at the beginning of the end phase')
    ->tested('Conduit of the Mad Mage')
    ->covers(['trigger', 'sacrifice', 'end-phase'], 'At the beginning of your end phase, sacrifice CARDNAME.')
    ->register();

// ---- Charger X Ultra ----

gaScenario('charger-x-ultra-recollection-puts-a-static-counter-on-an-object-that-has-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Charger X Ultra')
    ->patch('myField-0', ['Counters' => ['static' => 1]])
    ->seed('myField', 'Charger X Ultra')
    ->reachTurnThree()->target('myField-0')
    ->expectProperty('p1Field-0', 'Counters', '{"static":2}', 'The champion\'s static counter went from 1 to 2')
    ->tested('Charger X Ultra')
    ->covers(['trigger', 'recollection', 'counter'], 'At the beginning of each player\'s recollection phase, put a **static** counter on target object with one or more **static** counters on it.')
    ->register();

// ---- Ionizer X Ultra ----

gaScenario('ionizer-x-ultra-enter-puts-two-static-counters-on-the-linked-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ionizer X Ultra')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Ionizer X Ultra')
    ->play('Ionizer X Ultra')->target('myField-1')->pay(1)
    ->expectProperty('p1Field-1', 'Counters', '{"static":2}', 'Two static counters on the linked ally')
    ->expectZoneCount('myMemory', 1, 1, 'Only two static counters: nothing drawn into memory (the one payment is in memory)')
    ->tested('Ionizer X Ultra')
    ->covers(['on-enter', 'object-link', 'counter'], '**On Enter:** Put two **static** counters on linked object.')
    ->register();

gaScenario('ionizer-x-ultra-four-or-more-static-counters-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ionizer X Ultra')
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['static' => 2]])
    ->seed('myHand', 'Ionizer X Ultra')
    ->play('Ionizer X Ultra')->target('myField-1')->pay(1)
    ->expectProperty('p1Field-1', 'Counters', '{"static":4}', 'Two more static counters: four')
    ->expectZoneCount('myMemory', 2, 1, 'Four or more static counters: a card was drawn into memory (plus the one payment)')
    ->tested('Ionizer X Ultra')
    ->covers(['on-enter', 'object-link', 'counter', 'draw-into-memory', 'condition'], 'Then if there are four or more **static** counters on it, draw a card into your memory.')
    ->register();

// ---- Tome of Sacred Lightning ----

gaScenario('tome-of-sacred-lightning-class-bonus-rest-banishes-a-random-memory-card-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Sacred Lightning', classBonus: true)
    ->seed('myField', 'Tome of Sacred Lightning')
    ->seed('myMemory', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myBanish', 1, 1, 'The only memory card was banished at random')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is empty')
    ->expectZoneCount('myHand', 8, 1, 'A card was drawn because one was banished (7 + 1)')
    ->tested('Tome of Sacred Lightning')
    ->covers(['class-bonus', 'activated-ability', 'rest', 'banish', 'random', 'draw'], '[Class Bonus] [REST]**:** Banish a card at random from your memory. If a card was banished this way, draw a card.')
    ->register();

gaScenario('tome-of-sacred-lightning-with-an-empty-memory-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Sacred Lightning', classBonus: true)
    ->seed('myField', 'Tome of Sacred Lightning')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myHand', 7, 1, 'No memory card to banish: no draw')
    ->tested('Tome of Sacred Lightning')
    ->covers(['class-bonus', 'condition'], 'If a card was banished this way, draw a card.')
    ->register();

// ---- Surging Search ----

gaScenario('surging-search-scavenges-six-for-an-arcane-element-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Surging Search')
    ->deckTop(['Dungeon Guide', 'Charge Static'])
    ->seed('myHand', 'Surging Search')
    ->play('Surging Search')->pay(2)
    ->expectCardExists('myHand', 'Charge Static', 'The arcane card within the top six was put into the hand')
    ->tested('Surging Search')
    ->covers(['scavenge'], '**Scavenge 6** for an arcane element card.')
    ->register();

gaFloatingPays('surging-search-class-bonus-floating-memory-pays-a-memory-cost', 'Surging Search', '[Class Bonus] **Floating Memory**', 'MAGE');
gaFloatingPays('surging-search-without-the-class-bonus-cannot-pay-a-memory-cost', 'Surging Search', '[Class Bonus] **Floating Memory**', 'WARRIOR', false);
gaFloatingPays('conduit-of-the-mad-mage-floating-memory-pays-a-memory-cost', 'Conduit of the Mad Mage', '**Floating Memory**');

// ---- Shattering Discharge ----

gaScenario('shattering-discharge-deals-two-unpreventable-damage-to-a-champion-and-banishes-itself')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shattering Discharge')
    ->seed('myHand', 'Shattering Discharge')
    ->play('Shattering Discharge')->pay(1)->target('theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 2, '2 damage to the opposing champion')
    ->expectCardExists('myBanish', 'Shattering Discharge', 'The card banished itself')
    ->tested('Shattering Discharge')
    ->covers(['damage', 'unpreventable', 'banish-self', 'targeting'], 'Deal 2 unpreventable damage to target champion. Banish CARDNAME.')
    ->register();

gaScenario('shattering-discharge-class-bonus-banished-from-memory-gets-a-charge-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Sacred Lightning', classBonus: true)
    ->seed('myField', 'Tome of Sacred Lightning')
    ->seed('myMemory', 'Shattering Discharge')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('myBanish-0', 'Counters', '{"charge":1}', 'It got a charge counter')
    ->tested('Shattering Discharge')
    ->covers(['class-bonus', 'trigger', 'banish', 'counter'], '[Class Bonus] Whenever this card is banished from your memory, put a **charge** counter on it.')
    ->register();

gaScenario('shattering-discharge-with-a-charge-counter-can-be-activated-from-the-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shattering Discharge')
    ->seed('myBanish', 'Shattering Discharge', properties: ['Counters' => ['charge' => 1]])
    ->action(1, 10002, 'myBanish-0!FSM!')->pay(1)->target('theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 2, 'Activated from the banishment: 2 damage to the opposing champion')
    ->expectProperty('myBanish-0', 'Counters', '[]', 'It banished itself again, without the charge counter')
    ->tested('Shattering Discharge')
    ->covers(['banishment-activation', 'counter'], 'You may activate this card from your banishment as long as there\'s a **charge** counter on it.')
    ->register();

gaScenario('shattering-discharge-without-a-charge-counter-cannot-be-activated-from-the-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shattering Discharge')
    ->seed('myBanish', 'Shattering Discharge')
    ->action(1, 10002, 'myBanish-0!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 7, 1, 'No payment prompt: the activation was refused')
    ->tested('Shattering Discharge')
    ->covers(['banishment-activation', 'condition'], 'You may activate this card from your banishment as long as there\'s a **charge** counter on it.')
    ->register();

// ---- Arcane Elemental ----

gaScenario('arcane-elemental-class-bonus-costs-one-less-for-each-arcane-card-in-the-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arcane Elemental', classBonus: true, level: 7)
    ->seed('myBanish', 'Charge Static')
    ->seed('myBanish', 'Charge Static')
    ->seed('myHand', 'Arcane Elemental')
    ->play('Arcane Elemental')->pay(5)
    ->expectCardExists('myField', 'Arcane Elemental', '7 - 2 arcane cards in the banishment = 5 payments')
    ->tested('Arcane Elemental')
    ->covers(['class-bonus', 'cost-reduction', 'banishment'], '[Class Bonus] This card costs 1 less to activate for each arcane element card in your banishment.')
    ->register();

gaScenario('arcane-elemental-on-attack-banishes-itself-at-the-next-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arcane Elemental', classBonus: true, level: 7)
    ->seed('myField', 'Arcane Elemental')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 7, 'Seven power at level 7 or higher')
    ->endTurn()
    ->expectCardExists('myBanish', 'Arcane Elemental', 'At the beginning of the next end phase it was banished')
    ->tested('Arcane Elemental')
    ->covers(['pride', 'on-attack', 'delayed-trigger', 'banish'], '**On Attack:** At the beginning of the next end phase, banish CARDNAME.')
    ->register();

gaScenario('arcane-elemental-pride-seven-will-not-attack-below-level-seven')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Arcane Elemental', classBonus: true, level: 6)
    ->seed('myField', 'Arcane Elemental')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '2', 'Level 6: the attack declaration was refused and the Elemental is still awake')
    ->tested('Arcane Elemental')
    ->covers(['pride', 'condition'], '**Pride 7** *(This ally won\'t obey you unless your champion is level 7 or higher.)*')
    ->register();

// ---- Harbinger of Lightning ----

gaScenario('harbinger-of-lightning-class-bonus-on-death-banishes-itself-and-a-memory-card-to-deal-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Harbinger of Lightning', classBonus: true)
    ->seed('myField', 'Harbinger of Lightning')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->attack('myField-2', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-0', 5, 'Harbinger (2) plus the banished Dungeon Guide (3): 5 damage to the opposing champion')
    ->expectZoneCount('myBanish', 2, 1, 'Harbinger and the memory card were banished')
    ->expectZoneCount('myMemory', 0, 1, 'Memory is empty')
    ->tested('Harbinger of Lightning')
    ->covers(['class-bonus', 'on-death', 'banish', 'damage'], '[Class Bonus] **On Death**: Banish CARDNAME, and a card at random from your memory. When you do, as a Spell, deal **X** damage to target champion where **X** is the total reserve cost of the banished cards.')
    ->register();

// ---- Ionized Asceticism ([Rai Bonus] Efficiency) ----

gaScenario('ionized-asceticism-rai-bonus-efficiency-banishes-hand-and-memory-draws-seven-and-lowers-the-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Rai, Spellcrafter', lineage: ['Lorraine, Arclight Saber'])
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Ionized Asceticism')
    ->play('Ionized Asceticism')->pay(14)
    ->expectZoneCount('myHand', 7, 1, 'Everything in hand and memory was banished, then seven cards were drawn')
    ->expectZoneCount('myMemory', 0, 1, 'Memory was banished too')
    ->expectLevel('p1Field-0', -9, 'Rai\'s level 1 minus 10 until the next turn')
    ->tested('Ionized Asceticism')
    ->covers(['rai-bonus', 'efficiency', 'banish', 'draw', 'level'], 'Banish all cards from your hand and memory, then draw seven cards. Until the beginning of your next turn, your champion gets -10 level.')
    ->register();

// ---- Seiryuu's Command ----

gaScenario('seiryuus-command-the-beast-triggers-its-on-attack-abilities-twice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Seiryuu\'s Command')
    ->seed('myField', 'Firebird Trailblazer')
    ->seed('myHand', 'Seiryuu\'s Command')
    ->reachTurnThree(fastCardInHand: true)
    ->play('Seiryuu\'s Command')->pay(2)->target('myField-1')->passBoth()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->answer(1, 'myHand-0')->answer(1, 'YES')->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 3, 1, 'The Command plus two discards: the On Attack resolved twice')
    ->expectZoneCount('myDeck', 6, 1, 'And two cards were drawn')
    ->tested('Seiryuu\'s Command')
    ->covers(['on-attack', 'double-trigger', 'beast'], 'Until end of turn, whenever target Beast attacks, trigger all of its **on attack** abilities twice.')
    ->register();

// ---- Reversal's Polarity ----

gaScenario('reversals-polarity-a-negated-arcane-activation-deals-damage-equal-to-its-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Surging Search')
    ->seed('myField', 'Reversal\'s Polarity')
    ->seed('myHand', 'Surging Search')
    ->seed('myHand', 'Incapacitate', player: 2)
    ->play('Surging Search')->pay(2)->answer(2, 'myHand-7')->pay(4, 2)->answer(2, 'EffectStack-0')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Surging Search (reserve 2) was negated: 2 damage to the opposing champion')
    ->tested('Reversal\'s Polarity')
    ->covers(['trigger', 'negate', 'damage', 'arcane'], 'Whenever an arcane element card activation you control is **negated**, deal **X** damage to target champion, where **X** is that card\'s reserve cost.')
    ->register();

gaScenario('reversals-polarity-an-activation-that-resolves-deals-no-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Surging Search')
    ->seed('myField', 'Reversal\'s Polarity')
    ->seed('myHand', 'Surging Search')
    ->deckTop(['Charge Static'])
    ->play('Surging Search')->pay(2)->pass(2)->answer(1, 'myTempZone-5')
    ->expectDamage('p2Field-0', 0, 'Nothing was negated: no damage')
    ->tested('Reversal\'s Polarity')
    ->covers(['trigger', 'condition'], 'Whenever an arcane element card activation you control is **negated**')
    ->register();

// ---- Staves X Ultra ([Lorraine Bonus]) ----

if (!function_exists('gaStaves')) {
    function gaStaves(string $slug): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Lorraine, Arclight Saber'])
            ->seed('myField', 'Training Sword', properties: ['Subcards' => [GaScenario::id('Staves X Ultra')], 'Counters' => ['static' => 2]])
            ->seed('myField', 'Staves X Ultra', properties: ['Counters' => ['linkedToWeapon' => GaScenario::id('Training Sword')]]);
    }
}

gaStaves('staves-x-ultra-lorraine-bonus-linked-sword-gets-plus-one-power-per-static-counter')
    ->observe()->expectPower('p1Field-1', 3, 'Training Sword\'s 1 power plus its two static counters')
    ->tested('Staves X Ultra')
    ->covers(['lorraine-bonus', 'weapon-link', 'stat-change', 'counter'], '[Lorraine Bonus] Linked weapon has **spellshroud** and gets +1[POWER] for each **static** counter on it.')
    ->register();

gaScenario('staves-x-ultra-without-the-link-a-sword-with-static-counters-keeps-its-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Training Sword', properties: ['Counters' => ['static' => 2]])
    ->observe()->expectPower('p1Field-1', 1, 'No Staves link: the static counters add nothing')
    ->tested('Staves X Ultra')
    ->covers(['lorraine-bonus', 'weapon-link', 'condition'], '[Lorraine Bonus] Linked weapon has **spellshroud** and gets +1[POWER] for each **static** counter on it.')
    ->register();
