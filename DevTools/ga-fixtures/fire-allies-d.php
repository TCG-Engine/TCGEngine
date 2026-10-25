<?php
/**
 * Phase 3a (Fire): allies batch 5 -- the remaining non-unique allies and the Direwolf token.
 */

gaScenario('pyrolysis-sage-class-bonus-enter-banishes-a-fire-card-to-empower-two')
    ->standardDeck('Pyrolysis Sage')
    ->readyFor('Pyrolysis Sage', classBonus: true)
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Pyrolysis Sage')
    ->castSpell('Pyrolysis Sage', pay: 2, window: 'opp', target: 'myGraveyard-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","VJdQQmnL2Z","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2: the next Spell resolves as if the champion got +2 level')
    ->expectZoneCount('myBanish', 1, 1, 'The fire card was banished from the graveyard')
    ->tested('Pyrolysis Sage')
    ->covers(['on-enter', 'class-bonus', 'banish', 'empower'], '[Class Bonus] **On Enter:** You may banish a fire element card from your graveyard. If you do, **empower 2**.')
    ->register();

gaScenario('pyrolysis-sage-without-the-class-bonus-does-not-empower')
    ->standardDeck('Pyrolysis Sage')
    ->readyFor('Pyrolysis Sage')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Pyrolysis Sage')
    ->castSpell('Pyrolysis Sage', pay: 2, window: 'opp')
    ->expectZoneCount('myBanish', 0, 1, 'No Class Bonus: nothing was offered or banished')
    ->expectCardExists('myField', 'Pyrolysis Sage', 'The ally still entered')
    ->tested('Pyrolysis Sage')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:** You may banish a fire element card from your graveyard. If you do, **empower 2**.')
    ->register();

gaScenario('serpentine-judicator-opponents-cannot-recover')
    ->standardDeck('Serpentine Judicator')
    ->readyFor('Serpentine Judicator')
    ->seed('myField', 'Serpentine Judicator')
    ->champion(damage: 5, player: 2)
    ->seed('myField', 'Potion of Healing', player: 2)
    ->endTurn()
    ->answer(2, 'myField-1@Activate-0@Sacrifice')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 5, 'Your opponent\'s Recover 5 did nothing: the damage is still there')
    ->tested('Serpentine Judicator')
    ->covers(['static-ability', 'recover', 'prevention'], 'Your opponents can\'t **recover**.')
    ->register();

gaScenario('serpentine-judicator-its-controller-can-still-recover')
    ->standardDeck('Serpentine Judicator')
    ->champion(damage: 5, player: 2)
    ->seed('myField', 'Serpentine Judicator', player: 2)
    ->seed('myField', 'Potion of Healing', player: 2)
    ->endTurn()
    ->answer(2, 'myField-2@Activate-0@Sacrifice')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 0, 'Only the Judicator controller\'s opponents are blocked: their own Recover 5 worked')
    ->tested('Serpentine Judicator')
    ->covers(['static-ability', 'recover', 'condition'], 'Your opponents can\'t **recover**.')
    ->register();

gaScenario('flashfire-horse-fast-activation-can-be-played-from-a-response-window')
    ->standardDeck('Flashfire Horse')
    ->readyFor('Flashfire Horse')
    ->seed('myHand', 'Flashfire Horse')
    ->endTurn()->answer(1, 'myHand-7')->pay(3)->pass(1)->pass(2)
    ->expectCardExists('myField', 'Flashfire Horse', 'Fast Activation: the ally was activated from a response window, outside the main phase')
    ->expectZoneCount('myMemory', 3, 1, 'Its reserve cost of 3 was paid')
    ->tested('Flashfire Horse')
    ->covers(['keyword', 'fast-activation'], '**Fast Activation** *(You may activate this card at fast speed.)*')
    ->register();

gaScenario('firetuned-automaton-discards-a-fire-card-as-an-additional-cost')
    ->standardDeck('Firetuned Automaton')
    ->readyFor('Firetuned Automaton', classBonus: true)
    ->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Firetuned Automaton')
    ->play('Firetuned Automaton')->answer(1, 'myHand-7')->pay(2)->pass(2)
    ->expectProperty('p1Graveyard-0', 'CardID', 'mttsvbgl6f', 'The fire card was discarded as the additional cost')
    ->expectCardExists('myField', 'Firetuned Automaton', 'The ally entered')
    ->expectZoneCount('myMemory', 2, 1, 'The 2 reserve cost was paid on top of the discard')
    ->tested('Firetuned Automaton')
    ->covers(['additional-cost', 'discard'], 'As an additional cost to activate this card, discard a fire element card.')
    ->register();

