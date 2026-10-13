<?php
/**
 * Phase 3b (Wind): phantasias and domains. Written on the 'quiet' deck; phantasia/domain cards that open priority windows between phases use the long
 * reachTurnThree route.
 */

// ---- Fertile Grounds ----

gaScenario('fertile-grounds-recollection-summons-a-token-copy-of-an-herb-item')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fertile Grounds')
    ->seed('myField', 'Fertile Grounds')
    ->seed('myField', 'Manaroot')
    ->reachTurnThree(fastCardInHand: true)
    ->expectZoneCount('myField', 4, 1, 'The champion, Fertile Grounds, the Manaroot and its token copy')
    ->tested('Fertile Grounds')
    ->covers(['recollection', 'token', 'summon'], 'At the beginning of your recollection phase, **summon** a token copy of an Herb item you control.')
    ->register();

gaScenario('fertile-grounds-without-an-herb-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fertile Grounds')
    ->seed('myField', 'Fertile Grounds')
    ->reachTurnThree()
    ->expectZoneCount('myField', 2, 1, 'No Herb item: nothing summoned')
    ->tested('Fertile Grounds')
    ->covers(['recollection', 'token', 'condition'], 'a token copy of an Herb item you control')
    ->register();

// ---- Maiden of Glimmer's Dusk ----

gaScenario('maiden-of-glimmers-dusk-class-bonus-costs-one-less-for-each-of-up-to-two-phantasias')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maiden of Glimmer\'s Dusk', classBonus: true)
    ->seed('myField', 'Bygone Days')->seed('myField', 'Where Futures Stir')
    ->seed('myHand', 'Maiden of Glimmer\'s Dusk')
    ->play('Maiden of Glimmer\'s Dusk')->pay(1)
    ->expectCardExists('myField', 'Maiden of Glimmer\'s Dusk', 'Two phantasias: the Maiden cost 3 - 2 = 1')
    ->tested('Maiden of Glimmer\'s Dusk')
    ->covers(['class-bonus', 'cost-reduction', 'scaling'], '[Class Bonus] This card costs 1 less to activate for each of up to two phantasias you control.')
    ->register();

gaScenario('maiden-of-glimmers-dusk-without-phantasias-costs-the-printed-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maiden of Glimmer\'s Dusk', classBonus: true)
    ->seed('myHand', 'Maiden of Glimmer\'s Dusk')
    ->play('Maiden of Glimmer\'s Dusk')->pay(1)
    ->expectZoneCount('myField', 1, 1, 'One payment is not enough: the Maiden has not entered')
    ->tested('Maiden of Glimmer\'s Dusk')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], 'for each of up to two phantasias you control')
    ->register();

// ---- Mark of Fervor / Message in Shadows (links) ----

gaScenario('mark-of-fervor-links-to-an-ally-for-plus-one-power-plus-one-life-and-vigor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mark of Fervor')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'Mark of Fervor')
    ->play('Mark of Fervor')->answer(1, 'myField-1')->pay(2)
    ->expectPower('p1Field-1', 2, 'Linked ally: 1 + 1 power')
    ->expectLife('p1Field-1', 4, 'Linked ally: 3 + 1 life')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Linked ally has vigor: it woke at the end phase')
    ->tested('Mark of Fervor')
    ->covers(['ally-link', 'stat-change', 'vigor'], '**Ally Link**', 'Linked ally gets +1 [POWER] and +1 [LIFE], and has **vigor**.')
    ->register();

gaScenario('message-in-shadows-on-enter-glimpses-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Message in Shadows')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Message in Shadows')
    ->play('Message in Shadows')->answer(1, 'myField-1')->pay(2)->answer(1, 'Top=px60u5n1do;Bottom=em6eEh9q8y')
    ->expectProperty('p1Deck-0', 'CardID', 'px60u5n1do', 'Glimpse 2: the Shopkeep stays on top')
    ->tested('Message in Shadows')
    ->covers(['ally-link', 'on-enter', 'glimpse'], '**Ally Link**', '**On Enter: Glimpse 2**.')
    ->register();

// ---- Razorgale Calling ----

gaScenario('razorgale-calling-class-bonus-costs-two-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Razorgale Calling', classBonus: true)
    ->seed('myHand', 'Razorgale Calling')
    ->play('Razorgale Calling')->pay(2)
    ->expectCardExists('myField', 'Razorgale Calling', 'Class Bonus: it cost 4 - 2 = 2')
    ->tested('Razorgale Calling')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

gaScenario('razorgale-calling-deals-one-damage-to-a-champion-whenever-you-activate-a-wind-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Razorgale Calling')
    ->seed('myField', 'Razorgale Calling')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: '', target: 'myField-2')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 1, 'A wind card was activated: 1 damage to the chosen champion')
    ->tested('Razorgale Calling')
    ->covers(['trigger', 'damage', 'targeting'], 'Whenever you activate a wind element card, deal 1 damage to target champion.')
    ->register();

// ---- Slimecall Cyclone ----

gaScenario('slimecall-cyclone-class-bonus-recollection-summons-a-baby-slime')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Slimecall Cyclone', classBonus: true)
    ->seed('myField', 'Slimecall Cyclone')
    ->reachTurnThree()
    ->expectCardExists('myField', 'Baby Slime', 'A Baby Slime token was summoned')
    ->tested('Slimecall Cyclone')
    ->covers(['class-bonus', 'recollection', 'token', 'summon'], '[Class Bonus] At the beginning of your recollection phase, **summon** a Baby Slime token.')
    ->register();

