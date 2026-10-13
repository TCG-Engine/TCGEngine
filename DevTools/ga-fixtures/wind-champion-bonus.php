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

// ---- Tailwind's Blessing / Wulin Lancer (Shifting Currents changing from North to West) ----
// Kongming's end-phase prompt lets the owner change the direction; the stat bonuses are "until end of turn", so they are read right after the change.

gaBonus('tailwinds-blessing-allies-get-plus-one-power-when-the-currents-change-from-north-to-west', 'Kongming, Wayward Maven')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Tailwind\'s Blessing')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->answer(1, 'WEST')
    ->expectPower('p1Field-2', 2, 'North to West: allies got +1 power until end of turn (1 + 1)')
    ->tested('Tailwind\'s Blessing')
    ->covers(['currents', 'trigger', 'stat-change'], 'Whenever your Shifting Currents change from facing North to West, allies you control get +1 [POWER] until end of turn.')
    ->register();

gaBonus('tailwinds-blessing-other-direction-changes-give-no-power', 'Kongming, Wayward Maven')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Tailwind\'s Blessing')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->answer(1, 'EAST')
    ->expectPower('p1Field-2', 1, 'North to East: no bonus')
    ->tested('Tailwind\'s Blessing')
    ->covers(['currents', 'trigger', 'condition'], 'change from facing North to West')
    ->register();