gaScenario('firetuned-automaton-needs-a-fire-card-in-hand-to-discard')
    ->standardDeck('Dungeon Guide')
    ->readyFor('Firetuned Automaton', classBonus: true)
    ->seed('myHand', 'Firetuned Automaton')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Not activated: there is no other fire element card to discard')
    ->tested('Firetuned Automaton')
    ->covers(['additional-cost', 'restriction'], 'As an additional cost to activate this card, discard a fire element card.')
    ->register();

gaScenario('direwolf-token-is-sacrificed-at-the-beginning-of-your-end-phase')
    ->standardDeck('Direwolf')
    ->seed('myField', 'Direwolf')
    ->endTurn()
    ->expectZoneCount('myField', 1, 1, 'The Direwolf was sacrificed at the beginning of the end phase: only the champion remains')
    ->tested('Direwolf')
    ->covers(['end-phase', 'sacrifice', 'token'], 'At the beginning of your end phase, sacrifice CARDNAME.')
    ->register();

gaScenario('charged-gunslinger-ranged-two-while-distant')
    ->standardDeck('Charged Gunslinger')
    ->seed('myField', 'Charged Gunslinger', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage')
    ->tested('Charged Gunslinger')
    ->covers(['keyword', 'ranged', 'combat'], '**Ranged 2**')
    ->register();

gaScenario('pupil-of-sacred-flames-class-bonus-attack-banishes-a-fire-card-to-empower-two')
    ->standardDeck('Pupil of Sacred Flames')
    ->readyFor('Pupil of Sacred Flames', classBonus: true)
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myField', 'Pupil of Sacred Flames')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The fire card was banished from the graveyard')
    ->expectProperty('p1Field-0', 'TurnEffects', '["n06isycm60","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 went on the champion')
    ->tested('Pupil of Sacred Flames')
    ->covers(['on-attack', 'class-bonus', 'banish', 'empower'], '[Class Bonus] **On Attack:** You may banish a fire element card from your graveyard. If you do, **empower 2**.')
    ->register();

// --- Second printings (a different card ID with identical text) ---
gaScenario('cheetah-of-bound-fury-second-printing-on-hit-banishes-and-returns-as-fatestone')
    ->standardDeck('#5giGYm8WlR')
    ->seed('myField', '#5giGYm8WlR')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->tested('#5giGYm8WlR')
    ->expectProperty('p1Field-1', 'CardID', 'o37qtuvlxa', 'On Hit: the Cheetah came back as Fatestone of Unrelenting')
    ->expectDamage('p2Field-0', 2, 'The attack hit for the Cheetah\'s 2 POWER first')
    ->covers(['on-hit', 'banish', 'transform'], 'On Hit: You may banish Cheetah of Bound Fury, then return it to the field under its owner\'s control. (It enters the field as Fatestone of Unrelenting.)')
    ->register();

gaScenario('firebird-trailblazer-second-printing-attack-discards-a-card-to-draw')
    ->standardDeck('#x8gbz5Qli9')
    ->readyFor('#x8gbz5Qli9')
    ->seed('myField', '#x8gbz5Qli9')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->answer(1, 'YES')->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'One card was discarded')
    ->expectDamage('p2Field-0', 2, 'The attack dealt its 2 POWER')
    ->tested('#x8gbz5Qli9')
    ->covers(['on-attack', 'draw-discard', 'optional-choice'], 'On Attack: You may discard a card. If you do, draw a card.')
    ->register();

gaScenario('general-at-arms-class-bonus-polearm-attack-enters-the-intent-with-plus-two-power')
    ->standardDeck('General at Arms')
    ->readyFor('General at Arms', classBonus: true)
    ->seed('myField', 'General at Arms')
    ->seed('myHand', 'Savage Swing')
    ->reachTurnThree()
    ->playSlot(7)->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, '[Class Bonus] The Polearm attack entered the intent with +2 POWER: 2 + 2 = 4')
    ->tested('General at Arms')
    ->covers(['static-ability', 'class-bonus', 'stat-change'], '[Class Bonus] Polearm attack cards you activate enter the intent with +2 [POWER].')
    ->register();

