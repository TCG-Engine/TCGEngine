<?php
/**
 * Phase 3d (Norm): "whenever your champion levels up" cards. The 'lorraine-windslice' deck levels the champion up into Lorraine, Wandering Warrior at the turn-three materialize phase;
 * the memory cost is paid from a seeded memory card (the seed survives player 1's recollection only if it is the card that pays, so the level up happens first).
 */

if (!function_exists('gaNormLevelUp')) {
    function gaNormLevelUp(string $slug): GaScenario {
        return gaScenario($slug)->deckTemplate('lorraine-windslice', 'Dungeon Guide');
    }
}

gaNormLevelUp('liminal-guide-champion-level-up-returns-it-from-the-graveyard-ephemeral-and-draws')
    ->seed('myGraveyard', 'Liminal Guide')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-1')
    ->expectCardExists('myField', 'Liminal Guide', 'The champion levelled up: the Guide returned to the field')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'It returned ephemeral')
    ->tested('Liminal Guide')
    ->covers(['trigger', 'level-up', 'zone-movement', 'ephemeral', 'draw'], 'Whenever your champion levels up, return this card from your graveyard to the field. If you do, it becomes **ephemeral** and draw a card.')
    ->register();

gaNormLevelUp('devoted-martyr-class-bonus-champion-level-up-banishes-it-from-the-graveyard-to-recover-two')
    ->readyFor('Devoted Martyr', classBonus: true, damage: 5)
    ->seed('myGraveyard', 'Devoted Martyr')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-1')->answer(1, 'YES')
    ->expectDamage('p1Field-0', 3, '[Class Bonus] The Martyr was banished from the graveyard: recover 2 (5 - 2)')
    ->tested('Devoted Martyr')
    ->covers(['class-bonus', 'trigger', 'level-up', 'banish', 'recover'], '[Class Bonus] Whenever your champion levels up, you may banish this card from your graveyard. If you do, **recover 2**.')
    ->register();

gaNormLevelUp('suspicious-concoction-champion-level-up-banishes-it-to-draw-into-memory-and-recover-two')
    ->seed('myField', 'Suspicious Concoction')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->patch('myField-0', ['Damage' => 5])
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')->answer(1, 'YES')
    ->expectCardExists('myBanish', 'Suspicious Concoction', 'The Concoction banished itself')
    ->tested('Suspicious Concoction')
    ->covers(['trigger', 'level-up', 'banish-self', 'draw-into-memory', 'recover'], 'Whenever your champion levels up, you may banish Suspicious Concoction. If you do, draw a card into your memory and **recover 2**.')
    ->register();

gaNormLevelUp('sanctum-of-esoteric-truth-champion-level-up-bottoms-two-cards-and-draws-two')
    ->seed('myField', 'Sanctum of Esoteric Truth')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')->answer(1, 'myHand-0')->answer(1, 'myHand-0')
    ->expectZoneCount('myHand', 8, 1, 'Two cards to the bottom and two drawn: the hand stays at the 8 it had after the turn draw')
    ->tested('Sanctum of Esoteric Truth')
    ->covers(['trigger', 'level-up', 'zone-movement', 'draw'], 'Whenever your champion levels up, you may put two cards from your hand and/or memory on the bottom of your deck. If you do, draw two cards.')
    ->register();
