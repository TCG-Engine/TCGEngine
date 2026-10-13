<?php
/**
 * Phase 3b (Wind): the champion-bonus cards (Alice, Diao Chan, Merlin, Jin, Kongming). Each fixture patches the starting champion to the named champion with a
 * Spirit of Wind under it (which enables the wind element); the bonus checks look at the champion's name / lineage.
 */

function gaBonus(string $slug, string $champion): GaScenario {
    return gaScenario($slug)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: $champion, lineage: ['Spirit of Wind']);
}

// ---- Schwartz Castler (Alice) ----
// Giant Tortoise (6 life) is the opposing unit with an even life stat; the champions have 15 life.

gaBonus('schwartz-castler-alice-bonus-on-enter-summons-a-pawn-piece-when-an-opposing-unit-has-even-life', 'Alice, Distorted Queen')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Schwartz Castler')
    ->play('Schwartz Castler')->pay(3)
    ->expectCardExists('myField', 'Pawn Piece', '[Alice Bonus] An opposing unit has 6 life: a Pawn Piece token was summoned')
    ->tested('Schwartz Castler')
    ->covers(['on-enter', 'champion-bonus', 'token', 'summon', 'condition'], '[Alice Bonus] **On Enter: **If an opponent controls a unit with an even life stat, **summon** a Pawn Piece token.')
    ->register();

gaBonus('schwartz-castler-alice-bonus-without-an-even-life-opposing-unit-summons-nothing', 'Alice, Distorted Queen')
    ->seed('myHand', 'Schwartz Castler')
    ->play('Schwartz Castler')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'The only opposing unit has 15 life (odd): no token')
    ->tested('Schwartz Castler')
    ->covers(['on-enter', 'champion-bonus', 'condition'], 'If an opponent controls a unit with an even life stat')
    ->register();

gaScenario('schwartz-castler-without-alice-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Schwartz Castler')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Schwartz Castler')
    ->play('Schwartz Castler')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'Not Alice: no token even though an opposing unit has even life')
    ->tested('Schwartz Castler')
    ->covers(['on-enter', 'champion-bonus', 'condition'], '[Alice Bonus] **On Enter: **')
    ->register();

// ---- Fraternal Garrison (Jin) ----

gaBonus('fraternal-garrison-jin-bonus-gets-plus-one-power-when-another-ally-enters', 'Jin, Fate Defiant')
    ->seed('myField', 'Fraternal Garrison')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)
    ->expectPower('p1Field-1', 2, '[Jin Bonus] another ally entered: 1 + 1 power until end of turn')
    ->tested('Fraternal Garrison')
    ->covers(['champion-bonus', 'trigger', 'stat-change'], '[Jin Bonus] Whenever another ally enters the field under your control, CARDNAME gets +1 [POWER] until end of turn.')
    ->register();

// ---- Windspire Crest (Diao Chan) ----

gaBonus('windspire-crest-diao-chan-bonus-removes-two-glimmer-counters-to-buff-an-ally', 'Diao Chan, Enchantress')
    ->patch('myField-0', ['Counters' => ['glimmer' => 2]])
    ->seed('myField', 'Windspire Crest')
    ->seed('myField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-2')->pay(2)->pass(1)
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'A buff counter on the ally')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Both glimmer counters were removed')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Crest rested')
    ->expectZoneCount('myMemory', 2, 1, 'The (2) cost was paid')
    ->tested('Windspire Crest')
    ->covers(['activated-ability', 'champion-bonus', 'rest', 'counter', 'targeting'], '[Diao Chan Bonus] (2), [REST], **Remove two glimmer counters from your champion**: Put a **buff** counter on an ally you control.')
    ->register();

gaScenario('fraternal-garrison-without-jin-gets-no-power-when-another-ally-enters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fraternal Garrison')
    ->seed('myField', 'Fraternal Garrison')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)
    ->expectPower('p1Field-1', 1, 'Not Jin: still 1 power')
    ->tested('Fraternal Garrison')
    ->covers(['champion-bonus', 'trigger', 'condition'], '[Jin Bonus]')
    ->register();

