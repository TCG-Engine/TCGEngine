<?php
/**
 * Phase 3b (Wind): the Vanitas champions. Their lineage is "Vanitas, Obliviate Schemer" (level 1) under the tested champion.
 */

gaScenario('vanitas-convergent-ruin-gets-plus-one-power-on-its-next-attack-after-a-spell')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Vanitas, Convergent Ruin', lineage: ['Vanitas, Obliviate Schemer', 'Spirit of Wind'], level: 2)
    ->seed('myHand', 'Nascent Blast')
    ->reachTurnThree(fastCardInHand: true)
    ->play('Nascent Blast')->pay(3)->target('theirField-0')
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')
    ->expectDamage('p2Field-0', 5, 'Nascent Blast 3 + the champion attack 1 + 1 (a Spell was activated) = 5')
    ->tested('Vanitas, Convergent Ruin')
    ->covers(['champion', 'trigger', 'stat-change', 'combat'], 'Whenever you activate a Spell card, CARDNAME\' next attack without a weapon this turn gets +1 [POWER].')
    ->register();

gaScenario('vanitas-convergent-ruin-without-a-spell-attacks-for-its-printed-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Vanitas, Convergent Ruin', lineage: ['Vanitas, Obliviate Schemer', 'Spirit of Wind'], level: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')
    ->expectDamage('p2Field-0', 1, 'No Spell: only the printed 1 power')
    ->tested('Vanitas, Convergent Ruin')
    ->covers(['champion', 'trigger', 'condition'], 'Whenever you activate a Spell card')
    ->register();

gaScenario('vanitas-dominus-rex-on-champion-hit-makes-the-opponents-materializations-cost-one-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Vanitas, Dominus Rex', lineage: ['Vanitas, Obliviate Schemer', 'Vanitas, Convergent Ruin', 'Spirit of Wind'], level: 3)
    ->seed('myField', 'Zephyr\'s Edge')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->expectGlobalEffect(1, '3vkxrw9462', 'On Champion Hit: the opponent\'s materializations cost 1 more until my next turn')
    ->tested('Vanitas, Dominus Rex')
    ->covers(['champion', 'on-champion-hit', 'cost-increase'], '**On Champion Hit**: Cards that opponent materializes cost 1 more to materialize until the beginning of your next turn.')
    ->register();
