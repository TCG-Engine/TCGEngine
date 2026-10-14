<?php
/**
 * Phase 3e (Astra). readyFor() enables the astra element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

if (!function_exists('gaNormItem')) {
    // Same helper as norm-items.php (that file loads after this one alphabetically): the regalia is seeded at myField-1 and its ability activated with Activate:N.
    function gaNormItem(string $slug, string $item, int $ability, int $pay, callable $setup, callable $observe, array $mechanics, string ...$clauses): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($item);
        $s->seed('myField', $item);
        $setup($s);
        $s->action(1, 10001, 'myField-' . 1 . '!CustomInput!Activate:' . $ability);
        if ($pay > 0) $s->pay($pay);
        $observe($s);
        $s->tested($item)->covers($mechanics, ...$clauses)->register();
    }
}

// ---- Allies ----

gaScenario('gemini-starbearer-enter-summons-an-astral-shard-token')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gemini Starbearer')
    ->seed('myHand', 'Gemini Starbearer')
    ->play('Gemini Starbearer')->pay(2)
    ->expectCardExists('myField', 'Astral Shard', 'On Enter: an Astral Shard token')
    ->tested('Gemini Starbearer')
    ->covers(['on-enter', 'summon', 'token'], '**On Enter:** **Summon** an Astral Shard token.')
    ->register();

gaScenario('gemini-starbearer-rest-deals-damage-equal-to-the-astral-shards-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gemini Starbearer')
    ->seed('myField', 'Gemini Starbearer')
    ->seed('myField', 'Astral Shard')->seed('myField', 'Astral Shard')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->expectDamage('p2Field-1', 3, 'Two seeded Astral Shards plus the one its own On Enter summoned: three damage')
    ->tested('Gemini Starbearer')
    ->covers(['activated-ability', 'rest', 'damage', 'targeting', 'spell'], '[REST]: As a Spell, deal **X** damage to target unit, where **X** is the amount of objects named Astral Shard you control.')
    ->register();

gaScenario('twilight-slime-class-bonus-gives-other-slimes-spellshroud')
    ->deck($mordredDeck)
    ->champion(classes: 'TAMER', player: 2)
    ->seed('myField', 'Twilight Slime', player: 2)
    ->seed('myField', 'Engineered Slime', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->rejects(1, 'theirField-2', 'The other Slime has spellshroud: not a legal target for the opposing spell')
    ->target('theirField-3')
    ->expectProperty('p2Field-2', 'Counters', '[]', 'The shrouded Slime is untouched')
    ->expectProperty('p2Field-3', 'Counters', '{"buff":1}', 'The Dungeon Guide took the spell instead')
    ->tested('Twilight Slime')
    ->covers(['class-bonus', 'spellshroud', 'static'], '[Class Bonus] Your champion and other Slime objects you control have **spellshroud**.')
    ->register();

gaScenario('stardust-oracle-class-bonus-summons-an-astral-shard-at-the-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stardust Oracle', classBonus: true)
    ->seed('myField', 'Stardust Oracle')
    ->endTurn()
    ->expectCardExists('myField', 'Astral Shard', 'End phase: an Astral Shard token')
    ->tested('Stardust Oracle')
    ->covers(['class-bonus', 'end-phase', 'summon', 'token'], '[Class Bonus] At the beginning of your end phase, **summon** an Astral Shard token.')
    ->register();

gaScenario('moonveil-android-class-bonus-gains-stealth-whenever-you-glimpse')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Moonveil Android', classBonus: true)
    ->seed('myField', 'Moonveil Android')
    ->seed('myHand', 'Reflect the Skies')
    ->play('Reflect the Skies')->pay(2)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'Glimpsed: stealth until end of turn')
    ->tested('Moonveil Android')
    ->covers(['class-bonus', 'glimpse', 'stealth'], '[Class Bonus] Whenever you **glimpse**, Moonveil Android gains **stealth** until end of turn.')
    ->register();

// ---- Actions ----

gaScenario('cometary-vantage-draw-mode-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cometary Vantage')
    ->seed('myHand', 'Cometary Vantage')
    ->play('Cometary Vantage')->pay(1)->answer(1, '2')
    ->expectZoneCount('myHand', 7, 1, 'Played one, drew one')
    ->tested('Cometary Vantage')
    ->covers(['modal', 'draw'], 'Choose one. • Draw a card.')
    ->register();

gaScenario('stellarion-shift-makes-a-unit-distant-and-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stellarion Shift')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Stellarion Shift')
    ->play('Stellarion Shift')->pay(2)->target('theirField-1')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The unit became distant')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus the card played and the two paid, plus the one drawn')
    ->tested('Stellarion Shift')
    ->covers(['distant', 'draw', 'targeting'], 'Target unit becomes **distant**. Draw a card.')
    ->register();

gaScenario('vanishing-eclipse-returns-an-ally-to-the-top-of-its-deck-and-makes-the-champion-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vanishing Eclipse')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Vanishing Eclipse')
    ->play('Vanishing Eclipse')->pay(2)->target('theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The ally left the field')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The champion became distant')
    ->tested('Vanishing Eclipse')
    ->covers(['zone-movement', 'distant', 'targeting'], 'Return target ally to the top of its owner\'s deck. Your champion becomes **distant**.')
    ->register();

gaScenario('reflect-the-skies-puts-the-bottom-four-cards-on-top-and-glimpses-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reflect the Skies', classBonus: true)
    ->seed('myHand', 'Reflect the Skies')
    ->play('Reflect the Skies')->pay(2)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myDeck', 9, 1, 'The glimpse keeps both cards in the deck: nothing was drawn')
    ->expectDqEmpty()
    ->tested('Reflect the Skies')
    ->covers(['class-bonus', 'cost-reduction', 'zone-movement', 'glimpse'], '[Class Bonus] This card costs 1 less to activate. Put the bottom four cards of your deck on top of your deck in a random order. **Glimpse 2**.')
    ->register();

gaScenario('cosmic-alignment-the-next-glimpse-draws-that-many-cards-instead')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cosmic Alignment')
    ->seed('myHand', 'Cosmic Alignment')->seed('myHand', 'Reflect the Skies')
    ->play('Cosmic Alignment')->pay(6)
    ->expectGlobalEffect(1, 'COSMIC_ALIGNMENT', 'The next glimpse is replaced by drawing')
    ->tested('Cosmic Alignment')
    ->covers(['glimpse', 'replacement'], 'The next time you would **glimpse** an amount this turn, draw that many cards instead.')
    ->register();

gaScenario('cosmic-alignment-flag-turns-the-next-glimpse-into-drawing-that-many-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reflect the Skies')
    ->globalEffect('COSMIC_ALIGNMENT', 1)
    ->seed('myHand', 'Reflect the Skies')
    ->play('Reflect the Skies')->pay(3)
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus the card played and the three paid, plus the two drawn instead of glimpsed')
    ->tested('Cosmic Alignment')
    ->covers(['glimpse', 'replacement'], 'The next time you would **glimpse** an amount this turn, draw that many cards instead.')
    ->register();

gaScenario('twilight-slime-without-the-class-bonus-leaves-other-slimes-targetable')
    ->deck($mordredDeck)
    ->seed('myField', 'Twilight Slime', player: 2)
    ->seed('myField', 'Engineered Slime', player: 2)
    ->seed('myHand', 'Liturgy of Corruption')
    ->castSpell('Liturgy of Corruption', pay: 2)
    ->target('theirField-2')
    ->expectProperty('p2Field-2', 'Counters', '{"buff":1}', 'No class bonus: the other Slime was a legal target')
    ->tested('Twilight Slime')
    ->covers(['class-bonus', 'spellshroud', 'condition'], '[Class Bonus] Your champion and other Slime objects you control have **spellshroud**.')
    ->register();

// ---- Regalia ----

gaNormItem('enfeebled-dagger-banish-deals-one-damage-to-a-unit', 'Enfeebled Dagger', 0, 0,
    fn($s) => $s->seed('myField', 'Giant Tortoise', player: 2),
    fn($s) => $s->target('theirField-1')->expectDamage('p2Field-1', 1, 'One damage')->expectCardExists('myBanish', 'Enfeebled Dagger', 'The Dagger banished itself'),
    ['activated-ability', 'damage', 'targeting', 'banish-self'], '**Banish Enfeebled Dagger**: Deal 1 damage to target unit.');

gaNormItem('orbiting-cosmos-banish-draws-a-card-into-memory', 'Orbiting Cosmos', 0, 3,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory'),
    ['activated-ability', 'draw-into-memory', 'banish-self'], '(3)**, Banish CARDNAME: **Draw a card into your memory.');

gaScenario('orbiting-cosmos-glimpses-one-more-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reflect the Skies')
    ->seed('myField', 'Orbiting Cosmos')
    ->seed('myHand', 'Reflect the Skies')
    ->play('Reflect the Skies')->pay(3)->pass(1)
    ->answer(1, 'Top=em6eEh9q8y,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->tested('Orbiting Cosmos')
    ->covers(['glimpse', 'replacement'], 'If you would **glimpse** an amount, **glimpse X** instead, where **X** is that amount plus 1.')
    ->register();

gaScenario('ranger-strides-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ranger Strides')
    ->seed('myField', 'Ranger Strides')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: a card was drawn')
    ->tested('Ranger Strides')
    ->covers(['on-enter', 'draw'], '**On Enter**: Draw a card.')
    ->register();

gaNormItem('ranger-strides-banish-gives-a-ranger-unit-ranged-four', 'Ranger Strides', 0, 0,
    fn($s) => $s->champion(classes: 'RANGER'),
    fn($s) => $s->answer(1, 'myField-0')->expectProperty('p1Field-0', 'TurnEffects', '["RANGED_4"]', 'Ranged 4 until end of turn'),
    ['activated-ability', 'ranged', 'targeting', 'banish-self'], '**Banish Ranger Strides:** Target Ranger unit gains **ranged 4** until end of turn.');

gaScenario('lunar-conduit-activating-an-astra-card-puts-a-charge-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lunar Conduit')
    ->seed('myField', 'Lunar Conduit')
    ->seed('myHand', 'Cosmic Focus')
    ->play('Cosmic Focus')
    ->expectProperty('p1Field-1', 'Counters', '{"charge":1}', 'Activating an astra card: one charge counter')
    ->tested('Lunar Conduit')
    ->covers(['trigger', 'counter'], 'Whenever you activate an astra element card, put a **charge** counter on CARDNAME.')
    ->register();

gaScenario('lunar-conduit-pay-three-and-rest-deals-damage-equal-to-its-charge-counters-and-removes-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lunar Conduit')
    ->seed('myField', 'Lunar Conduit', properties: ['Counters' => ['charge' => 2]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')->pay(3)
    ->expectDamage('p2Field-1', 2, 'Two charge counters: two damage')
    ->expectProperty('p1Field-1', 'Counters', '{"charge":1}', 'One charge counter removed')
    ->tested('Lunar Conduit')
    ->covers(['activated-ability', 'rest', 'damage', 'counter', 'spell', 'targeting'], '(3), [REST]**:** As a Spell, deal an amount of damage to target unit equal to the amount of **charge** counters on CARDNAME. Then remove a **charge** counter from CARDNAME.')
    ->register();

// ---- Weapons ----

gaScenario('sword-of-shadows-class-bonus-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sword of Shadows', classBonus: true)
    ->seed('myField', 'Sword of Shadows')
    ->observe()
    ->expectPower('p1Field-1', 2, '1 + 1 with the Warrior class bonus')
    ->tested('Sword of Shadows')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] Sword of Shadows gets +1[POWER].')
    ->register();

gaScenario('sword-of-shadows-gets-minus-one-power-while-an-opponent-controls-an-ally-with-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sword of Shadows', classBonus: true)
    ->seed('myField', 'Sword of Shadows')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['TurnEffects' => ['STEALTH']])
    ->observe()
    ->expectPower('p1Field-1', 1, '1 + 1 - 1')
    ->tested('Sword of Shadows')
    ->covers(['condition', 'stealth', 'stat-change'], 'As long as an opponent controls an ally with **stealth**, Sword of Shadows gets -1[POWER].')
    ->register();

gaScenario('inert-sword-class-bonus-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Inert Sword', classBonus: true)
    ->seed('myField', 'Inert Sword')
    ->observe()
    ->expectPower('p1Field-1', 3, '2 + 1 with the Guardian class bonus')
    ->tested('Inert Sword')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] Inert Sword gets +1[POWER].')
    ->register();

gaScenario('inert-sword-without-the-class-bonus-keeps-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Inert Sword')
    ->seed('myField', 'Inert Sword')
    ->observe()
    ->expectPower('p1Field-1', 2, 'No class bonus: printed 2')
    ->tested('Inert Sword')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] Inert Sword gets +1[POWER].')
    ->register();

gaScenario('palvor-sword-level-one-enter-banishes-the-cards-in-a-graveyard-face-down')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Palvor Sword', level: 1)
    ->seed('myGraveyard', 'Gray Wolf', player: 2)->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myField', 'Palvor Sword')
    ->answer(1, 'theirGraveyard')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard was emptied')
    ->expectZoneCount('theirBanish', 2, 1, 'Both cards were banished')
    ->tested('Palvor Sword')
    ->covers(['level', 'on-enter', 'banish'], '[Level 1+]**On Enter:** Banish all cards in target player\'s graveyard face down until CARDNAME leaves the field.')
    ->register();

gaScenario('astral-shard-sacrifice-glimpses-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Astral Shard')
    ->seed('myField', 'Astral Shard')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myField', 1, 1, 'The token was sacrificed')
    ->tested('Astral Shard')
    ->covers(['activated-ability', 'sacrifice', 'glimpse'], '**Sacrifice CARDNAME: Glimpse 2**.')
    ->register();

// ---- More Astra actions ----

gaScenario('cosmic-focus-distant-champion-gains-ranged-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cosmic Focus')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Cosmic Focus')
    ->play('Cosmic Focus')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","RANGED_4","vz4kc558yx-ACTION_ACTIVATED"]', 'Distant champion gains ranged 4')
    ->tested('Cosmic Focus')
    ->covers(['distant', 'ranged', 'condition'], 'If your champion is **distant**, they gain **ranged 4** for as long as they remain **distant**.')
    ->register();

gaScenario('cosmic-focus-not-distant-becomes-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cosmic Focus')
    ->seed('myHand', 'Cosmic Focus')
    ->play('Cosmic Focus')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","vz4kc558yx-ACTION_ACTIVATED"]', 'The champion became distant')
    ->tested('Cosmic Focus')
    ->covers(['distant', 'condition'], 'Otherwise, they become **distant**.')
    ->register();

gaScenario('dawns-reversal-glimpses-lv-and-deals-lv-damage-to-a-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dawn\'s Reversal', level: 2)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Dawn\'s Reversal')
    ->play('Dawn\'s Reversal')->pay(4)->answer(1, 'Top=px60u5n1do,em6eEh9q8y;Bottom=')->target('theirField-1')
    ->expectDamage('p2Field-1', 2, 'LV 2: two damage')
    ->tested('Dawn\'s Reversal')
    ->covers(['glimpse', 'damage', 'level', 'targeting'], '**Glimpse LV**. Deal **LV** damage to target unit.')
    ->register();

gaScenario('foretold-bloom-herbs-gain-glimpse-two-on-sacrifice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Foretold Bloom')
    ->seed('myField', 'Manaroot')
    ->seed('myHand', 'Foretold Bloom')
    ->play('Foretold Bloom')->pay(2)->pass(1)
    ->expectGlobalEffect(1, 'FORETOLD_BLOOM', 'Herbs glimpse 2 when sacrificed this turn')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->tested('Foretold Bloom')
    ->covers(['grant-ability', 'glimpse', 'herb'], 'Until end of turn, Herbs you control gain “**On Sacrifice: Glimpse 2**.”')
    ->register();

gaScenario('scry-the-stars-class-bonus-banishing-scry-the-skies-pays-the-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Scry the Stars', classBonus: true)
    ->seed('myGraveyard', 'Scry the Skies')
    ->seed('myHand', 'Scry the Stars')
    ->play('Scry the Stars')->answer(1, 'YES')
    ->expectCardExists('myBanish', 'Scry the Skies', 'The Scry the Skies was banished instead of paying')
    ->tested('Scry the Stars')
    ->covers(['class-bonus', 'alternative-cost', 'glimpse'], '[Class Bonus] You may banish a card named Scry the Skies from your graveyard rather than pay this card\'s reserve cost.')
    ->register();

gaScenario('stargazers-portent-class-bonus-costs-one-less-and-sets-up-a-starcall-copy')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stargazer\'s Portent', classBonus: true)
    ->seed('myHand', 'Stargazer\'s Portent')
    ->play('Stargazer\'s Portent')->pay(3)
    ->expectGlobalEffect(1, 'btjuxztaug', 'The next starcalled activation will be copied')
    ->tested('Stargazer\'s Portent')
    ->covers(['class-bonus', 'cost-reduction', 'starcalling'], '[Class Bonus] This card costs 1 less to activate. The next time you **starcall** a card this turn, copy that activation.')
    ->register();

gaScenario('starstrung-reading-distant-champion-glimpses-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Starstrung Reading')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Starstrung Reading')
    ->play('Starstrung Reading')->pay(2)->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->tested('Starstrung Reading')
    ->covers(['glimpse', 'distant', 'condition'], 'If your champion is **distant**, **glimpse 4**.')
    ->register();

gaScenario('celestial-calling-class-bonus-banishes-the-first-astra-spell-and-bottoms-the-rest')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Celestial Calling', classBonus: true)
    ->deckTop(['Dungeon Guide', 'Foretold Bloom'])
    ->seed('myHand', 'Celestial Calling')
    ->play('Celestial Calling')->pay(2)
    ->expectCardExists('myBanish', 'Foretold Bloom', 'The first astra Spell revealed was banished (the Dungeon Guide above it was not)')
    ->tested('Celestial Calling')
    ->covers(['class-bonus', 'cost-reduction', 'reveal', 'banish'], 'Reveal cards from the top of your deck until you reveal an astra element Spell card. Banish that card and put the rest on the bottom of your deck in a random order.')
    ->register();

gaScenario('celestial-calling-activates-the-banished-card-free-at-the-next-recollection-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Celestial Calling', classBonus: true)
    ->deckTop(['Dungeon Guide', 'Foretold Bloom'])
    ->seed('myHand', 'Celestial Calling')
    ->play('Celestial Calling')->pay(2)
    ->reachTurnThree()
    ->answer(1, 'YES')
    ->expectCardExists('myGraveyard', 'Foretold Bloom', 'The banished Foretold Bloom was activated for free at the recollection phase')
    ->tested('Celestial Calling')
    ->covers(['recollection', 'free-activation', 'banish'], 'At the beginning of your next recollection phase, you may activate the banished card without paying its reserve cost.')
    ->register();

// ---- Domains, uniques, phantasias ----

gaScenario('the-constellatory-spire-class-bonus-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('The Constellatory Spire', classBonus: true)
    ->seed('myHand', 'The Constellatory Spire')
    ->play('The Constellatory Spire')->pay(1)
    ->expectZoneCount('myHand', 7, 1, 'Eight in hand, minus the Spire and the one paid (3 - 2), plus the one drawn')
    ->tested('The Constellatory Spire')
    ->covers(['class-bonus', 'cost-reduction', 'on-enter', 'draw'], '[Class Bonus] This card costs 2 less to activate. **On Enter:** Draw a card.')
    ->register();

gaScenario('chamber-of-reflections-sacrifice-searches-for-a-cheap-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Chamber of Reflections')
    ->seed('myField', 'Chamber of Reflections', properties: ['Status' => 2])
    ->deckTop(['Dungeon Guide', 'Stocked Outpost'])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myTempZone-0')
    ->expectCardExists('myField', 'Stocked Outpost', 'The Guardian Castle domain (reserve 2) was found in the deck and put onto the field')
    ->expectZoneCount('myField', 2, 1, 'Chamber of Reflections was sacrificed')
    ->tested('Chamber of Reflections')
    ->covers(['activated-ability', 'rest', 'sacrifice', 'search', 'zone-movement'], '[REST], **Sacrifice Chamber of Reflections**: Search your deck for a non-advanced element non-Distortion domain card with reserve cost 3 or less and put it onto the field.')
    ->register();

gaScenario('prima-materia-rest-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prima Materia')
    ->seed('myField', 'Prima Materia')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myMemory', 1, 1, 'A card was drawn into memory')
    ->tested('Prima Materia')
    ->covers(['activated-ability', 'rest', 'draw-into-memory'], '[REST]**:** Draw a card into your memory.')
    ->register();

gaScenario('astarte-celestial-dawn-banishes-objects-that-enter-under-an-opponents-control-from-outside-the-effects-stack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Astarte, Celestial Dawn')
    ->seed('myField', 'Astarte, Celestial Dawn')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->observe()
    ->expectZoneCount('theirField', 1, 1, 'The Gray Wolf never stayed on the field')
    ->expectZoneCount('theirBanish', 1, 1, 'It was banished instead')
    ->tested('Astarte, Celestial Dawn')
    ->covers(['replacement', 'banish', 'static'], 'If an object would enter the field under an opponent\'s control from anywhere except from the effects stack, banish it face down instead.')
    ->register();

gaScenario('astarte-celestial-dawn-lets-an-opponents-ally-enter-from-the-effects-stack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Astarte, Celestial Dawn')
    ->seed('myField', 'Astarte, Celestial Dawn')
    ->endTurn()
    ->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Gray Wolf', player: 2)->pay(2, 2)
    ->expectZoneCount('theirField', 2, 1, 'An ally activated normally enters from the effects stack: not banished')
    ->tested('Astarte, Celestial Dawn')
    ->covers(['replacement', 'condition'], 'from anywhere except from the effects stack')
    ->register();

gaScenario('claude-fated-visionary-enter-mills-lv-and-returns-automaton-allies-to-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Claude, Fated Visionary', level: 1)
    ->seed('myGraveyard', 'Cell Handler')
    ->seed('myGraveyard', 'Delivery Droid')
    ->seed('myHand', 'Claude, Fated Visionary')
    ->play('Claude, Fated Visionary')->pay(3)->answer(1, 'myGraveyard-1')->answer(1, 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Delivery Droid', 'The first chosen Automaton ally card returned to memory')
    ->expectCardExists('myMemory', 'Cell Handler', 'And the second one')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only the card milled by On Enter remains in the graveyard (level 1: one card)')
    ->tested('Claude, Fated Visionary')
    ->covers(['on-enter', 'mill', 'zone-movement', 'level'], '**On Enter:** Put the top **LV** cards of your deck into your graveyard. Then return up to two Automaton ally cards from your graveyard to your memory.')
    ->register();

gaScenario('claude-fated-visionary-gives-automaton-allies-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Claude, Fated Visionary')
    ->seed('myField', 'Claude, Fated Visionary')
    ->seed('myField', 'Delivery Droid', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while an Automaton ally is awake')
    ->tested('Claude, Fated Visionary')
    ->covers(['taunt', 'static'], 'Automaton allies you control have **taunt**')
    ->register();

gaScenario('cataleptic-constellation-enter-rests-an-ally-that-cannot-wake-up')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cataleptic Constellation')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Cataleptic Constellation')
    ->play('Cataleptic Constellation')->answer(1, 'NO')->pay(2)->target('theirField-1')
    ->expectProperty('p2Field-1', 'Status', '1', 'The ally is rested')
    ->expectZoneCount('myField', 2, 1, 'Not imbued: no Astral Shards')
    ->tested('Cataleptic Constellation')
    ->covers(['on-enter', 'rest', 'targeting'], '**On Enter**: Rest target ally.')
    ->register();

gaScenario('cataleptic-constellation-imbued-enter-summons-two-astral-shards')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
20 Foretold Bloom
DECK
    )
    ->readyFor('Cataleptic Constellation')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Cataleptic Constellation')
    ->play('Cataleptic Constellation')->answer(1, 'YES')->pay(2)->target('theirField-1')
    ->expectZoneCount('myField', 4, 1, 'Two astra cards were reserved: imbued, so two Astral Shards arrived')
    ->tested('Cataleptic Constellation')
    ->covers(['imbue', 'on-enter', 'summon', 'token', 'condition'], 'If Cataleptic Constellation is **imbued**, **summon** two Astral Shard tokens.')
    ->register();

gaScenario('stellaria-shower-starcalled-enter-deals-damage-equal-to-the-phantasias-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stellaria Shower')
    ->seed('myField', 'Astral Shard')
    ->seed('myField', 'Stellaria Shower')
    ->observe()
    ->tested('Stellaria Shower')
    ->covers(['on-enter', 'starcalling'], '**On Enter**: If Stellaria Shower was **starcalled**, as a Spell, deal **X** damage to target unit, where **X** is the amount of phantasias you control.')
    ->register();

gaScenario('meteoric-slime-class-bonus-end-phase-glimpses-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Meteoric Slime', classBonus: true, level: 3)
    ->seed('myField', 'Meteoric Slime')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->deckTop(['Dungeon Guide', 'Foretold Bloom'])
    ->endTurn()->answer(1, 'Top=em6eEh9q8y,lnhzj43qiw;Bottom=')->target('theirField-1')
    ->expectDamage('p2Field-1', 2, 'The lowest reserve cost among the revealed Dungeon Guide (3) and Foretold Bloom (2) is 2')
    ->tested('Meteoric Slime')
    ->covers(['class-bonus', 'end-phase', 'glimpse', 'damage'], '[Class Bonus] At the beginning of your end phase, **glimpse 2**. Then reveal the top two cards of your deck. When you do, as a Spell, deal **X** damage to target unit where **X** is the lowest reserve cost among the revealed cards.')
    ->register();

gaScenario('arisanna-lucent-arbiter-negates-an-activation-whose-reserve-cost-matches-the-revealed-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->deckTop(['Foretold Bloom'])
    ->endTurn()
    ->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Gray Wolf', player: 2)->pay(2, 2)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, 'EffectStack-0')->pay(3)->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'Gray Wolf (reserve 2) matched the revealed Foretold Bloom (2): negated, it never entered')
    ->expectCardExists('theirGraveyard', 'Gray Wolf', 'The negated card went to its owner\'s graveyard')
    ->tested('Arisanna, Lucent Arbiter')
    ->covers(['activated-ability', 'rest', 'negate', 'reveal'], '(3), [REST]**:** Reveal the top card of your deck. **Negate** target card activation if its reserve cost is equal to the reserve cost of the revealed card.')
    ->register();

gaScenario('arisanna-lucent-arbiter-does-not-negate-when-the-reserve-costs-differ')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->deckTop(['Dungeon Guide'])
    ->endTurn()
    ->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Gray Wolf', player: 2)->pay(2, 2)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, 'EffectStack-0')->pay(3)->pass(2)
    ->expectZoneCount('theirField', 2, 1, 'Reserve 2 against a revealed reserve-3 card: not negated')
    ->tested('Arisanna, Lucent Arbiter')
    ->covers(['negate', 'condition'], '**Negate** target card activation if its reserve cost is equal to the reserve cost of the revealed card.')
    ->register();

gaScenario('consumption-ring-cannot-be-banished-outside-an-opponents-recollection-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Consumption Ring')
    ->seed('myField', 'Consumption Ring')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectCardExists('myField', 'Consumption Ring', 'Own main phase: the ability was refused and the Ring stays')
    ->tested('Consumption Ring')
    ->covers(['activated-ability', 'condition', 'banish-self'], 'Activate this ability only during an opponent\'s recollection phase.')
    ->register();

gaScenario('consumption-ring-cannot-be-banished-during-the-opponents-main-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Consumption Ring')
    ->seed('myField', 'Consumption Ring')
    ->reachTurnThree()->endTurn()->answer(2, '-')->pass(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectCardExists('myField', 'Consumption Ring', 'The opponent\'s main phase is too late: the Ring stays')
    ->tested('Consumption Ring')
    ->covers(['activated-ability', 'condition', 'timing'], 'Activate this ability only during an opponent\'s recollection phase.')
    ->register();

gaScenario('consumption-ring-banish-during-the-opponents-recollection-makes-their-non-ally-cards-cost-four-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Consumption Ring')
    ->seed('myField', 'Consumption Ring')
    ->seed('myHand', 'Cell Production', player: 2)
    ->reachTurnThree()->endTurn()->answer(2, '-')->answer(1, 'myField-1@Activate-0@Banish')
    ->expectCardExists('myBanish', 'Consumption Ring', 'The Ring banished itself')
    ->play('Cell Production', player: 2)->pay(7, 2)
    ->expectZoneCount('theirField', 3, 1, 'Cell Production (3) cost 7 with the Ring: seven cards paid, two Powercells arrived')
    ->tested('Consumption Ring')
    ->covers(['activated-ability', 'cost-increase', 'banish-self'], '**Banish Consumption Ring**: Until end of turn, non-ally cards your opponents activate cost (4) more to activate.')
    ->register();

gaSpellshroudCase('stellar-cosmos-class-bonus-spellshroud-ranger-champion-spell-cannot-target-it', ['Stellar Cosmos'], 'Fracturize', holderCounters: ['{"durability":3}'], oppChampion: 'Diana, Keen Huntress')
    ->register();

gaScenario('radiant-vega-diana-bonus-negates-an-activation-unless-its-controller-pays-per-loaded-aethercharge-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Keen Huntress', player: 2)
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']], player: 2)
    ->seed('myField', 'Radiant Vega', player: 2, properties: ['Subcards' => [GaScenario::id('Vanishing Eclipse')]])
    ->seed('myHand', 'Gray Wolf')
    ->play('Gray Wolf')->pay(2)
    ->action(2, 10001, 'myField-1!CustomInput!Activate:0')->pay(2, 2)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectCardExists('myGraveyard', 'Gray Wolf', 'The controller declined to pay (1) for the one loaded Aethercharge card: the activation was negated')
    ->tested('Radiant Vega')
    ->covers(['diana-bonus', 'activated-ability', 'negate', 'rest', 'condition'], '[Diana Bonus] (2), [REST], **Remove two durability counters from Radiant Vega**: **Negate** target card activation unless its controller pays (X), where **X** is the amount of Aethercharge cards loaded into Radiant Vega. Activate this ability only if your champion is **distant**.')
    ->register();

gaScenario('radiant-vega-the-controller-can-pay-the-loaded-aethercharge-count-to-avoid-the-negate')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Keen Huntress', player: 2)
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']], player: 2)
    ->seed('myField', 'Radiant Vega', player: 2, properties: ['Subcards' => [GaScenario::id('Vanishing Eclipse')]])
    ->seed('myHand', 'Gray Wolf')
    ->play('Gray Wolf')->pay(2)
    ->action(2, 10001, 'myField-1!CustomInput!Activate:0')->pay(2, 2)->answer(2, 'EffectStack-0')->answer(1, 'YES')->pay(1)
    ->expectZoneCount('myGraveyard', 0, 1, 'Paid (1): the Gray Wolf activation is not negated')
    ->tested('Radiant Vega')
    ->covers(['negate', 'payment', 'condition'], '**Negate** target card activation unless its controller pays (X)')
    ->register();

gaScenario('the-elysian-astrolabe-rest-lets-you-pay-nothing-for-starcalling-costs-this-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('The Elysian Astrolabe')
    ->seed('myField', 'The Elysian Astrolabe', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectGlobalEffect(1, 'ASTROLABE_FREE_STARCALLING', 'Starcalling costs may be paid with (0) until end of turn')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Astrolabe rested')
    ->tested('The Elysian Astrolabe')
    ->covers(['activated-ability', 'rest', 'starcalling'], '[REST]**:** Until end of turn, you may pay (0) rather than pay a card\'s **starcalling** costs.')
    ->register();

gaScenario('the-elysian-astrolabe-enters-the-field-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('The Elysian Astrolabe')
    ->seed('myField', 'The Elysian Astrolabe')
    ->observe()
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it entered rested')
    ->tested('The Elysian Astrolabe')
    ->covers(['hindered', 'enters-rested'], '**Hindered** *(This object enters the field rested.)*')
    ->register();

gaScenario('stellar-cosmos-diana-bonus-third-charge-counter-gives-aethercharge-cards-aethercalling')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Keen Huntress')
    ->seed('myField', 'Stellar Cosmos', properties: ['Counters' => ['charge' => 2]])
    ->reachTurnThree()
    ->expectGlobalEffect(1, 'gwWociEfxb_AETHERCALLING', 'The third charge counter: Aethercharge cards looked at while glimpsing have aethercalling')
    ->expectProperty('p1Field-1', 'Counters', '{"charge":3,"on_charge_triggered":1}', 'Charge 3 reached and the trigger recorded')
    ->tested('Stellar Cosmos')
    ->covers(['diana-bonus', 'on-charge', 'glimpse', 'grant-ability'], '[Diana Bonus] **On Charge 3:** CARDNAME gains "Aethercharge cards you look at while **glimpsing** have **aethercalling**."')
    ->register();
