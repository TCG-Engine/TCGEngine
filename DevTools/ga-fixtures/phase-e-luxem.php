<?php
/**
 * Phase E (Luxem): Lumen Borealis "Whenever an Animal ally you control dies, you may reveal a card from your memory."
 * (Uther's On Enter / On Leave already have fixtures in luxem.php.)
 */

const GA_LUMEN_DEATH_TEXT = 'Whenever an Animal ally you control dies, you may reveal a card from your memory.';

// P1 controls Lumen Borealis and a 1-life $victim; P2's Giant Tortoise (power 1) attacks the victim on P2's turn.
function gaLumenDeath(string $slug, string $victim, bool $victimIsP2 = false, bool $seedMemory = true) {
    $s = gaScenario($slug)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor('Lumen Borealis')
        ->seed('myField', 'Lumen Borealis');
    if ($seedMemory) $s->seed('myMemory', 'Dungeon Guide');
    if (!$victimIsP2) $s->seed('myField', $victim);
    $s->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2]);
    if ($victimIsP2) $s->seed('myField', $victim, player: 2);
    return $s;
}

$gaLumenAttack = fn($sc) => $sc->endTurn()->pass(1)->pass(1)->attack('myField-1', 'theirField-2', player: 2)->pass(1);

$gaLumenAttack(gaLumenDeath('lumen-borealis-an-animal-ally-dying-offers-the-controller-a-memory-reveal', 'Woodland Squirrels'))
    ->expectZoneCount('myGraveyard', 1, 1, 'The Squirrels died')
    ->answer(1, 'myMemory-0')
    ->expectDqEmpty()
    ->expectZoneCount('myMemory', 1, 1, 'Revealing does not move the card')
    ->tested('Lumen Borealis')
    ->covers(['trigger', 'death', 'reveal'], GA_LUMEN_DEATH_TEXT)
    ->register();

$gaLumenAttack(gaLumenDeath('lumen-borealis-the-memory-reveal-after-an-animal-death-is-optional', 'Woodland Squirrels'))
    ->pass(1)
    ->expectDqEmpty()
    ->expectZoneCount('myGraveyard', 1, 1, 'The Squirrels died')
    ->tested('Lumen Borealis')
    ->covers(['trigger', 'death', 'optional-choice'], GA_LUMEN_DEATH_TEXT)
    ->register();

$gaLumenAttack(gaLumenDeath('lumen-borealis-a-non-animal-ally-dying-offers-no-reveal', 'Blitz Mage'))
    ->expectZoneCount('myGraveyard', 1, 1, 'The Blitz Mage died')
    ->expectDqEmpty()
    ->tested('Lumen Borealis')
    ->covers(['trigger', 'death', 'condition'], GA_LUMEN_DEATH_TEXT)
    ->register();

$gaLumenAttack(gaLumenDeath('lumen-borealis-an-animal-death-with-an-empty-memory-offers-no-reveal', 'Woodland Squirrels', seedMemory: false))
    ->expectZoneCount('myGraveyard', 1, 1, 'The Squirrels died')
    ->expectDqEmpty()
    ->tested('Lumen Borealis')
    ->covers(['trigger', 'death', 'condition'], GA_LUMEN_DEATH_TEXT)
    ->register();

// An opposing Animal dying is not "an Animal ally you control".
gaScenario('lumen-borealis-an-opposing-animal-dying-offers-no-reveal')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lumen Borealis')
    ->seed('myField', 'Woodland Squirrels')
    ->seed('myField', 'Lumen Borealis', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-2', 'theirField-1', player: 2)->pass(1)
    ->expectZoneCount('myGraveyard', 1, 1, 'The opposing Squirrels died')
    ->expectZoneCount('myMemory', 1, 2, 'Lumen Borealis owner still holds the seeded memory card')
    ->expectDqEmpty()
    ->tested('Lumen Borealis')
    ->covers(['trigger', 'death', 'controller'], GA_LUMEN_DEATH_TEXT)
    ->register();

gaScenario('uther-illustrious-king-declining-to-rest-banishes-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Uther, Illustrious King')
    ->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Status', '2', 'Uther stayed ready')
    ->expectCardExists('theirField', 'Dungeon Guide', 'Nothing was banished')
    ->expectZoneCount('theirBanish', 0, 1, 'The opposing banishment is empty')
    ->expectDqEmpty()
    ->tested('Uther, Illustrious King')
    ->covers(['on-enter', 'rest', 'optional-choice'], '**On Enter:** You may rest Uther. When you do, banish another target non-champion object.')
    ->register();
