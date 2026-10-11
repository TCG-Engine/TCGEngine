<?php
/**
 * Phase 3a (Fire): items, regalia, phantasias, domains and boons. Abilities that sacrifice or banish their source are taken
 * from the priority window that opens when player 1 passes (the offered choice is '<field slot>@Activate-<n>@<label>').
 */

// --- Items with sacrifice / banish abilities ---
gaScenario('bottled-forgelight-sacrifice-deals-two-damage-to-a-unit')
    ->standardDeck('Bottled Forgelight')
    ->readyFor('Bottled Forgelight')
    ->seed('myField', 'Bottled Forgelight')
    ->endTurn()->answer(1, 'myField-1@Activate-0@g616r0zadf')->target('theirField-0')
    ->tested('Bottled Forgelight')
    ->expectDamage('p2Field-0', 2, 'Sacrifice Bottled Forgelight: 2 damage to the target unit')
    ->expectProperty('p1Graveyard-0', 'CardID', 'g616r0zadf', 'The item was sacrificed')
    ->covers(['activated-ability', 'damage', 'sacrifice'], 'Sacrifice CARDNAME: Deal 2 damage to target unit.')
    ->register();

gaScenario('explosive-rune-banish-deals-one-damage-to-an-ally')
    ->standardDeck('Explosive Rune')
    ->readyFor('Explosive Rune')
    ->seed('myField', 'Explosive Rune')
    ->seed('theirField', 'Dungeon Guide')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Banish')->target('theirField-1')
    ->tested('Explosive Rune')
    ->expectDamage('p2Field-1', 1, 'Banish Explosive Rune: 1 damage to the target ally (it is not attacking)')
    ->expectZoneCount('myBanish', 1, 1, 'The Rune banished itself as the cost')
    ->covers(['activated-ability', 'damage', 'banish-self', 'condition'], 'Banish CARDNAME: Deal 1 damage to target ally. Class Bonus: If that ally is attacking, deal 2 damage to it instead.')
    ->register();

gaScenario('volnia-sacrifice-deals-one-damage-to-a-unit')
    ->standardDeck('Volnia')
    ->readyFor('Volnia')
    ->seed('myField', 'Volnia')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Sacrifice')->target('theirField-0')
    ->tested('Volnia')
    ->expectDamage('p2Field-0', 1, 'Sacrifice Volnia: 1 damage to the target unit')
    ->covers(['activated-ability', 'damage', 'sacrifice'], 'Sacrifice CARDNAME: As a Spell, deal 1 damage to target unit.')
    ->register();

// --- Items / phantasias that enter the field ---
gaScenario('coiled-fatestone-enter-discards-two-cards-at-random')
    ->standardDeck('Coiled Fatestone')
    ->readyFor('Coiled Fatestone')
    ->seed('myHand', 'Coiled Fatestone')
    ->castSpell('Coiled Fatestone', pay: 2)
    ->expectZoneCount('myGraveyard', 2, 1, 'On Enter: two cards were discarded at random')
    ->expectCardExists('myField', 'Coiled Fatestone', 'Coiled Fatestone is on the field')
    ->covers(['on-enter', 'discard', 'random'], 'On Enter: Discard two cards at random. For each fire element card discarded this way, draw a card.')
    ->register();

gaScenario('combustible-potion-played-for-reserve-enters-without-its-brew-bonus')
    ->standardDeck('Combustible Potion')
    ->readyFor('Combustible Potion')
    ->seed('myHand', 'Combustible Potion')
    ->castSpell('Combustible Potion', pay: 4)
    ->expectCardExists('myField', 'Combustible Potion', 'Combustible Potion is on the field')
    ->expectZoneCount('myHand', 3, 1, 'Not brewed: no cards drawn (8 - 1 - 4 = 3)')
    ->covers(['brew', 'on-enter', 'condition'], 'On Enter: If CARDNAME was brewed, draw two cards and discard a card.')
    ->register();

gaScenario('explosive-concoction-enters-the-field-rested')
    ->standardDeck('Explosive Concoction')
    ->readyFor('Explosive Concoction')
    ->seed('myHand', 'Explosive Concoction')
    ->castSpell('Explosive Concoction', pay: 7)
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it enters the field rested')
    ->covers(['keyword', 'status', 'brew'], 'Hindered (This object enters the field rested.)')
    ->register();

gaScenario('flametech-shield-links-to-an-ally')
    ->standardDeck('FlameTech Shield')
    ->readyFor('FlameTech Shield')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'FlameTech Shield')
    ->play('FlameTech Shield')->target('myField-1')->pay(3)->answer(1, 'myHand-0')
    ->expectProperty('p1Field-2', 'Counters', '{"linkedToAlly":"em6eEh9q8y"}', 'FlameTech Shield entered linked to the Dungeon Guide')
    ->expectZoneCount('myGraveyard', 1, 1, 'On Enter: a card was discarded after the draw')
    ->covers(['link', 'on-enter', 'draw-discard'], 'Ally Link. On Enter: Draw a card, then discard a card.')
    ->register();

