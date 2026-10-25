<?php
// Exercise Ephemerate in an actual defending response window.
gaScenario('graveyard-reaction-twingale-during-attack')
    ->standardDeck('Twingale Parry')->readyFor('Twingale Parry', classBonus: true)
    ->seed('myGraveyard', 'Twingale Parry')->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myField', 'Suzaku, Vermillion Phoenix', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myGraveyard-0')->pay(2)->pass(1)->answer(1, 'myField-0')->pass(1)->pass(1)
    ->expectDamage('p1Field-0', 2, 'The graveyard Reaction prevents two combat damage')
    ->expectCardExists('myBanish', 'Twingale Parry', 'The Ephemerated Reaction resolves into banishment')
    ->expectZoneCount('myMemory', 2, 1, 'The Ephemerate cost was paid in the response window')
    ->tested('Twingale Parry')->covers(['ephemerate', 'reaction', 'combat'], 'Ephemerate (2) while controlling a Sword weapon.')->register();