gaScenario('general-at-arms-without-the-class-bonus-gives-no-power')
    ->standardDeck('General at Arms')
    ->readyFor('General at Arms')
    ->seed('myField', 'General at Arms')
    ->seed('myHand', 'Savage Swing')
    ->reachTurnThree()
    ->playSlot(7)->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'No Class Bonus: the Polearm attack keeps its printed 2 POWER')
    ->tested('General at Arms')
    ->covers(['static-ability', 'class-bonus', 'condition'], '[Class Bonus] Polearm attack cards you activate enter the intent with +2 [POWER].')
    ->register();

gaScenario('general-at-arms-class-bonus-does-not-boost-a-non-polearm-attack')
    ->standardDeck('General at Arms')
    ->readyFor('General at Arms', classBonus: true)
    ->seed('myField', 'General at Arms')
    ->seed('myHand', 'Fiery Momentum')
    ->reachTurnThree()
    ->playSlot(7)->pay(3)->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'Fiery Momentum is not a Polearm: its printed 2 POWER with no fire cards in the graveyard')
    ->tested('General at Arms')
    ->covers(['static-ability', 'condition'], '[Class Bonus] Polearm attack cards you activate enter the intent with +2 [POWER].')
    ->register();

gaScenario('airship-captain-a-domain-entering-deals-two-damage-to-a-champion')
    ->standardDeck('Airship Captain')
    ->readyFor('Airship Captain')
    ->seed('myField', 'Airship Captain')
    ->seed('myHand', 'Epicurean Institute')
    ->castSpell('Epicurean Institute', pay: 1, window: 'opp', target: 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'A domain entered under your control: 2 damage to the targeted champion')
    ->expectDamage('p1Field-0', 0, 'Your own champion was not hurt')
    ->tested('Airship Captain')
    ->covers(['triggered-ability', 'domain', 'damage', 'targeting'], 'Whenever a domain enters the field under your control, deal 2 damage to target champion.')
    ->register();

gaScenario('lavasoul-tiger-class-bonus-enter-banishes-two-fire-cards-to-lose-pride')
    ->standardDeck('Lavasoul Tiger')
    ->readyFor('Lavasoul Tiger', classBonus: true)
    ->seed('myGraveyard', 'Red Slime')->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Lavasoul Tiger')
    ->castSpell('Lavasoul Tiger', pay: 4, window: 'opp')->answer(1, 'YES')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","zq0dvl1m3z"]', 'The Tiger lost pride until end of turn')
    ->expectZoneCount('myBanish', 2, 1, 'Two fire cards were banished from the graveyard')
    ->tested('Lavasoul Tiger')
    ->covers(['on-enter', 'class-bonus', 'banish', 'pride'], '[Class Bonus] **On Enter:** You may banish two fire element cards from your graveyard. If you do, CARDNAME loses **pride** until end of turn.')
    ->register();

gaScenario('lavasoul-tiger-declining-the-banish-keeps-its-pride')
    ->standardDeck('Lavasoul Tiger')
    ->readyFor('Lavasoul Tiger', classBonus: true)
    ->seed('myGraveyard', 'Red Slime')->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Lavasoul Tiger')
    ->castSpell('Lavasoul Tiger', pay: 4, window: 'opp')->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Declined: the Tiger keeps pride')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->tested('Lavasoul Tiger')
    ->covers(['on-enter', 'optional-choice-decline', 'pride'], '[Class Bonus] **On Enter:** You may banish two fire element cards from your graveyard. If you do, CARDNAME loses **pride** until end of turn.')
    ->register();

gaScenario('ritai-guard-class-bonus-equestrian-with-a-horse-ally-has-taunt')
    ->standardDeck('Ritai Guard')
    ->readyFor('Ritai Guard', classBonus: true)
    ->seed('myField', 'Ritai Guard')
    ->seed('myField', 'Flashfire Horse')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the Ritai Guard must be targeted before your champion')
    ->answer(2, 'theirField-1')->pass(2)->pass(1)
    ->expectDamage('p1Field-1', 1, 'The attack landed on the taunting Ritai Guard')
    ->expectDamage('p1Field-0', 0, 'The champion was protected')
    ->tested('Ritai Guard')
    ->covers(['taunt', 'class-bonus', 'condition'], '[Class Bonus] ***Equestrian*** -- As long as you control a Horse ally, CARDNAME has **taunt** and **vigor**.')
    ->register();

