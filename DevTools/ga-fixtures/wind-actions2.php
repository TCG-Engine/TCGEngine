<?php
/**
 * Phase 3b (Wind): actions, second file -- cards with alternate or additional costs, reactions and Potion interactions.
 */

// ---- Ensnaring Fumes ----

gaScenario('ensnaring-fumes-returns-every-ally-to-its-owners-hand')
    ->standardDeck('Ensnaring Fumes')
    ->readyFor('Ensnaring Fumes')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Ensnaring Fumes')
    ->castSpell('Ensnaring Fumes', pay: 5, window: 'me')
    ->expectCardExists('myHand', 'Giant Tortoise', 'Our ally returned to our hand')
    ->expectZoneCount('myField', 1, 1, 'Only our champion remains')
    ->expectZoneCount('theirField', 1, 1, 'The opposing ally left the field too: only their champion remains')
    ->expectZoneCount('theirHand', 8, 1, 'It returned to ITS owner\'s hand (7 + 1)')
    ->tested('Ensnaring Fumes')
    ->covers(['zone-movement', 'cost'], 'Return all allies to their owner\'s hands.')
    ->register();

gaScenario('ensnaring-fumes-three-preparation-counters-replace-the-reserve-cost')
    ->standardDeck('Ensnaring Fumes')
    ->readyFor('Ensnaring Fumes')
    ->patch('myField-0', ['Counters' => ['preparation' => 3]])
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Ensnaring Fumes')
    ->play('Ensnaring Fumes')->answer(1, 'YES')->pass(1)
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The three preparation counters were removed')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve card was paid')
    ->expectCardExists('myHand', 'Giant Tortoise', 'The effect still returned the ally to hand')
    ->tested('Ensnaring Fumes')
    ->covers(['alternate-cost', 'counter', 'zone-movement'], 'You may remove three **preparation** counters from your champion rather than pay this card\'s reserve cost.')
    ->register();

gaScenario('ensnaring-fumes-declining-the-alternate-cost-pays-the-reserve-cost')
    ->standardDeck('Ensnaring Fumes')
    ->readyFor('Ensnaring Fumes')
    ->patch('myField-0', ['Counters' => ['preparation' => 3]])
    ->seed('myHand', 'Ensnaring Fumes')
    ->play('Ensnaring Fumes')->answer(1, 'NO')->pay(5)->pass(1)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":3}', 'The preparation counters were kept')
    ->expectZoneCount('myMemory', 5, 1, 'The reserve cost 5 was paid instead')
    ->tested('Ensnaring Fumes')
    ->covers(['alternate-cost', 'optional-choice-decline'], 'You may remove three **preparation** counters from your champion rather than pay this card\'s reserve cost.')
    ->register();

gaScenario('ensnaring-fumes-two-preparation-counters-are-not-enough-for-the-alternate-cost')
    ->standardDeck('Ensnaring Fumes')
    ->readyFor('Ensnaring Fumes')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myHand', 'Ensnaring Fumes')
    ->castSpell('Ensnaring Fumes', pay: 5, window: 'me')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'No alternate cost was offered: the counters stay')
    ->expectZoneCount('myMemory', 5, 1, 'The reserve cost 5 was paid')
    ->tested('Ensnaring Fumes')
    ->covers(['alternate-cost', 'restriction'], 'You may remove three **preparation** counters from your champion rather than pay this card\'s reserve cost.')
    ->register();

// ---- Primeval Ritual ----

gaScenario('primeval-ritual-sacrifices-an-ally-and-returns-a-wind-card-to-memory')
    ->standardDeck('Primeval Ritual')
    ->readyFor('Primeval Ritual')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myGraveyard', 'Aenean Reclaim')
    ->seed('myHand', 'Primeval Ritual')
    ->play('Primeval Ritual')->answer(1, 'myField-1')->pay(3)->pass(1)->answer(1, 'myGraveyard-0')
    ->expectCardExists('myGraveyard', 'Giant Tortoise', 'The sacrificed ally is in the graveyard')
    ->expectCardExists('myMemory', 'Aenean Reclaim', 'The wind card returned from the graveyard to memory')
    ->expectZoneCount('myField', 1, 1, 'The ally was sacrificed: only the champion remains')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid cards plus the returned card')
    ->tested('Primeval Ritual')
    ->covers(['additional-cost', 'sacrifice', 'zone-movement'], 'As an additional cost to activate this card, sacrifice an ally.', 'Return target wind element card from your graveyard to your memory.')
    ->register();