gaScenario('hot-cake-enters-the-field')
    ->standardDeck('Hot Cake')
    ->readyFor('Hot Cake')
    ->seed('myHand', 'Hot Cake')
    ->castSpell('Hot Cake', pay: 3)
    ->expectCardExists('myField', 'Hot Cake', 'Hot Cake is on the field')
    ->covers(['item'], 'Whenever an ally enters the field, you may sacrifice CARDNAME. If you do, that ally\'s next attack this turn gets +3 [POWER].')
    ->register();

gaScenario('aenean-guttering-flames-enter-draws-then-discards')
    ->standardDeck('Aenean Guttering Flames')
    ->readyFor('Aenean Guttering Flames', classBonus: true)
    ->seed('myHand', 'Aenean Guttering Flames')
    ->play('Aenean Guttering Flames')->pay(3)->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'On Enter: a card was discarded')
    ->expectCardExists('myField', 'Aenean Guttering Flames', 'The phantasia is on the field')
    ->covers(['on-enter', 'draw-discard'], 'On Enter: Draw a card, then discard a card.')
    ->register();

gaScenario('explosive-fractal-class-bonus-memory-four-deals-two-damage-to-a-champion')
    ->standardDeck('Explosive Fractal')
    ->readyFor('Explosive Fractal', classBonus: true)
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Explosive Fractal')
    ->castSpell('Explosive Fractal', pay: 3, target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, '[Class Bonus][Memory 4+] On Enter: 2 damage to the target champion')
    ->covers(['on-enter', 'damage', 'class-bonus', 'condition'], '[Class Bonus] [Memory 4+] On Enter: Deal 2 damage to target champion.')
    ->register();

gaScenario('explosive-fractal-with-fewer-than-four-memory-cards-does-nothing-on-enter')
    ->standardDeck('Explosive Fractal')
    ->readyFor('Explosive Fractal', classBonus: true)
    ->seed('myHand', 'Explosive Fractal')
    ->castSpell('Explosive Fractal', pay: 3)
    ->expectDamage('p2Field-0', 0, 'Only the 3 paid memory cards: below the threshold, no damage')
    ->expectCardExists('myField', 'Explosive Fractal', 'The phantasia is on the field')
    ->covers(['on-enter', 'condition', 'class-bonus'], '[Class Bonus] [Memory 4+] On Enter: Deal 2 damage to target champion.')
    ->register();

gaScenario('incendiary-fractal-class-bonus-enter-deals-four-damage-to-an-opposing-ally')
    ->standardDeck('Incendiary Fractal')
    ->readyFor('Incendiary Fractal', classBonus: true)
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Incendiary Fractal')
    ->castSpell('Incendiary Fractal', pay: 4, target: 'theirField-1')
    ->expectProperty('p2Graveyard-0', 'CardID', 'em6eEh9q8y', '[Class Bonus] 4 damage instead of 2 destroyed the 3-life Dungeon Guide')
    ->covers(['on-enter', 'damage', 'class-bonus'], 'On Enter: Deal 2 damage to target ally you don\'t control. Class Bonus: Deal 4 damage to that ally instead.')
    ->register();

gaScenario('incendiary-fractal-without-the-class-bonus-deals-two-damage')
    ->standardDeck('Incendiary Fractal')
    ->readyFor('Incendiary Fractal')
    ->seed('theirField', 'Dungeon Guide')
    ->seed('myHand', 'Incendiary Fractal')
    ->castSpell('Incendiary Fractal', pay: 4, target: 'theirField-1')
    ->expectDamage('p2Field-1', 2, 'No Class Bonus: 2 damage')
    ->covers(['on-enter', 'damage', 'class-bonus', 'condition'], 'On Enter: Deal 2 damage to target ally you don\'t control.')
    ->register();

gaScenario('ravenous-pyre-class-bonus-enter-draws-then-discards')
    ->standardDeck('Ravenous Pyre')
    ->readyFor('Ravenous Pyre', classBonus: true)
    ->seed('myHand', 'Ravenous Pyre')
    ->play('Ravenous Pyre')->pay(2)->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 1, 1, '[Class Bonus] On Enter: a card was discarded')
    ->covers(['on-enter', 'draw-discard', 'class-bonus'], '[Class Bonus] On Enter: Draw a card and discard a card.')
    ->register();

