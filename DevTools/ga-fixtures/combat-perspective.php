<?php
// Computed stats must agree when the defending player views the attacker's objects.
gaScenario('combat-perspective-opposing-horned-knight')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Horned Knight')->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Horned Knight', player: 2)
    ->dqVariables(['CombatAttacker' => 'myField-1', 'CombatAttackerPlayer' => '2', 'CombatTarget' => 'theirField-2'])
    ->observe()->expectPower('p2Field-1', 3, 'The opposing attacker gets its ally-target bonus')
    ->expectPower('p1Field-1', 2, 'The same-index defending Knight is not attacking')
    ->tested('Horned Knight')->covers(['combat', 'power', 'perspective'], 'While attacking an ally, this gets one additional power.')->register();

gaScenario('combat-perspective-opposing-golden-rook')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Golden Rook')->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Golden Rook', player: 2)
    ->dqVariables(['CombatAttacker' => 'myField-1', 'CombatAttackerPlayer' => '2', 'CombatTarget' => 'theirField-2'])
    ->observe()->expectPower('p2Field-1', 2, 'The opposing attacker sees the even-life target')
    ->expectPower('p1Field-1', 1, 'The defending Rook gets no attacking bonus')
    ->tested('Golden Rook')->covers(['combat', 'power', 'perspective'], 'While attacking a unit with even life, this gets one additional power.')->register();

gaScenario('combat-perspective-opposing-debuff')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Horned Knight', player: 2, properties: ['TurnEffects' => ['y7wbtbasch-debuff']])
    ->dqVariables(['CombatAttacker' => 'myField-1', 'CombatAttackerPlayer' => '2', 'CombatTarget' => 'theirField-0'])
    ->observe()->expectPower('p2Field-1', -1, 'The opposing attacker retains the minus-three attack debuff')
    ->tested('Dissuading Halt')->covers(['combat', 'power', 'perspective'], 'The target unit attacks with three less power.')->register();

// A defender must not read the attacker's relative target as its own opponent.
gaScenario('combat-perspective-samaritan-no-favor')
    ->deck(GA_KONGMING_BASE_DECK)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', "Samaritan's Reach")
    ->endTurn()->passBoth()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pass(2)
    ->play("Samaritan's Reach")->target('myHand-7')->pay(3)
    ->expectDamage('theirField-1', 3, 'The opposing attacking ally takes three damage')
    ->expectGlobalEffect(1, 'gpmJdGYqoC', 'Defending your own champion does not grant Crowds Favor', count: 0)
    ->tested("Samaritan's Reach")->covers(['combat', 'perspective', 'status'], 'Gain Crowds Favor only when the attacked unit is not controlled by the caster.')->register();

gaScenario('combat-perspective-hymn-defending-redirect')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor("Hymn of Gaia's Grace", level: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', "Hymn of Gaia's Grace")->seed('myHand', 'Woodland Squirrels')
    ->endTurn()->passBoth()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pass(2)
    ->play("Hymn of Gaia's Grace")->target('myHand-7')->pay(3)
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(1, 'myHand-4')
    ->answer(1, 'YES')
    ->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 0, 'The champion is protected by the redirected attack')
    ->expectDamage('p1Field-1', 1, 'The entered ally takes the redirected combat damage')
    ->tested("Hymn of Gaia's Grace")->covers(['combat', 'perspective', 'redirect'], 'Redirect the attack from your champion to the entered ally.')
    ->register();

gaScenario('combat-perspective-hymn-declines-redirect')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor("Hymn of Gaia's Grace", level: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', "Hymn of Gaia's Grace")->seed('myHand', 'Woodland Squirrels')
    ->endTurn()->passBoth()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->pass(2)
    ->play("Hymn of Gaia's Grace")->target('myHand-7')->pay(3)
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(1, 'myHand-4')
    ->answer(1, 'NO')
    ->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 1, 'Declining keeps the attack on the champion')
    ->expectDamage('p1Field-1', 0, 'Declining leaves the entered ally undamaged')
    ->tested("Hymn of Gaia's Grace")->covers(['combat', 'perspective', 'redirect'], 'Redirect the attack from your champion to the entered ally.')
    ->register();