gaScenario('ritai-guard-without-a-horse-ally-has-no-taunt')
    ->standardDeck('Ritai Guard')
    ->readyFor('Ritai Guard', classBonus: true)
    ->seed('myField', 'Ritai Guard')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 1, 'No Horse ally: no taunt, so the champion could be attacked directly')
    ->tested('Ritai Guard')
    ->covers(['taunt', 'class-bonus', 'condition'], '[Class Bonus] ***Equestrian*** -- As long as you control a Horse ally, CARDNAME has **taunt** and **vigor**.')
    ->register();

gaScenario('ritai-stablemaster-equestrian-enter-discards-fire-cards-to-draw-into-memory')
    ->standardDeck('Ritai Stablemaster')
    ->readyFor('Ritai Stablemaster')
    ->seed('myField', 'Flashfire Horse')
    ->seed('myHand', 'Red Slime')->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Ritai Stablemaster')
    ->play('Ritai Stablemaster')->pay(2)->pass(2)->answer(1, 'myHand-1&myHand-3')
    ->expectZoneCount('myGraveyard', 2, 1, 'Two fire cards were discarded')
    ->expectZoneCount('myMemory', 4, 1, 'Paid 2 for the ally, then drew 2 cards into memory')
    ->tested('Ritai Stablemaster')
    ->covers(['on-enter', 'equestrian', 'discard', 'draw-into-memory'], '***Equestrian*** -- **On Enter**: If you control a Horse ally, you may discard up to two fire element cards. For each card discarded this way, draw a card into your memory.')
    ->register();

gaScenario('ritai-stablemaster-without-a-horse-ally-its-enter-does-nothing')
    ->standardDeck('Ritai Stablemaster')
    ->readyFor('Ritai Stablemaster')
    ->seed('myHand', 'Red Slime')->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Ritai Stablemaster')
    ->play('Ritai Stablemaster')->pay(2)->pass(2)
    ->expectDqEmpty()
    ->expectZoneCount('myGraveyard', 0, 1, 'No Horse ally: nothing could be discarded')
    ->expectCardExists('myField', 'Ritai Stablemaster', 'The ally still entered')
    ->tested('Ritai Stablemaster')
    ->covers(['on-enter', 'equestrian', 'condition'], '***Equestrian*** -- **On Enter**: If you control a Horse ally, you may discard up to two fire element cards. For each card discarded this way, draw a card into your memory.')
    ->register();

$gaRavagerDeck = <<<'DECK'
# Material
1 Spirit of Fire
1 Blast Shield
# Main
4 Outfitted Ravager
8 Dungeon Guide
8 Fluffy Shopkeep
DECK;

gaScenario('outfitted-ravager-class-bonus-a-newly-linked-object-draws-then-discards')
    ->deck($gaRavagerDeck)
    ->readyFor('Outfitted Ravager', classBonus: true)
    ->seed('myField', 'Outfitted Ravager')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')->answer(1, 'myHand-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'Drew a card, then discarded one')
    ->expectZoneCount('myHand', 8, 1, 'Net hand size is unchanged: +1 draw, -1 discard')
    ->expectCardExists('myField', 'Blast Shield', 'The shield entered linked to the Ravager')
    ->tested('Outfitted Ravager', 'Blast Shield')
    ->covers(['triggered-ability', 'link', 'class-bonus', 'draw-discard'], '[Class Bonus] Whenever an object becomes linked to CARDNAME, draw a card and discard a card.')
    ->register();

gaScenario('outfitted-ravager-without-the-class-bonus-a-link-draws-nothing')
    ->deck($gaRavagerDeck)
    ->readyFor('Outfitted Ravager')
    ->seed('myField', 'Outfitted Ravager')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')
    ->expectZoneCount('myHand', 8, 1, 'No Class Bonus: no draw and no discard (8 = 7 + the turn draw)')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing was discarded')
    ->tested('Outfitted Ravager')
    ->covers(['triggered-ability', 'link', 'class-bonus', 'condition'], '[Class Bonus] Whenever an object becomes linked to CARDNAME, draw a card and discard a card.')
    ->register();

$gaBlastShieldDeck = <<<'DECK'
# Material
1 Spirit of Fire
1 Blast Shield
# Main
8 Dungeon Guide
8 Fluffy Shopkeep
4 Fairy Whispers
DECK;

