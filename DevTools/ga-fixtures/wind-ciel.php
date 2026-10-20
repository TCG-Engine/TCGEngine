<?php
/**
 * Phase 3b (Wind): the [Ciel Bonus] cards. The champion is Ciel, Loyal Valet (level 1, Guardian) over a Spirit of Wind (which enables the wind element);
 * IsCielBonusActive() only looks at the champion's name.
 */

function gaCiel(string $slug, string $mainCard): GaScenario {
    return gaScenario($slug)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Ciel, Loyal Valet', lineage: ['Spirit of Wind']);
}

// ---- Ouragan Sentinel ----

gaCiel('ouragan-sentinel-ciel-bonus-on-enter-summons-a-vacuous-servant', 'Ouragan Sentinel')
    ->seed('myHand', 'Ouragan Sentinel')
    ->play('Ouragan Sentinel')->pay(4)
    ->expectCardExists('myField', 'Vacuous Servant', '[Ciel Bonus] On Enter: a Vacuous Servant token was summoned')
    ->tested('Ouragan Sentinel')
    ->covers(['on-enter', 'champion-bonus', 'token', 'summon'], '[Ciel Bonus] **On Enter**: **Summon** a Vacuous Servant token.')
    ->register();

gaScenario('ouragan-sentinel-without-ciel-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ouragan Sentinel')
    ->seed('myHand', 'Ouragan Sentinel')
    ->play('Ouragan Sentinel')->pay(4)
    ->expectZoneCount('myField', 2, 1, 'Not Ciel: no token')
    ->tested('Ouragan Sentinel')
    ->covers(['on-enter', 'champion-bonus', 'condition'], '[Ciel Bonus] **On Enter**')
    ->register();