gaScenario('slimecall-cyclone-without-the-class-bonus-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Slimecall Cyclone')
    ->seed('myField', 'Slimecall Cyclone')
    ->reachTurnThree()
    ->expectZoneCount('myField', 2, 1, 'No class bonus: no token')
    ->tested('Slimecall Cyclone')
    ->covers(['class-bonus', 'recollection', 'condition'], '[Class Bonus] At the beginning of your recollection phase')
    ->register();

// ---- Winbless Hurricane Farm ----

gaScenario('winbless-hurricane-farm-recollection-reveals-three-wind-cards-to-summon-a-powercell')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Winbless Hurricane Farm')
    ->seed('myField', 'Winbless Hurricane Farm')
    ->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Windrider Mage')
    ->reachTurnThree()->answer(1, 'YES')
    ->expectCardExists('myField', 'Powercell', 'Three wind cards revealed: a Powercell token was summoned')
    ->tested('Winbless Hurricane Farm')
    ->covers(['recollection', 'reveal', 'token', 'summon'], 'At the beginning of your recollection phase, you may reveal three wind element cards from your memory. If you do, **summon** a Powercell token.')
    ->register();

// ---- Baidi, Oathsworn Palace ----

gaScenario('baidi-oathsworn-palace-gives-distant-ranger-units-ranged-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Baidi, Oathsworn Palace', player: 2)
    ->seed('myField', 'Baidi, Oathsworn Palace', player: 2)
    ->seed('myField', 'Yunzhou Cavalry', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-2', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 3, 'Ranged 1 while distant: 2 + 1 = 3 damage')
    ->tested('Baidi, Oathsworn Palace')
    ->covers(['ranged', 'static-ability'], 'Ranger units you control have **ranged 1**.')
    ->register();

gaScenario('winbless-hurricane-farm-declined-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Winbless Hurricane Farm')
    ->seed('myField', 'Winbless Hurricane Farm')
    ->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Windrider Mage')
    ->reachTurnThree()->answer(1, 'NO')
    ->expectZoneCount('myField', 2, 1, 'Declined: no Powercell')
    ->tested('Winbless Hurricane Farm')
    ->covers(['recollection', 'reveal', 'optional'], 'you may reveal three wind element cards from your memory')
    ->register();

gaScenario('winbless-hurricane-farm-with-only-two-wind-memory-cards-is-not-offered')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Winbless Hurricane Farm')
    ->seed('myField', 'Winbless Hurricane Farm')
    ->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Windrider Mage')->seed('myMemory', 'Dungeon Guide')
    ->reachTurnThree()
    ->expectDqEmpty()
    ->expectZoneCount('myField', 2, 1, 'Only two wind cards in memory: no offer, no Powercell')
    ->tested('Winbless Hurricane Farm')
    ->covers(['recollection', 'reveal', 'condition'], 'reveal three wind element cards from your memory')
    ->register();

// ---- Gearstride Academy ----

gaScenario('gearstride-academy-gives-wind-ally-cards-imbue-two-and-an-imbued-ally-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gearstride Academy')
    ->seed('myField', 'Gearstride Academy')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->play('Windrider Mage')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-2', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN","lxnq80yu75"]', 'The Mage was offered imbue 2, reserved two wind cards and is imbued')
    ->expectPower('p1Field-2', 2, 'Imbued by the Academy: On Enter gave it +1 power (1 + 1)')
    ->tested('Gearstride Academy')
    ->covers(['imbue', 'static-ability', 'on-enter'], 'Wind ally cards you activate have **imbue 2**.', 'Wind allies you control have “**On Enter:** If this ally is **imbued**, it gets +1 [POWER] until end of turn.”')
    ->register();

// ---- Maiden of Shrouded Fog ----

gaScenario('maiden-of-shrouded-fog-class-bonus-spellshroud-cannot-be-targeted-by-spells')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maiden of Shrouded Fog', classBonus: true)
    ->seed('myField', 'Maiden of Shrouded Fog')
    ->seed('myHand', 'Nascent Blast')
    ->play('Nascent Blast')->pay(3)
    ->rejects(1, 'myField-1', 'Spellshroud: the Maiden is not a legal target for a Spell')
    ->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'The Blast went to the opposing champion instead')
    ->tested('Maiden of Shrouded Fog')
    ->covers(['class-bonus', 'spellshroud'], '[Class Bonus] **Spellshroud**')
    ->register();

gaScenario('maiden-of-shrouded-fog-without-the-class-bonus-can-be-targeted-by-spells')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maiden of Shrouded Fog')
    ->seed('myField', 'Maiden of Shrouded Fog')
    ->seed('myHand', 'Nascent Blast')
    ->play('Nascent Blast')->pay(3)->answer(1, 'myField-1')
    ->expectCardExists('myGraveyard', 'Maiden of Shrouded Fog', 'No class bonus, no spellshroud: the Blast hit the 2 life Maiden and destroyed it')
    ->tested('Maiden of Shrouded Fog')
    ->covers(['class-bonus', 'spellshroud', 'condition'], '[Class Bonus] **Spellshroud**')
    ->register();

// ---- Stifling Gyre ----

gaScenario('stifling-gyre-on-enter-draws-into-memory-and-chooses-an-ally-name')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stifling Gyre')
    ->seed('myHand', 'Stifling Gyre')
    ->play('Stifling Gyre')->pay(3)->answer(1, 'em6eEh9q8y')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid cards plus the card drawn into memory by On Enter')
    ->tested('Stifling Gyre')
    ->covers(['on-enter', 'draw-into-memory', 'name-a-card'], 'As CARDNAME enters the field, choose an ally card name.', '**On Enter:** Draw a card into your memory.')
    ->register();