gaScenario('blast-shield-class-bonus-the-linked-ally-gets-plus-two-power')
    ->deck($gaBlastShieldDeck)
    ->readyFor('Blast Shield', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')
    ->expectPower('p1Field-1', 3, '[Class Bonus] The linked ally gets +2 POWER: 1 + 2 = 3')
    ->tested('Blast Shield')
    ->covers(['ally-link', 'class-bonus', 'stat-change'], '**Ally Link**', '[Class Bonus] Linked ally gets +2 [POWER].')
    ->register();

gaScenario('blast-shield-without-the-class-bonus-the-linked-ally-keeps-its-power')
    ->deck($gaBlastShieldDeck)
    ->readyFor('Blast Shield')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')
    ->expectPower('p1Field-1', 1, 'No Class Bonus: the linked ally has its printed 1 POWER')
    ->tested('Blast Shield')
    ->covers(['ally-link', 'class-bonus', 'condition'], '[Class Bonus] Linked ally gets +2 [POWER].')
    ->register();

gaScenario('blast-shield-deals-two-damage-to-your-champion-at-your-recollection-phase')
    ->deck($gaBlastShieldDeck)
    ->readyFor('Blast Shield')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')
    ->expectDamage('p1Field-0', 2, 'Materialized in the materialize phase, the shield dealt 2 damage to its controller\'s champion in the same turn\'s recollection phase')
    ->tested('Blast Shield')
    ->covers(['recollection-phase', 'damage'], 'At the beginning of your recollection phase, deal 2 damage to your champion.')
    ->register();

if (!function_exists('gaRegaliaDeck')) {
    // A deck whose material deck holds one regalia card to materialize in the materialize phase of turn 3.
    function gaRegaliaDeck(string $regalia): string {
        return "# Material\n1 Spirit of Fire\n1 {$regalia}\n# Main\n8 Dungeon Guide\n8 Fluffy Shopkeep\n4 Fairy Whispers";
    }
}

gaScenario('beastsoul-visage-linked-ally-gets-plus-two-power-and-pride-three')
    ->deck(gaRegaliaDeck('Beastsoul Visage'))
    ->readyFor('Beastsoul Visage')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')->target('myField-1')
    ->expectPower('p1Field-1', 3, 'The linked ally gets +2 POWER: 1 + 2 = 3')
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectDqEmpty()
    ->expectDamage('p2Field-0', 0, 'Pride 3: a level 0 champion cannot attack with the linked ally, so no attack prompt opened')
    ->tested('Beastsoul Visage')
    ->covers(['ally-link', 'stat-change', 'pride'], '**Ally Link**', 'Linked ally gets +2 [POWER], has **pride 3**, and is a Beast in addition to its other types.')
    ->register();

gaScenario('infernal-vessel-reduces-every-recover-by-three')
    ->deck(gaRegaliaDeck('Infernal Vessel'))
    ->readyFor('Infernal Vessel')
    ->champion(damage: 6, player: 2)
    ->seed('myField', 'Potion of Healing', player: 2)
    ->endTurn()->pass(2)->pass(2)->endTurn(2)->pass(2)->pass(2)->answer(1, 'myMaterial-0')
    ->answer(2, 'myField-1@Activate-0@Sacrifice')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 4, 'Recover 5 became Recover 5 - 3 = 2: 6 damage down to 4')
    ->tested('Infernal Vessel')
    ->covers(['static-ability', 'recover', 'replacement-effect'], 'If a player would **recover** an amount, they **recover X** instead, where **X** is that amount minus 3.')
    ->register();

gaScenario('infernal-vessel-control-without-it-a-recover-five-heals-five')
    ->deck(gaRegaliaDeck('Infernal Vessel'))
    ->readyFor('Infernal Vessel')
    ->champion(damage: 6, player: 2)
    ->seed('myField', 'Potion of Healing', player: 2)
    ->endTurn()->pass(2)->pass(2)->endTurn(2)->pass(2)->pass(2)->pass(1)
    ->answer(2, 'myField-1@Activate-0@Sacrifice')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 1, 'The Vessel was never materialized: Recover 5 took 6 damage down to 1')
    ->tested('Infernal Vessel')
    ->covers(['static-ability', 'recover', 'condition'], 'If a player would **recover** an amount, they **recover X** instead, where **X** is that amount minus 3.')
    ->register();

gaScenario('fanned-synchron-an-opponents-on-enter-deals-one-damage-to-their-champion')
    ->standardDeck('Fanned Synchron')
    ->seed('myField', 'Fanned Synchron')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->play('Dungeon Guide', player: 2)->pay(3, 2)
    ->expectCardExists('myField', 'Dungeon Guide', 'The ally entered', 2)
    ->expectDamage('p2Field-0', 1, 'The opponent\'s On Enter dealt 1 damage to its own controller\'s champion')
    ->tested('Fanned Synchron')
    ->covers(['triggered-ability', 'on-enter', 'damage'], 'Whenever an **on enter** ability of a non-champion object you don\'t control triggers, that object deals 1 damage to its controller\'s champion.')
    ->register();

