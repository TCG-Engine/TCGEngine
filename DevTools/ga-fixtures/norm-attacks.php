<?php
/**
 * Phase 3d (Norm): attack cards. Player 2 plays the attack on turn 2 (no recollection before it, so seeded memory and rested units persist) and targets player 1's side. 'quiet' deck.
 */

if (!function_exists('gaNormAttackCard')) {
    // $seeds1: [zone, card, props] seeded for player 1 (the defender); $targetMz: the target as player 2 sees it; $retaliate: player 1 declines the retaliation prompt.
    function gaNormAttackCard(string $slug, string $card, bool $classBonus, array $seeds1, string $targetMz, callable $observe, array $mechanics, string ...$clauses): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($card, classBonus: $classBonus, player: 2);
        foreach ($seeds1 as $seed) $s->seed($seed[0], $seed[1], properties: $seed[2] ?? []);
        $s->seed('myHand', $card, player: 2)->endTurn();
        $s->play($card, 2)->pay(CardCost_reserve(GaScenario::id($card)), 2)->target($targetMz, 2);
        $observe($s);
        $s->tested($card)->covers($mechanics, ...$clauses)->register();
    }
}

gaNormAttackCard('backstab-class-bonus-attacking-a-rested-unit-gets-plus-two-power', 'Backstab', true,
    [['myField', 'Giant Tortoise', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectDamage('p1Field-1', 4, '[Class Bonus] A rested unit: 2 + 2 = 4 damage'),
    ['class-bonus', 'condition', 'stat-change', 'combat'], '[Class Bonus] As long as your champion is attacking a rested unit, CARDNAME gets +2 [POWER].');

gaNormAttackCard('crowdguards-slash-class-bonus-against-three-units-gets-plus-two-power', 'Crowdguard\'s Slash', true,
    [['myField', 'Dungeon Guide'], ['myField', 'Dungeon Guide']], 'theirField-0',
    fn($s) => $s->expectDamage('p1Field-0', 6, '[Class Bonus] The opponent controls three units: 4 + 2 = 6 damage'),
    ['class-bonus', 'condition', 'stat-change', 'combat'], '[Class Bonus] As long as an opponent controls three or more units, CARDNAME gets +2[POWER].');

gaNormAttackCard('crowdguards-slash-class-bonus-against-two-units-has-its-printed-power', 'Crowdguard\'s Slash', true,
    [['myField', 'Dungeon Guide']], 'theirField-0',
    fn($s) => $s->expectDamage('p1Field-0', 4, 'Only two units: the printed 4 damage'),
    ['class-bonus', 'condition', 'combat'], '[Class Bonus] As long as an opponent controls three or more units, CARDNAME gets +2[POWER].');

gaNormAttackCard('off-with-her-head-attacking-a-unique-ally-gets-plus-three-power', 'Off With Her Head', false,
    [['myField', 'Geldus, Terror of Dorumegia', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectDamage('p1Field-1', 5, 'A unique ally: 2 + 3 = 5 damage'),
    ['condition', 'stat-change', 'combat'], 'As long as the attacker is attacking a unique ally, CARDNAME gets +3[POWER].');

gaNormAttackCard('off-with-her-head-attacking-a-non-unique-ally-has-its-printed-power', 'Off With Her Head', false,
    [['myField', 'Giant Tortoise', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectDamage('p1Field-1', 2, 'Not unique: the printed 2 damage'),
    ['condition', 'combat'], 'As long as the attacker is attacking a unique ally, CARDNAME gets +3[POWER].');

gaNormAttackCard('seeking-shot-attacking-a-human-ally-gets-plus-three-power', 'Seeking Shot', false,
    [['myField', 'Dungeon Guide', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectCardExists('myGraveyard', 'Dungeon Guide', 'A Human ally: 1 + 3 = 4 damage destroyed the 3-life Guide'),
    ['condition', 'stat-change', 'combat'], 'As long as the attacker is attacking a Human ally, CARDNAME gets +3 [POWER].');

gaNormAttackCard('synthetic-strike-attacking-an-automaton-gets-plus-one-power', 'Synthetic Strike', false,
    [['myField', 'Delivery Droid', ['Status' => 1, 'Counters' => ['buff' => 3]]]], 'theirField-1',
    fn($s) => $s->expectCardExists('myGraveyard', 'Delivery Droid', 'An Automaton: 3 + 1 = 4 damage destroyed the 4-life Droid'),
    ['condition', 'stat-change', 'combat'], 'As long as the attacker is attacking an Automaton unit, CARDNAME gets +1 [POWER].');

gaNormAttackCard('vicious-slice-class-bonus-attacking-a-human-gets-plus-one-power', 'Vicious Slice', true,
    [['myField', 'Dungeon Guide', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectCardExists('myGraveyard', 'Dungeon Guide', '[Class Bonus] A Human: 2 + 1 = 3 damage destroyed the 3-life Guide'),
    ['class-bonus', 'condition', 'stat-change', 'combat'], '[Class Bonus] As long as the attacker is attacking a Human, CARDNAME gets +1 [POWER].');

gaNormAttackCard('vicious-slice-without-the-class-bonus-attacking-a-human-has-its-printed-power', 'Vicious Slice', false,
    [['myField', 'Dungeon Guide', ['Status' => 1]]], 'theirField-1',
    fn($s) => $s->expectDamage('p1Field-1', 2, 'No class bonus: the printed 2 damage'),
    ['class-bonus', 'condition', 'combat'], '[Class Bonus] As long as the attacker is attacking a Human, CARDNAME gets +1 [POWER].');

gaScenario('staggering-strike-on-champion-hit-removes-three-preparation-counters-from-the-hit-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Staggering Strike', player: 2)
    ->patch('myField-0', ['Counters' => ['preparation' => 4]])
    ->seed('myHand', 'Staggering Strike', player: 2)
    ->endTurn()
    ->play('Staggering Strike', 2)->pay(3, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 3, 'The printed 3 damage')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1,"_champDamageThisTurn":3}', 'On Champion Hit: three of the four preparation counters were removed')
    ->tested('Staggering Strike')
    ->covers(['on-champion-hit', 'counter', 'combat', 'keyword', 'true-sight'], '**On Champion Hit:** Remove three **preparation** counters from the hit champion.')
    ->register();

gaNormAttackCard('reckless-slash-deals-its-printed-three-damage', 'Reckless Slash', false,
    [], 'theirField-0',
    fn($s) => $s->expectDamage('p1Field-0', 3, 'The printed 3 damage'),
    ['vanilla'], 'Reckless Slash (no rules text)');

gaScenario('subjugating-lash-with-twelve-damage-on-the-champion-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Subjugating Lash', damage: 12, player: 2)
    ->seed('myHand', 'Subjugating Lash', player: 2)
    ->endTurn()
    ->play('Subjugating Lash', 2)->pay(2, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 4, 'Twelve damage counters on your champion: 2 + 2 = 4 damage')
    ->tested('Subjugating Lash')
    ->covers(['condition', 'stat-change', 'combat'], 'As long as your champion has twelve or more damage counters on them, CARDNAME gets +2 [POWER].')
    ->register();

gaScenario('subjugating-lash-below-twelve-damage-has-its-printed-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Subjugating Lash', damage: 11, player: 2)
    ->seed('myHand', 'Subjugating Lash', player: 2)
    ->endTurn()
    ->play('Subjugating Lash', 2)->pay(2, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 2, 'Eleven damage counters: the printed 2 damage')
    ->tested('Subjugating Lash')
    ->covers(['condition', 'combat'], 'As long as your champion has twelve or more damage counters on them, CARDNAME gets +2 [POWER].')
    ->register();
