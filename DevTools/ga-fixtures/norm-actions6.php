<?php
/**
 * Phase 3d (Norm): actions, sixth batch (tokens, scavenges, reveals, Aetherwing and fate cards). 'quiet' deck.
 */

// ---- Equivalent Exchange ----

gaScenario('equivalent-exchange-summons-a-lost-being-and-banishing-two-allies-wakes-and-buffs-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Equivalent Exchange')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Gray Wolf')
    ->seed('myHand', 'Equivalent Exchange')
    ->castSpell('Equivalent Exchange', pay: 4, window: '')
    ->expectCardExists('myField', 'Lost Being', 'A Lost Being token was summoned')
    ->tested('Equivalent Exchange')
    ->covers(['summon', 'banish', 'optional-choice'], '**Summon** a Lost Being token rested. Then you may banish two ally cards from a single graveyard. If you do, wake up that token and put a **buff** counter on it.')
    ->register();

// ---- Shardwing Searchlight ----

gaScenario('shardwing-searchlight-summons-a-memorite-shardwing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shardwing Searchlight')
    ->seed('myHand', 'Shardwing Searchlight')
    ->castSpell('Shardwing Searchlight', pay: 3, window: '')
    ->expectCardExists('myField', 'Memorite Shardwing', 'A Memorite Shardwing token was summoned')
    ->tested('Shardwing Searchlight')
    ->covers(['summon'], '**Summon** a Memorite Shardwing token.')
    ->register();

// ---- Scavenge the Distillery ----

gaScenario('scavenge-the-distillery-returns-a-potion-item-from-the-graveyard-to-hand')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Scavenge the Distillery')
    ->seed('myGraveyard', 'Distilled Water')
    ->seed('myHand', 'Scavenge the Distillery')
    ->castSpell('Scavenge the Distillery', pay: 3, window: '', target: 'myGraveyard-0')
    ->expectCardExists('myHand', 'Distilled Water', 'The Potion item returned to hand')
    ->tested('Scavenge the Distillery')
    ->covers(['zone-movement', 'targeting'], 'Return up to one target Potion item card from your graveyard to your hand.')
    ->register();

// ---- Find Recipe ----

gaScenario('find-recipe-scavenge-six-finds-a-potion-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Find Recipe')
    ->deckTop(['Dungeon Guide', 'Distilled Water'])
    ->seed('myHand', 'Find Recipe')
    ->castSpell('Find Recipe', pay: 2, window: '')->answer(1, 'myTempZone-4')
    ->expectCardExists('myHand', 'Distilled Water', 'The Potion within the top six was put into the hand')
    ->tested('Find Recipe')
    ->covers(['scavenge'], '**Scavenge 6** for a Potion card.')
    ->register();

// ---- Gear Haul ----

gaScenario('gear-haul-scavenges-five-for-a-veltech-item-twice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gear Haul')
    ->deckTop(['Dungeon Guide', 'CookTech Knife', 'Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide', 'CookTech Apron'])
    ->seed('myHand', 'Gear Haul')
    ->castSpell('Gear Haul', pay: 4, window: '')->answer(1, 'myTempZone-3')->answer(1, 'PASS')->answer(1, 'myTempZone-3')
    ->expectCardExists('myHand', 'CookTech Knife', 'The first scavenge put the VelTech item within the top five into the hand')
    ->expectCardExists('myHand', 'CookTech Apron', 'The second scavenge found the next VelTech item')
    ->tested('Gear Haul')
    ->covers(['scavenge'], '**Scavenge 5** for a VelTech item card twice.')
    ->register();

// ---- Edge of Tomorrow ----

gaScenario('edge-of-tomorrow-scavenge-eight-for-a-domain-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Edge of Tomorrow')
    ->deckTop(['Dungeon Guide', 'Wool Brook'])
    ->seed('myHand', 'Edge of Tomorrow')
    ->castSpell('Edge of Tomorrow', pay: 2, window: '')->answer(1, 'myTempZone-6')
    ->expectCardExists('myHand', 'Wool Brook', 'The domain within the top eight was put into the hand')
    ->tested('Edge of Tomorrow')
    ->covers(['scavenge'], '**Scavenge 8** for a domain card.')
    ->register();

// ---- Obscured Offering ----

gaScenario('obscured-offering-banishing-two-material-cards-gives-a-regalia-spellshroud')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obscured Offering')
    ->seed('myField', 'Prototype Pistol')
    ->seed('myHand', 'Obscured Offering')
    ->play('Obscured Offering')->answer(1, 'myMaterial-0')->answer(1, 'myMaterial-0')->pay(2)->answer(1, 'myField-1')
    ->expectZoneCount('myMaterial', 2, 1, 'Two cards were banished from the material deck as the additional cost (4 - 2)')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The regalia gained spellshroud')
    ->tested('Obscured Offering')
    ->covers(['additional-cost', 'spellshroud'], 'As an additional cost to activate this card, banish two cards from your material deck.', 'Target regalia gains **spellshroud** until end of turn.')
    ->register();

