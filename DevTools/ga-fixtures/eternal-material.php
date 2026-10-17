<?php
foreach ([['imbued', ['IMBUED'], false], ['plain', [], true], ['suppressed', ['IMBUED', 'NO_ABILITIES'], true]] as [$suffix, $effects, $allowed]) {
    $s = gaScenario('eternal-magistrate-material-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->patch('myField-0', ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 4]])
        ->seed('theirField', 'Eternal Magistrate', properties: ['TurnEffects' => $effects])
        ->seed('myMaterial', "Shadow's Claw")->action(1, 10002, 'myMaterial-4!FSM!');
    if ($allowed) $s->expectCardExists('myField', "Shadow's Claw", 'An inactive restriction permits material departure');
    else $s->expectCardExists('myMaterial', "Shadow's Claw", 'The imbued opponent blocks main-phase departure')
        ->expectZoneCount('myField', 1, 1, 'No weapon enters when departure is blocked');
    $s->expectDqEmpty()->tested('Eternal Magistrate')->covers(['imbue', 'restriction', 'material'], 'Opposing material cards cannot leave outside their owner materialize phase.')->register();
}
