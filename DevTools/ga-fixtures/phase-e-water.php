<?php
/**
 * Phase E (Water): Frozen Divinity's opposing level-2 champion clause; Treacle, Drowned Mouse's stealth while ephemeral.
 */

const GA_FROZEN_RETURN_TEXT = 'If a champion with base level 2 you don\'t control would level up into a champion card, return that card to its owner\'s material deck instead.';
const GA_TREACLE_STEALTH_TEXT = 'As long as CARDNAME is **ephemeral**, it has **stealth**.';

// Turn one is a single turn shared by both players, so neither champion can level up until their second turn. Player 1 owns the Frozen
// Divinity: a flag (a level-up that turn) keeps it through their first end phase, and their own level-up on turn two keeps it through the second.
// Player 2's level 2 champion then tries to level into level 3 at the start of their second turn.
function gaFrozenLevelUp(string $slug, bool $withDivinity = true) {
    $scenario = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    if ($withDivinity) $scenario->seed('myField', 'Frozen Divinity');
    return $scenario
        ->champion(cardName: 'Alice, Golden Queen', lineage: ['Spirit of Water'])
        ->seed('myMaterial', 'Alice, Whim\'s Monarch')
        ->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
        ->champion(cardName: 'Tristan, Hired Blade', player: 2)
        ->seed('myMaterial', 'Tristan, Shadowreaver', player: 2)
        ->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)
        ->globalEffect('LEVELED_UP_THIS_TURN', 1)
        ->endTurn()->endTurn(2)->answer(1, 'myMaterial-4')->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y;Bottom=')->pass(1)->endTurn()->pass(1)->pass(1);
}

gaFrozenLevelUp('frozen-divinity-returns-an-opposing-level-two-champions-level-up-card-to-the-material-deck')
    ->answer(2, 'myMaterial-4')->pass(1)
    ->expectProperty('p2Field-0', 'CardID', 'gt7lh9v221', 'The champion stayed Hired Blade: the level-up was replaced')
    ->expectZoneCount('theirMaterial', 5, 1, 'The level-up card went back to the material deck (it did not enter the field)')
    ->expectZoneCount('theirMemory', 0, 1, 'The level-up was attempted: its three memory cards were paid')
    ->expectDqEmpty()
    ->tested('Frozen Divinity')
    ->covers(['replacement', 'level-up', 'material-deck', 'opponent'], GA_FROZEN_RETURN_TEXT)
    ->register();

// Control: the same turns with no Frozen Divinity: the level-up completes.
gaFrozenLevelUp('frozen-divinity-control-without-it-the-level-two-champion-levels-up', false)
    ->answer(2, 'myMaterial-4')->pass(1)
    ->expectProperty('p2Field-0', 'CardID', '4upufooz13', 'Without Frozen Divinity the level-up into Shadowreaver completed')
    ->tested('Frozen Divinity')
    ->covers(['replacement', 'level-up', 'control'], GA_FROZEN_RETURN_TEXT)
    ->register();

// ---- Treacle, Drowned Mouse ----

// Ephemeral is persistent (the object is banished instead of leaving the field), so the opposing Treacle is still ephemeral on player 1's next turn.
function gaTreacleTarget(string $slug, array $effects) {
    return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', 'Treacle, Drowned Mouse', player: 2, properties: ['TurnEffects' => $effects])
        ->seed('myField', 'Dungeon Guide')
        ->reachTurnThree()
        ->action(1, 10002, 'myField-1!FSM!');
}

gaTreacleTarget('treacle-drowned-mouse-ephemeral-has-stealth-and-cannot-be-attacked', ['EPHEMERAL'])
    ->rejects(1, 'theirField-1', 'Ephemeral Treacle has stealth: it is not a legal attack target', 100)
    ->answer(1, 'theirField-0')
    ->expectDamage('p2Field-1', 0, 'Treacle was never attacked')
    ->tested('Treacle, Drowned Mouse')
    ->covers(['stealth', 'ephemeral', 'condition'], GA_TREACLE_STEALTH_TEXT)
    ->register();

gaTreacleTarget('treacle-drowned-mouse-not-ephemeral-has-no-stealth-and-can-be-attacked', [])
    ->answer(1, 'theirField-1')->answer(2, '-')
    ->expectDamage('p2Field-1', 1, 'Without ephemeral there is no stealth: Treacle took the attack')
    ->tested('Treacle, Drowned Mouse')
    ->covers(['stealth', 'ephemeral', 'control'], GA_TREACLE_STEALTH_TEXT)
    ->register();