gaScenario('windspire-crest-without-diao-chan-cannot-use-its-first-ability')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windspire Crest')
    ->patch('myField-0', ['Counters' => ['glimmer' => 2]])
    ->seed('myField', 'Windspire Crest')
    ->seed('myField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '2', 'Not Diao Chan: the activation was refused and the Crest did not rest')
    ->tested('Windspire Crest')
    ->covers(['activated-ability', 'champion-bonus', 'condition'], '[Diao Chan Bonus]')
    ->register();

// ---- Crystalvein Awakening (Merlin) ----
// (The imbue YES/NO comes before payment, so a plain castSpell would answer it with a card click.)

gaBonus('crystalvein-awakening-not-imbued-summons-a-memorite-obelith-with-one-sheen-counter', 'Merlin, Memorite Vassal')
    ->seed('myHand', 'Crystalvein Awakening')
    ->play('Crystalvein Awakening')->answer(1, 'NO')->pay(2)
    ->expectCardExists('myField', 'Memorite Obelith', 'A Memorite Obelith token was summoned')
    ->expectProperty('p1Field-1', 'Counters', '{"sheen":1}', 'Not imbued: one sheen counter')
    ->tested('Crystalvein Awakening')
    ->covers(['summon', 'token', 'counter', 'imbue'], '**Summon** a Memorite Obelith token with a **sheen** counter on it.')
    ->register();

gaBonus('crystalvein-awakening-imbued-puts-an-additional-sheen-counter-on-the-obelith', 'Merlin, Memorite Vassal')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Crystalvein Awakening')
    ->play('Crystalvein Awakening')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'Counters', '{"sheen":2}', 'Imbued: an additional sheen counter (two)')
    ->tested('Crystalvein Awakening')
    ->covers(['imbue', 'champion-bonus', 'counter', 'condition'], '[Merlin Bonus] **Imbue 2**', 'If CARDNAME is **imbued**, put an additional **sheen** counter on that token.')
    ->register();

gaScenario('crystalvein-awakening-without-merlin-is-not-offered-imbue')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crystalvein Awakening')
    ->seed('myHand', 'Crystalvein Awakening')
    ->play('Crystalvein Awakening')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"sheen":1}', 'Not Merlin: no imbue offer, one sheen counter')
    ->tested('Crystalvein Awakening')
    ->covers(['imbue', 'champion-bonus', 'condition'], '[Merlin Bonus] **Imbue 2**')
    ->register();

// ---- Fracturing Slash (Merlin) ----

gaBonus('fracturing-slash-merlin-bonus-on-attack-moves-sheen-counters-onto-the-defender', 'Merlin, Memorite Vassal')
    ->seed('myField', 'Dungeon Guide', properties: ['Counters' => ['sheen' => 2]])
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Fracturing Slash')
    ->endTurn()->endTurn(2)->pass(1)
    ->play('Fracturing Slash')->pay(2)->answer(1, 'myField-2')->answer(1, 'theirField-0')->answer(1, 'myField-1')->answer(1, 'myField-1')->pass(1)
    ->expectProperty('p2Field-0', 'Counters', '{"sheen":2,"_champDamageThisTurn":4}', '[Merlin Bonus] On Attack: both sheen counters moved onto the defender')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The Dungeon Guide lost its sheen counters')
    ->tested('Fracturing Slash')
    ->covers(['on-attack', 'champion-bonus', 'counter', 'sheen'], '[Merlin Bonus] **On Attack: ** You may move up to three **sheen** counters from among units on the field onto the defender.')
    ->register();

// ---- Landscape Corsair (Kongming) ----

gaBonus('landscape-corsair-kongming-bonus-on-enter-changes-the-direction-of-the-shifting-currents', 'Kongming, Wayward Maven')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myHand', 'Landscape Corsair')
    ->play('Landscape Corsair')->pay(3)->answer(1, 'SOUTH')
    ->expectProperty('p1Mastery-0', 'Direction', 'SOUTH', '[Kongming Bonus] On Enter: the Shifting Currents now face South')
    ->tested('Landscape Corsair')
    ->covers(['on-enter', 'champion-bonus', 'currents'], '[Kongming Bonus] **On Enter:** You may change the direction of your Shifting Currents to a different direction of your choice.')
    ->register();

gaScenario('landscape-corsair-without-kongming-leaves-the-currents-alone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Landscape Corsair')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myHand', 'Landscape Corsair')
    ->play('Landscape Corsair')->pay(3)
    ->expectProperty('p1Mastery-0', 'Direction', 'NORTH', 'Not Kongming: no prompt, the currents still face North')
    ->tested('Landscape Corsair')
    ->covers(['on-enter', 'champion-bonus', 'condition'], '[Kongming Bonus] **On Enter:**')
    ->register();