gaScenario('relic-of-dancing-embers-enters-the-field-rested')
    ->standardDeck('Relic of Dancing Embers')
    ->readyFor('Relic of Dancing Embers')
    ->seed('myHand', 'Relic of Dancing Embers')
    ->castSpell('Relic of Dancing Embers', pay: 2)
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it enters the field rested')
    ->covers(['keyword', 'status'], 'Hindered (This object entered the field rested.)')
    ->register();

gaScenario('fractal-of-sparks-class-bonus-rest-deals-one-damage-to-a-champion')
    ->standardDeck('Fractal of Sparks')
    ->readyFor('Fractal of Sparks', classBonus: true)
    ->seed('myField', 'Fractal of Sparks')
    ->endTurn()->answer(1, 'myField-1@Activate-0@Damage')->target('theirField-0')
    ->tested('Fractal of Sparks')
    ->expectDamage('p2Field-0', 1, '[Class Bonus] [REST]: 1 damage to the target champion')
    ->covers(['activated-ability', 'damage', 'class-bonus'], '[Class Bonus] [REST]: Deal 1 damage to target champion.')
    ->register();

gaScenario('firebloom-flourish-class-bonus-damages-the-opposing-champion-at-recollection')
    ->standardDeck('Firebloom Flourish')
    ->readyFor('Firebloom Flourish', classBonus: true)
    ->seed('myField', 'Firebloom Flourish')
    ->reachTurnThree()->target('theirField-0')
    ->tested('Firebloom Flourish')
    ->expectDamage('p2Field-0', 1, '[Class Bonus] At the beginning of your recollection phase: 1 damage to the opposing champion')
    ->covers(['trigger', 'damage', 'class-bonus'], '[Class Bonus] At the beginning of your recollection phase, deal 1 damage to target champion.')
    ->register();

// --- Domains and boons ---
gaScenario('leran-pastoral-hymns-end-phase-damages-a-champion-with-influence-eight')
    ->standardDeck('Leran, Pastoral Hymns')
    ->readyFor('Leran, Pastoral Hymns')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myField', 'Leran, Pastoral Hymns')
    ->endTurn()
    ->tested('Leran, Pastoral Hymns')
    ->expectDamage('p1Field-0', 1, 'Influence 8 (7 in hand + 1 in memory): X - 7 = 1 damage to that player\'s champion at their end phase')
    ->covers(['trigger', 'damage', 'influence'], 'At the beginning of each player\'s end phase, if that player\'s influence is eight or more, deal X-7 damage to that player\'s champion, where X is that player\'s influence.')
    ->register();

gaScenario('leran-pastoral-hymns-end-phase-with-influence-seven-does-nothing')
    ->standardDeck('Leran, Pastoral Hymns')
    ->readyFor('Leran, Pastoral Hymns')
    ->seed('myField', 'Leran, Pastoral Hymns')
    ->endTurn()
    ->tested('Leran, Pastoral Hymns')
    ->expectDamage('p1Field-0', 0, 'Influence 7: below the threshold, no damage')
    ->covers(['trigger', 'condition', 'influence'], 'At the beginning of each player\'s end phase, if that player\'s influence is eight or more, deal X-7 damage to that player\'s champion.')
    ->register();

gaScenario('lesser-boon-of-vritra-draws-and-damages-champions-with-influence-over-seven')
    ->standardDeck('Lesser Boon of Vritra')
    ->readyFor('Lesser Boon of Vritra')
    ->seed('myHand', 'Lesser Boon of Vritra')
    ->castSpell('Lesser Boon of Vritra', pay: 2)
    ->expectDamage('p1Field-0', 2, 'Your influence (6 in hand + 2 in memory = 8) is more than seven: 2 damage')
    ->expectDamage('p2Field-0', 2, 'The opponent\'s influence (8 after drawing) is more than seven: 2 damage')
    ->expectZoneCount('theirHand', 8, 1, 'Each player drew a card')
    ->covers(['draw-discard', 'damage', 'influence'], 'As you gain this boon, each player draws a card. Then deal 2 damage to each champion controlled by players with influence more than seven.')
    ->register();

gaScenario('lesser-boon-of-vritra-spares-a-champion-with-low-influence')
    ->standardDeck('Lesser Boon of Vritra')
    ->readyFor('Lesser Boon of Vritra')
    ->emptyZone('myHand', player: 2, destZone: 'myBanish')
    ->seed('myHand', 'Lesser Boon of Vritra')
    ->castSpell('Lesser Boon of Vritra', pay: 2)
    ->expectDamage('p2Field-0', 0, 'The opponent has one card in hand after drawing: influence 1, no damage')
    ->expectDamage('p1Field-0', 2, 'Your own influence is still over seven')
    ->covers(['damage', 'influence', 'condition'], 'Deal 2 damage to each champion controlled by players with influence more than seven.')
    ->register();