gaWindRanged('ouragan-sentinel-ranged-two-while-distant', 'Ouragan Sentinel', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');

gaScenario('ouragan-sentinel-rests-to-suppress-an-ally-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ouragan Sentinel')
    ->seed('myField', 'Ouragan Sentinel', properties: ['TurnEffects' => ['DISTANT']])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->expectCardExists('theirBanish', 'Dungeon Guide', 'The ally was suppressed', 1)
    ->expectProperty('p1Field-1', 'Status', '1', 'The Sentinel rested for the ability')
    ->tested('Ouragan Sentinel')
    ->covers(['activated-ability', 'rest', 'suppress', 'distant', 'targeting'], '[REST]**: Suppress** target ally, item, or weapon. Activate this ability only if CARDNAME is **distant**.')
    ->register();

// ---- Vacuous Call ----

gaScenario('vacuous-call-on-enter-summons-a-vacuous-servant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vacuous Call')
    ->seed('myHand', 'Vacuous Call')
    ->play('Vacuous Call')->pay(3)
    ->expectCardExists('myField', 'Vacuous Servant', 'On Enter: a Vacuous Servant token was summoned')
    ->tested('Vacuous Call')
    ->covers(['on-enter', 'token', 'summon'], '**On Enter:** **Summon** a Vacuous Servant token.')
    ->register();

gaCiel('vacuous-call-ciel-bonus-pays-two-discards-an-ally-and-sacrifices-itself-for-a-servant', 'Vacuous Call')
    ->seed('myField', 'Vacuous Call')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myHand-0')->pay(2)
    ->expectZoneCount('myField', 3, 1, 'The seeded Vacuous Call summoned one Servant on enter; the ability summoned a second')
    ->expectCardExists('myField', 'Vacuous Servant', '[Ciel Bonus] A Vacuous Servant token was summoned')
    ->expectCardExists('myGraveyard', 'Vacuous Call', 'Vacuous Call was sacrificed')
    ->tested('Vacuous Call')
    ->covers(['activated-ability', 'champion-bonus', 'discard', 'sacrifice', 'token', 'summon'], '[Ciel Bonus] (2)**, Discard an ally card, sacrifice CARDNAME:** **Summon** a Vacuous Servant token.')
    ->register();

// ---- Effluve Guard ----

gaCiel('effluve-guard-ciel-bonus-as-an-omen-gives-vacuous-servants-vigor', 'Effluve Guard')
    ->seed('myBanish', 'Effluve Guard', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Vacuous Servant', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'The Effluve Guard is an omen: the rested Vacuous Servant has vigor and woke at the end phase')
    ->tested('Effluve Guard')
    ->covers(['champion-bonus', 'omen', 'vigor', 'static-ability'], '[Ciel Bonus] As long as this card is an **omen**, allies named Vacuous Servants you control have **vigor**.')
    ->register();

gaCiel('effluve-guard-not-an-omen-gives-vacuous-servants-no-vigor', 'Effluve Guard')
    ->seed('myField', 'Effluve Guard')
    ->seed('myField', 'Vacuous Servant', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-2', 'Status', '1', 'Not an omen: the Servant stays rested')
    ->tested('Effluve Guard')
    ->covers(['champion-bonus', 'omen', 'condition'], 'As long as this card is an **omen**')
    ->register();

// ---- Vacuous Servant ----

gaCiel('vacuous-servant-ciel-bonus-gets-plus-one-power-per-attack-omen-and-plus-one-life-per-ally-omen', 'Vacuous Servant')
    ->seed('myField', 'Vacuous Servant')
    ->seed('myBanish', 'Crosswind Cuts', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Crosswind Cuts', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->endTurn()
    ->expectPower('p1Field-1', 3, 'Two attack omens: 1 + 2 power')
    ->expectLife('p1Field-1', 2, 'One ally omen: 1 + 1 life')
    ->tested('Vacuous Servant')
    ->covers(['champion-bonus', 'omen', 'stat-change'], '[Ciel Bonus] CARDNAME gets +1 [POWER] for each attack **omen** you have and +1 [LIFE] for each ally **omen** you have.')
    ->register();

gaScenario('vacuous-servant-without-ciel-has-its-printed-stats')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vacuous Servant')
    ->seed('myField', 'Vacuous Servant')
    ->seed('myBanish', 'Crosswind Cuts', properties: ['Counters' => ['omen' => 1]])
    ->endTurn()
    ->expectPower('p1Field-1', 1, 'Not Ciel: 1 power')
    ->expectLife('p1Field-1', 1, 'Not Ciel: 1 life')
    ->tested('Vacuous Servant')
    ->covers(['champion-bonus', 'omen', 'condition'], '[Ciel Bonus]')
    ->register();

// ---- Rafale's Slash / Visage's Strike ----

// Ciel has no power of her own, so the attack cards are played with a weapon (Zephyr's Edge) and Ciel's end-phase inherited effect prompt is declined after turn 1.
gaCiel('rafales-slash-ciel-bonus-on-attack-pays-three-to-summon-a-rested-servant', 'Rafale\'s Slash')
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Rafale\'s Slash')
    ->endTurn()->pass(1)->endTurn(2)->pass(1)
    ->play('Rafale\'s Slash')->pay(2)->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'YES')->pay(3)
    ->expectCardExists('myField', 'Vacuous Servant', '[Ciel Bonus] On Attack: paid (3), a Vacuous Servant was summoned')
    ->expectProperty('p1Field-2', 'Status', '1', 'It was summoned rested')
    ->expectZoneCount('myMemory', 5, 1, 'Two for the card and three for the On Attack')
    ->tested('Rafale\'s Slash')
    ->covers(['on-attack', 'champion-bonus', 'optional-cost', 'token', 'summon'], '[Ciel Bonus] **On Attack**: You may pay (3). If you do, **summon** a Vacuous Servant token rested.')
    ->register();

gaCiel('rafales-slash-ciel-bonus-on-attack-declined-summons-nothing', 'Rafale\'s Slash')
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Rafale\'s Slash')
    ->endTurn()->pass(1)->endTurn(2)->pass(1)
    ->play('Rafale\'s Slash')->pay(2)->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'NO')
    ->expectZoneCount('myField', 2, 1, 'Declined: no token (the champion and the weapon)')
    ->tested('Rafale\'s Slash')
    ->covers(['on-attack', 'champion-bonus', 'optional-cost'], 'You may pay (3).')
    ->register();

gaCiel('visages-strike-ciel-bonus-on-hit-banishes-itself-as-an-omen', 'Visage\'s Strike')
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myHand', 'Visage\'s Strike')
    ->endTurn()->pass(1)->endTurn(2)->pass(1)
    ->play('Visage\'s Strike')->pay(2)->answer(1, 'myField-1')->answer(1, 'theirField-0')
    ->expectCardExists('myBanish', 'Visage\'s Strike', '[Ciel Bonus] On Hit with two or fewer omens: the Strike was banished')
    ->expectProperty('p1Banish-0', 'Counters', '{"omen":1}', 'It carries an omen counter')
    ->tested('Visage\'s Strike')
    ->covers(['on-hit', 'champion-bonus', 'omen', 'banish', 'condition'], '[Ciel Bonus] **On Hit: ** If you have two or less **omens**, banish CARDNAME and put an **omen** counter on it.')
    ->register();

gaCiel('visages-strike-ciel-bonus-with-three-omens-goes-to-the-graveyard', 'Visage\'s Strike')
    ->seed('myField', 'Zephyr\'s Edge')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Visage\'s Strike')
    ->endTurn()->pass(1)->endTurn(2)->pass(1)
    ->play('Visage\'s Strike')->pay(2)->answer(1, 'myField-1')->answer(1, 'theirField-0')
    ->expectCardExists('myGraveyard', 'Visage\'s Strike', 'Three omens: the Strike went to the graveyard as usual')
    ->tested('Visage\'s Strike')
    ->covers(['on-hit', 'champion-bonus', 'omen', 'condition'], 'If you have two or less **omens**')
    ->register();