gaScenario('primeval-ritual-cannot-be-activated-without-an-ally-to-sacrifice')
    ->standardDeck('Primeval Ritual')
    ->readyFor('Primeval Ritual')
    ->seed('myGraveyard', 'Aenean Reclaim')
    ->seed('myHand', 'Primeval Ritual')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Nothing happened: the additional cost cannot be paid')
    ->expectZoneCount('myGraveyard', 1, 1, 'The graveyard card stayed put')
    ->tested('Primeval Ritual')
    ->covers(['additional-cost', 'restriction'], 'As an additional cost to activate this card, sacrifice an ally.')
    ->register();

// ---- Innervate Agility ----

gaScenario('innervate-agility-delevels-recovers-five-and-grants-stealth')
    ->standardDeck('Innervate Agility')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Wind'], level: 1, damage: 5)
    ->seed('myHand', 'Innervate Agility')
    ->castSpell('Innervate Agility', pay: 2, window: 'me')->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'CardID', 'pNiyaGlIe7', 'Delevel: the top card of the lineage became the champion')
    ->expectDamage('p1Field-0', 0, 'Recover 5: 5 damage became 0')
    ->expectGlobalEffect(1, 'INNERVATE_STEALTH', 'YES chose stealth for the units you control')
    ->tested('Innervate Agility')
    ->covers(['additional-cost', 'delevel', 'recover', 'stealth'], 'As an additional cost to activate this card, **delevel** your champion and **recover 5**. *(To **delevel** your champion, return the top card of its lineage to its owner\'s material deck.)*', 'Units you control gain your choice of **stealth** or **spellshroud** until end of turn.')
    ->register();

gaScenario('innervate-agility-spellshroud-choice-grants-spellshroud')
    ->standardDeck('Innervate Agility')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Wind'], level: 1, damage: 5)
    ->seed('myHand', 'Innervate Agility')
    ->castSpell('Innervate Agility', pay: 2, window: 'me')->answer(1, 'NO')
    ->expectGlobalEffect(1, 'INNERVATE_SPELLSHROUD', 'NO chose spellshroud for the units you control')
    ->tested('Innervate Agility')
    ->covers(['spellshroud', 'optional-choice'], 'Units you control gain your choice of **stealth** or **spellshroud** until end of turn.')
    ->register();

gaScenario('innervate-agility-cannot-be-activated-by-a-champion-with-no-lineage')
    ->standardDeck('Innervate Agility')
    ->champion(cardName: 'Spirit of Wind')
    ->seed('myHand', 'Innervate Agility')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Nothing happened: a level 0 champion cannot pay the delevel cost')
    ->tested('Innervate Agility')
    ->covers(['additional-cost', 'restriction'], 'As an additional cost to activate this card, **delevel** your champion and **recover 5**.')
    ->register();

// ---- Cry for Help (a Reaction) ----

if (!function_exists('gaCryForHelp')) {
    // P2's Giant Tortoise attacks P1's champion; P1 answers with Cry for Help and redirects the attack to P1's own Giant Tortoise.
    function gaCryForHelp(string $slug, bool $classBonus, callable $assert): void {
        $s = gaScenario($slug)->standardDeck('Cry for Help')->readyFor('Cry for Help', classBonus: $classBonus)
            ->seed('myHand', 'Cry for Help')
            ->seed('myField', 'Giant Tortoise')
            ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
            ->endTurn()->pass(1)->pass(1)
            ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(1)->pass(1)->answer(1, 'myField-1')
            ->pass(1)->answer(1, 'myField-1');
        $assert($s);
        $s->tested('Cry for Help')->register();
    }
}

gaCryForHelp('cry-for-help-class-bonus-redirects-the-attack-to-an-ally-and-gives-it-plus-one-life', true, fn($s) => $s
    ->expectProperty('p1Field-0', 'Damage', '0', 'The champion took no damage: the attack was redirected')
    ->expectProperty('p1Field-1', 'Damage', '1', 'The redirected ally took the 1 damage instead')
    ->expectLife('p1Field-1', 7, 'Class Bonus: the ally got +1 LIFE until end of turn (6 + 1)')
    ->covers(['redirect', 'combat', 'class-bonus'], 'Change the target of an attack that targets your champion to an ally you control. **Class Bonus:** That ally gets +1 [LIFE] until end of turn.'));

gaCryForHelp('cry-for-help-without-the-class-bonus-redirects-without-extra-life', false, fn($s) => $s
    ->expectProperty('p1Field-0', 'Damage', '0', 'The champion took no damage: the attack was redirected')
    ->expectProperty('p1Field-1', 'Damage', '1', 'The redirected ally took the 1 damage')
    ->expectLife('p1Field-1', 6, 'No Class Bonus: the ally keeps its printed 6 LIFE')
    ->covers(['redirect', 'class-bonus'], '**Class Bonus:** That ally gets +1 [LIFE] until end of turn.'));
