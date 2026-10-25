<?php
gaScenario('dilu-exalted-wind-unique-human-removes-pride')
    ->deckTemplate('quiet', 'Dilu, Auspicious Charger')
    ->readyFor('Dilu, Auspicious Charger', classBonus: true, level: 1)
    ->seed('myField', 'Dilu, Auspicious Charger')
    ->seed('myField', 'Parcenet, Royal Maid', properties: ['Counters' => ['_overrides' => ['element' => 'EXALTED,WIND']]])
    ->reachTurnThree(fastCardInHand: true)
    ->attack('myField-1', 'theirField-0')->pass(1)
    ->expectDamage('p2Field-0', 3, 'Effective Exalted Wind satisfies the unique Human condition at level one')
    ->tested('Dilu, Auspicious Charger')->covers(['pride', 'element', 'effective-property'], 'A Wind unique Human ally removes pride, including effective dual elements.')->register();

gaScenario('red-hare-effective-exalted-fire-enables-pride-and-discard-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Red Hare, Unrivaled Stallion', level: 1)
    ->seed('myField', 'Red Hare, Unrivaled Stallion')
    ->seed('myField', 'Parcenet, Royal Maid', properties: ['Counters' => ['_overrides' => ['element' => 'EXALTED,FIRE']]])
    ->seed('myHand', 'Scorched Conquest')->reachTurnThree(fastCardInHand: true)
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->answer(1, 'myHand-7')->pass(1)
    ->expectCardExists('myGraveyard', 'Scorched Conquest', 'The granted On Attack discard was paid')
    ->expectDamage('p2Field-0', 3, 'Effective Fire removes pride at level one')
    ->tested('Red Hare, Unrivaled Stallion')->covers(['pride', 'on-attack', 'element', 'effective-property'], 'The same Fire or Tera unique Human condition controls both clauses.')->register();