gaBonus('wulin-lancer-gets-plus-two-power-when-the-currents-change-from-north-to-west', 'Kongming, Wayward Maven')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Wulin Lancer')
    ->endTurn()->answer(1, 'WEST')
    ->expectPower('p1Field-1', 4, 'North to West: +2 power until end of turn (2 + 2)')
    ->tested('Wulin Lancer')
    ->covers(['currents', 'trigger', 'stat-change'], 'Whenever your Shifting Currents change from facing North to West, CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

// ---- Fatestones (Guo Jia) ----

gaScenario('beseeched-fatestone-on-enter-materializes-a-card-from-the-material-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Beseeched Fatestone')
    ->seed('myHand', 'Beseeched Fatestone')
    ->play('Beseeched Fatestone')->pay(6)->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Lorraine, Wandering Warrior', 'On Enter: a card from the material deck was materialized (a champion, so the champion levelled up)')
    ->tested('Beseeched Fatestone')
    ->covers(['on-enter', 'materialize'], '**On Enter**: **Materialize** a card from your material deck.')
    ->register();

gaBonus('wildgrowth-fatestone-guo-jia-bonus-gets-a-buff-counter-when-another-wind-object-enters', 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Wildgrowth Fatestone')
    ->seed('myHand', 'Windrider Mage')
    ->play('Windrider Mage')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Guo Jia Bonus] another wind object entered: a buff counter on the Fatestone')
    ->tested('Wildgrowth Fatestone')
    ->covers(['champion-bonus', 'trigger', 'counter'], '[Guo Jia Bonus] Whenever another wind element object enters the field under your control, put a **buff** counter on Wildgrowth Fatestone.')
    ->register();

gaScenario('wildgrowth-fatestone-without-guo-jia-gets-no-buff-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wildgrowth Fatestone')
    ->seed('myField', 'Wildgrowth Fatestone')
    ->seed('myHand', 'Windrider Mage')
    ->play('Windrider Mage')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Not Guo Jia: no counter')
    ->tested('Wildgrowth Fatestone')
    ->covers(['champion-bonus', 'trigger', 'condition'], '[Guo Jia Bonus]')
    ->register();

gaScenario('cyclonic-fatestone-fast-on-enter-suppresses-an-attacking-ally-you-dont-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cyclonic Fatestone')
    ->seed('myHand', 'Cyclonic Fatestone')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(3)->answer(1, 'theirField-1')
    ->expectCardExists('theirBanish', 'Giant Tortoise', 'On Enter: the attacking ally was suppressed (banished until the end phase)', 1)
    ->expectDamage('p1Field-0', 0, 'The suppressed ally never dealt its damage')
    ->tested('Cyclonic Fatestone')
    ->covers(['keyword', 'fast-activation', 'on-enter', 'suppress', 'targeting'], '**Fast Activation**', '**On Enter**: **Suppress** target attacking ally you don\'t control.')
    ->register();

// ---- Rowland, Schwartz Knight (Alice) ----

gaBonus('rowland-alice-bonus-on-ally-kill-of-a-taunt-unit-wakes-him-up', 'Alice, Distorted Queen')
    ->seed('myField', 'Rowland, Schwartz Knight', properties: ['Counters' => ['buff' => 2]])
    ->seed('myField', 'Throne-Keeper Bullfrog', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')
    ->expectCardExists('theirGraveyard', 'Throne-Keeper Bullfrog', 'The Bullfrog (taunt, 4 life) was killed by 2 + 2 power', 1)
    ->expectProperty('p1Field-1', 'Status', '2', '[Alice Bonus] On Ally Kill of a taunt unit: Rowland woke up')
    ->tested('Rowland, Schwartz Knight')
    ->covers(['on-kill', 'champion-bonus', 'status', 'condition'], '[Alice Bonus] **On Ally Kill:** If the killed unit had **intercept** or **taunt**, wake up CARDNAME.')
    ->register();

gaBonus('rowland-alice-bonus-on-ally-kill-of-a-plain-unit-leaves-him-rested', 'Alice, Distorted Queen')
    ->seed('myField', 'Rowland, Schwartz Knight', properties: ['Counters' => ['buff' => 2]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('theirGraveyard', 'Dungeon Guide', 'The Dungeon Guide was killed by 2 + 2 power', 1)
    ->expectProperty('p1Field-1', 'Status', '1', 'No intercept or taunt: Rowland stays rested')
    ->tested('Rowland, Schwartz Knight')
    ->covers(['on-kill', 'champion-bonus', 'condition'], 'If the killed unit had **intercept** or **taunt**')
    ->register();

// ---- Beseeching Flourish (Jin) ----
// Jin has no power of his own, so the attack is made with a weapon (Zephyr's Edge).

gaScenario('beseeching-flourish-jin-bonus-on-hit-materializes-a-polearm-weapon')
    ->deck("# Material\n1 Spirit of Fire\n1 Crescent Glaive\n# Main\n12 Dungeon Guide\n4 Beseeching Flourish")
    ->champion(cardName: 'Jin, Fate Defiant', lineage: ['Spirit of Wind'])
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Beseeching Flourish')
    ->reachTurnThree()
    ->play('Beseeching Flourish')->pay(3)->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Crescent Glaive', '[Jin Bonus] On Hit: a Polearm weapon was materialized')
    ->tested('Beseeching Flourish')
    ->covers(['on-hit', 'champion-bonus', 'materialize'], '[Jin Bonus] **On Hit:** **Materialize** a Polearm weapon card from your material deck.')
    ->register();

gaScenario('beseeching-flourish-without-jin-materializes-nothing')
    ->deck("# Material\n1 Spirit of Fire\n1 Crescent Glaive\n# Main\n12 Dungeon Guide\n4 Beseeching Flourish")
    ->readyFor('Beseeching Flourish')
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Beseeching Flourish')
    ->reachTurnThree()
    ->play('Beseeching Flourish')->pay(3)->answer(1, 'myField-1')->answer(1, 'theirField-0')
    ->expectZoneCount('myField', 2, 1, 'Not Jin: nothing materialized')
    ->tested('Beseeching Flourish')
    ->covers(['on-hit', 'champion-bonus', 'condition'], '[Jin Bonus] **On Hit:**')
    ->register();

// ---- Dominating Strike (Vanitas) ----
// Player 2 plays it on turn 2 (no recollection phase), so the wind cards seeded into memory are still there to be revealed.

gaScenario('dominating-strike-vanitas-bonus-reveals-three-wind-cards-from-memory-rather-than-paying')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Vanitas, Convergent Ruin', lineage: ['Vanitas, Obliviate Schemer', 'Spirit of Wind'], level: 2, player: 2)
    ->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)
    ->seed('myHand', 'Dominating Strike', player: 2)
    ->endTurn()
    ->play('Dominating Strike', 2)->answer(2, 'YES')->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 3, 2, 'Revealed, not paid: the three wind cards are still in memory')
    ->expectZoneCount('myHand', 8, 2, 'Nothing was paid from the hand (7 + the turn draw + the seeded Strike - the Strike)')
    ->expectDamage('p1Field-0', 5, 'The attack: Dominating Strike 4 + the champion\'s 1 power = 5')
    ->tested('Dominating Strike')
    ->covers(['alt-cost', 'champion-bonus', 'reveal'], '[Vanitas Bonus] You may reveal three wind element cards from your memory rather than pay this card\'s reserve cost.')
    ->register();

gaScenario('dominating-strike-vanitas-bonus-declined-pays-the-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Vanitas, Convergent Ruin', lineage: ['Vanitas, Obliviate Schemer', 'Spirit of Wind'], level: 2, player: 2)
    ->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)
    ->seed('myHand', 'Dominating Strike', player: 2)
    ->endTurn()
    ->play('Dominating Strike', 2)->answer(2, 'NO')->pay(3, 2)->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 6, 2, 'Declined: three cards were paid into memory on top of the three wind cards')
    ->tested('Dominating Strike')
    ->covers(['alt-cost', 'champion-bonus', 'condition'], 'rather than pay this card\'s reserve cost')
    ->register();

gaScenario('dominating-strike-without-vanitas-is-not-offered-the-alternative-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dominating Strike', player: 2)
    ->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)
    ->seed('myHand', 'Dominating Strike', player: 2)
    ->endTurn()
    ->play('Dominating Strike', 2)->pay(3, 2)->target('theirField-0', 2)
    ->expectZoneCount('myMemory', 6, 2, 'Not Vanitas: the reserve cost was paid')
    ->tested('Dominating Strike')
    ->covers(['alt-cost', 'champion-bonus', 'condition'], '[Vanitas Bonus]')
    ->register();
