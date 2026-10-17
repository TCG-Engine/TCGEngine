<?php
/**
 * Phase E (Exia): Annihilation "[Damage 35+] Fast Activation" (the champion has 35 or more damage; not damage taken this turn).
 */

const GA_ANNIHILATION_FAST_TEXT = '[Damage 35+] Fast Activation';

// P2's Giant Tortoise attacks; P1 holds Annihilation with a champion at $damage damage and answers the attack with it ($cast) or passes.
function gaAnnihilationFast(string $slug, int $damage, bool $cast): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor('Annihilation', classBonus: true, damage: $damage)
        ->seed('myHand', 'Annihilation')->seed('myField', 'Dungeon Guide')
        ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
        ->endTurn()->pass(1)->pass(1)
        ->attack('myField-1', 'theirField-1', player: 2);
    return $cast ? $s->answer(1, 'myHand-7')->pay(3)->answer(1, '0') : $s->rejects(1, 'myHand-7', 'Annihilation is not offered at fast speed below Damage 35');
}

gaAnnihilationFast('annihilation-activates-at-fast-speed-with-35-damage-on-the-champion', 35, true)
    ->expectCardExists('myGraveyard', 'Annihilation', 'Cast during the opponent\'s attack')
    ->expectDamage('p1Field-0', 35, 'Up to 7 damage: the champion chose 0 and stays at 35')
    ->expectCardExists('myField', 'Dungeon Guide', 'The reserve-3 ally survives a 0-damage Annihilation')
    ->tested('Annihilation')
    ->covers(['damage-threshold', 'fast-activation'], GA_ANNIHILATION_FAST_TEXT)
    ->register();

gaAnnihilationFast('annihilation-has-no-fast-activation-with-34-damage-on-the-champion', 34, false)
    ->expectDamage('p1Field-0', 34, 'Nothing was cast: the champion is still at 34')
    ->tested('Annihilation')
    ->covers(['damage-threshold', 'condition'], GA_ANNIHILATION_FAST_TEXT)
    ->register();
