<?php
// Omens retain all printed elements, including the basic element of Exalted cards.
foreach (['dual' => ['Noble Dissolution', 7], 'mixed' => ['Aenean Reclaim', 7], 'not-omen' => ['Noble Dissolution', 6]] as $case => [$card, $life]) {
    gaScenario('omen-elements-wind-' . $case)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Favorable Omens')
        ->seed('myBanish', $card, properties: ['Counters' => ['omen' => $case === 'not-omen' ? 0 : 1]])
        ->seed('myBanish', 'Scorched Conquest', properties: ['Counters' => ['omen' => 1]])
        ->seed('myField', 'Giant Tortoise')->seed('myHand', 'Favorable Omens')
        ->castSpell('Favorable Omens', pay: 1, window: 'me')
        ->expectLife('p1Field-1', $life, 'Only Wind cards carrying omen counters grant life')
        ->tested('Favorable Omens')->covers(['omen', 'element', 'stat-change'], 'For each wind element omen, allies get +1 LIFE.')->register();
}
foreach (['dual' => ['Noble Dissolution', 1], 'below-threshold' => ['Scorched Conquest', 0]] as $case => [$card, $suppressed]) {
    $s = gaScenario('omen-elements-chrysalis-' . $case)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Ciel, Loyal Valet', lineage: ['Spirit of Wind'])
        ->seed('myBanish', 'Aenean Reclaim', properties: ['Counters' => ['omen' => 1]])
        ->seed('myBanish', $card, properties: ['Counters' => ['omen' => 1]])
        ->seed('myField', 'Giant Tortoise', player: 2)
        ->seed('myHand', 'Chrysalis, Hazy Caterpillar')
        ->play('Chrysalis, Hazy Caterpillar')->pay(2);
    if ($suppressed) $s->target('theirField-1');
    $s->expectZoneCount('theirBanish', $suppressed, 1, 'Two Wind omens enable suppression; one does not')
        ->tested('Chrysalis, Hazy Caterpillar')->covers(['omen', 'element', 'condition', 'suppress'], 'Ciel Bonus On Enter: two or more wind omens enable suppression.')->register();
}
foreach (['dual' => ['Scorched Conquest', 1], 'ordinary' => ['Red Slime', 1], 'non-fire' => ['Noble Dissolution', 5]] as $case => [$card, $cost]) {
    gaScenario('omen-elements-pyroslash-' . $case)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Ciel, Loyal Valet', lineage: ['Spirit of Fire'], player: 2)
        ->seed('myBanish', $card, player: 2, properties: ['Counters' => ['omen' => 1]])
        ->seed('myBanish', $card, player: 2, properties: ['Counters' => ['omen' => 1]])
        ->seed('myBanish', $card, player: 2, properties: ['Counters' => ['omen' => 1]])
        ->seed('myHand', 'Inverted Pyroslash', player: 2)->endTurn()
        ->play('Inverted Pyroslash', 2)->pay($cost, 2)->target('theirField-0', 2)
        ->expectZoneCount('myMemory', $cost, 2, 'Only Fire omens reduce the five reserve cost, capped at four')
        ->tested('Inverted Pyroslash')->covers(['omen', 'element', 'cost'], 'Ciel Bonus: costs two less for each of up to two fire omens.')->register();
}
