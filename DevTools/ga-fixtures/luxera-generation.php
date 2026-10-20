<?php
foreach ([['blocked', 'Refurbish', 2, false], ['different-name', 'Dungeon Guide', 3, true]] as [$suffix, $name, $memory, $generated]) {
    $s = gaScenario('luxera-generation-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Nuwa', classBonus: true)
        ->seed('myHand', 'Greater Boon of Luxera')->seed('myHand', 'Lesser Boon of Nuwa')
        ->play('Greater Boon of Luxera')->answer(1, $name)
        ->play('Lesser Boon of Nuwa')->pay(2)
        ->expectZoneCount('myMemory', $memory, 1, 'Only permitted generation adds the extra memory card');
    if($generated) $s->expectCardExists('myMemory', 'Refurbish', 'A different chosen name permits generation');
    $s->expectCardExists('myField', 'Lesser Boon of Nuwa', 'The boon still resolves when generation is prohibited')
        ->tested('Greater Boon of Luxera')->covers(['name-choice', 'generate', 'restriction'], 'You cannot generate cards with the chosen name.')->register();
}