gaScenario('fanned-synchron-your-own-on-enter-deals-no-damage')
    ->standardDeck('Fanned Synchron')
    ->seed('myField', 'Fanned Synchron')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->pass(2)
    ->expectCardExists('myField', 'Dungeon Guide', 'The ally entered and its On Enter triggered')
    ->expectDamage('p1Field-0', 0, 'An object you control triggering On Enter does not hurt you')
    ->tested('Fanned Synchron')
    ->covers(['triggered-ability', 'on-enter', 'condition'], 'Whenever an **on enter** ability of a non-champion object you don\'t control triggers, that object deals 1 damage to its controller\'s champion.')
    ->register();

gaScenario('fang-of-dragons-breath-polearm-weapon-link-gives-the-linked-weapon-plus-two-power')
    ->standardDeck('Fang of Dragon\'s Breath')
    ->readyFor('Fang of Dragon\'s Breath')
    ->seed('myField', 'Dragon\'s Dawn')
    ->seed('myHand', 'Fang of Dragon\'s Breath')
    ->play('Fang of Dragon\'s Breath')->target('myField-1')->pay(1)->pass(2)
    ->expectPower('p1Field-1', 4, 'The linked Polearm weapon gets +2 POWER: 2 + 2 = 4')
    ->expectCardExists('myField', 'Fang of Dragon\'s Breath', 'The phantasia entered linked to the weapon')
    ->tested('Fang of Dragon\'s Breath')
    ->covers(['weapon-link', 'stat-change', 'phantasia'], '**Polearm Weapon Link**', 'Linked weapon gets +2 [POWER].')
    ->register();

gaScenario('fang-of-dragons-breath-needs-a-polearm-weapon-to-link-to')
    ->standardDeck('Fang of Dragon\'s Breath')
    ->readyFor('Fang of Dragon\'s Breath')
    ->seed('myField', 'Firetongue')
    ->seed('myHand', 'Fang of Dragon\'s Breath')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Firetongue is not a Polearm: there is nothing to link to, so the card was not activated')
    ->tested('Fang of Dragon\'s Breath')
    ->covers(['weapon-link', 'restriction'], '**Polearm Weapon Link**')
    ->register();

gaScenario('aithne-spirit-of-fire-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Aithne, Spirit of Fire')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Aithne, Spirit of Fire')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('hanabi-spirit-of-fire-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Hanabi, Spirit of Fire')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Hanabi, Spirit of Fire')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('vyra-spirit-of-fire-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Vyra, Spirit of Fire')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Vyra, Spirit of Fire')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('fragmented-spirit-of-fire-enter-glimpses-draws-six-and-summons-a-spirit-shard')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Fragmented Spirit of Fire')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->expectCardExists('myField', 'Spirit Shard', 'Summoned a Spirit Shard token')
    ->tested('Fragmented Spirit of Fire')
    ->covers(['on-enter', 'glimpse', 'draw', 'summon', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards. Then **summon** a Spirit Shard token.')
    ->register();

gaScenario('spirit-of-fortuitous-fire-enter-glimpses-and-draws-seven-into-memory')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Fortuitous Fire')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->expectZoneCount('myMemory', 7, 1, 'Drew seven cards into memory')
    ->expectZoneCount('myHand', 7, 1, 'The hand is untouched: the cards went to memory')
    ->tested('Spirit of Fortuitous Fire')
    ->covers(['on-enter', 'glimpse', 'draw-into-memory', 'champion'], '**On Enter**: **Glimpse 7**. Draw seven cards into your memory.')
    ->register();

gaScenario('spirit-of-serene-fire-enter-glimpses-and-draws-six')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Serene Fire')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->tested('Spirit of Serene Fire')
    ->covers(['on-enter', 'glimpse', 'draw', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards.')
    ->register();

gaScenario('spirit-of-serene-fire-lineage-release-recovers-six')
    ->standardDeck('Dungeon Guide')
    ->champion(lineage: ['Spirit of Serene Fire'], damage: 8)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 2, 'Lineage Release -- Recover 6: 8 damage down to 2')
    ->tested('Spirit of Serene Fire')
    ->covers(['lineage-release', 'recover'], '**Lineage Release** -- **Recover 6**.')
    ->register();
