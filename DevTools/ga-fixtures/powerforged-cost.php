<?php
foreach ([0, 1, 3] as $x) {
    $s = gaScenario('powerforged-burst-pays-' . $x . '-before-response')
        ->standardDeck('Powerforged Burst')->readyFor('Powerforged Burst')
        ->seed('myField', 'Powercell')->seed('myField', 'Powercell')->seed('myField', 'Powercell')
        ->seed('myField', 'Genbu, Black Tortoise', player: 2)
        ->seed('myHand', 'Powerforged Burst')->play('Powerforged Burst')
        ->answer(1, $x === 0 ? '-' : ($x === 1 ? 'myField-1' : 'myField-1&myField-2&myField-3'))
        ->expectZoneCount('myField', 4 - $x, 1, 'The chosen Powercells are gone before priority', 2)
        ->expectDamage('p2Field-1', 0, 'The effect has not resolved before priority', 2)
        ->answer(1, '-')->answer(2, '-')->target('theirField-1')
        ->expectDamage('p2Field-1', 2 * $x, 'Paid X survives priority and the Skill targets spellshroud')
        ->expectZoneCount('myMemory', $x === 3 ? 1 : 0, 1, 'Only X three draws into memory')
        ->expectDqEmpty()->tested('Powerforged Burst')
        ->covers(['additional-cost', 'sacrifice', 'damage', 'targeting', 'draw-into-memory'], 'As an additional cost to activate this card, sacrifice **X** Powercell items. **X** can\'t be more than 3.', 'Deal 2 damage to target unit **X** times. If **X** is 3, draw a card into your memory.')
        ->register();
}
gaScenario('powerforged-burst-with-no-powercells-uses-zero')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Powerforged Burst')
    ->seed('myHand', 'Powerforged Burst')->play('Powerforged Burst')->target('theirField-0')
    ->expectDamage('p2Field-0', 0, 'X zero deals no damage')->expectZoneCount('myMemory', 0, 1, 'X zero draws no memory')
    ->expectCardExists('myGraveyard', 'Powerforged Burst', 'Zero Powercells is a legal activation')->expectDqEmpty()
    ->tested('Powerforged Burst')->covers(['additional-cost', 'damage'], 'As an additional cost to activate this card, sacrifice **X** Powercell items. **X** can\'t be more than 3.')
    ->register();