// ---- Peer Beyond ----

gaScenario('peer-beyond-the-target-unit-gains-true-sight-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Peer Beyond')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Peer Beyond')
    ->castSpell('Peer Beyond', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","TRUE_SIGHT"]', 'The unit gained true sight until end of turn')
    ->tested('Peer Beyond')
    ->covers(['true-sight', 'targeting'], 'Target unit gains **true sight** until end of turn.')
    ->register();

gaScenario('peer-beyond-ephemerate-from-the-graveyard-costs-one-and-is-ephemeral')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Peer Beyond')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myGraveyard', 'Peer Beyond')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)->answer(1, 'myField-1')
    ->expectCardExists('myBanish', 'Peer Beyond', 'An ephemeral action is banished once it resolves')
    ->expectZoneCount('myMemory', 1, 1, 'Ephemerate (1) was paid')
    ->tested('Peer Beyond')
    ->covers(['ephemerate', 'graveyard-activation'], '**Ephemerate —** (1)')
    ->register();

// ---- Reconstructive Surgery ----

gaScenario('reconstructive-surgery-puts-a-buff-counter-on-an-automaton-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reconstructive Surgery')
    ->seed('myField', 'Delivery Droid')
    ->seed('myHand', 'Reconstructive Surgery')
    ->castSpell('Reconstructive Surgery', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Delivery Droid got a buff counter')
    ->tested('Reconstructive Surgery')
    ->covers(['modal', 'counter', 'targeting'], 'Put a **buff** counter on target Automaton ally.')
    ->register();

// ---- Bestial Frenzy ----

gaScenario('bestial-frenzy-choosing-the-first-mode-gives-the-champion-plus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bestial Frenzy')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Bestial Frenzy')
    ->castSpell('Bestial Frenzy', pay: 2, window: '')->answer(1, 'YES')
    ->expectLevel('p1Field-0', 1, 'Your champion got +1 level until end of turn')
    ->tested('Bestial Frenzy')
    ->covers(['modal', 'level', 'stat-change'], 'Your champion gets +1 level until end of turn.')
    ->register();

// ---- Crystallized Destiny ----

gaScenario('crystallized-destiny-with-two-fatestones-costs-two-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crystallized Destiny')
    ->seed('myField', 'Airborne Squirrel')
    ->seed('myField', 'Airborne Squirrel')
    ->seed('myHand', 'Crystallized Destiny')
    ->castSpell('Crystallized Destiny', pay: 1, window: '')
    ->expectCardExists('myGraveyard', 'Crystallized Destiny', 'Two Fatebound objects: the spell cost 3 - 2 = 1 and resolved')
    ->expectZoneCount('myMemory', 1, 1, 'One card paid')
    ->tested('Crystallized Destiny')
    ->covers(['cost-reduction', 'condition'], 'As long as you control two or more Fatestone and/or Fatebound objects, this card costs 2 less to activate.')
    ->register();

// ---- Dodge Roll ----

gaScenario('dodge-roll-makes-the-target-unit-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dodge Roll')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Dodge Roll')
    ->castSpell('Dodge Roll', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_NONCOMBAT_3","DISTANT"]', 'The unit became distant')
    ->tested('Dodge Roll')
    ->covers(['prevention', 'distant', 'targeting'], 'Prevent the next 3 non-combat damage that would be dealt to target unit you control this turn. That unit becomes **distant**.')
    ->register();

// ---- Suited Trickery ----

gaScenario('suited-trickery-champion-attack-declarations-cost-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Suited Trickery')
    ->seed('myHand', 'Suited Trickery')
    ->castSpell('Suited Trickery', pay: 1, window: '')
    ->expectCardExists('myGraveyard', 'Suited Trickery', 'The spell resolved')
    ->tested('Suited Trickery')
    ->covers(['restriction', 'cost'], 'Until the beginning of your next turn, players can\'t declare attacks with champions they control unless they pay (2) for each attack declaration.')
    ->register();

// ---- Twisted Verdict ----

gaScenario('twisted-verdict-the-opponent-puts-two-of-the-top-five-into-your-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Twisted Verdict')
    ->seed('myHand', 'Twisted Verdict')
    ->castSpell('Twisted Verdict', pay: 6, window: '')->answer(2, 'myTempZone-0&myTempZone-1')
    ->expectZoneCount('myMemory', 8, 1, 'Six paid plus the two cards the opponent chose')
    ->tested('Twisted Verdict')
    ->covers(['look', 'zone-movement', 'targeting'], 'Target opponent looks at the top five cards of your deck. They choose two cards from among them and put them into your memory.')
    ->register();
