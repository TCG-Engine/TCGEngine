<?php
foreach([false, true] as $inherited) {
$s = gaScenario($inherited ? 'lorraine-ascendant-inherited-pays-2024' : 'lorraine-ascendant-pays-2024-and-generates-two-swords')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: $inherited ? 'Lorraine, Blademaster' : 'Lorraine, Ascendant Wings', lineage: $inherited ? ['Lorraine, Ascendant Wings'] : [])
    ->patch('myField-0', ['Counters' => ['_overrides' => ['type' => 'CHAMPION,ASCENDANT']]])
    ->emptyZone('myHand', destZone: 'myDeck');
for($i = 0; $i < 2024; ++$i) $s->seed('myHand', 'Dungeon Guide');
$payment = implode('&', array_map(fn($i) => 'myHand-' . $i, range(0, 2023)));
$s->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, $payment)
    ->answer(1, 'YES')->answer(1, 'Training Sword')
    ->answer(1, 'YES')->answer(1, 'Training Sword')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 2024, 1, 'The literal 2024 reserve cost is paid', step: 7)
    ->expectZoneCount('myField', 3, 1, 'Two named Sword regalia copies enter together after choices finish', step: 7)
    ->expectProperty('p1Field-0', 'Status', '2', 'There is no rest in the cost', step: 7)
    ->expectGlobalEffect(1, 'LORRAINE_ASCENDANT_SWORDS_AS_ALLY', 'Sword attack permission lasts for the rest of the game')
    ->expectDqEmpty(step: 7)
    ->tested('Lorraine, Ascendant Wings')
    ->covers(['lineage', 'activated-ability', 'cost', 'generate'], 'Pay 2024 reserve, then generate any amount of Sword regalia.')->register();

}

foreach([['not-ascendant', false], ['unaffordable', true]] as [$suffix, $ascendant]) {
    $s = gaScenario('lorraine-ascendant-' . $suffix)->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Lorraine, Ascendant Wings');
    if($ascendant) $s->patch('myField-0', ['Counters' => ['_overrides' => ['type' => 'CHAMPION,ASCENDANT']]]);
    $s->action(1, 10001, 'myField-0!CustomInput!Activate:0')
        ->expectZoneCount('myMemory', 0, 1, 'An illegal activation pays no reserve')
        ->expectZoneCount('myField', 1, 1, 'An illegal activation generates no Sword')
        ->expectDqEmpty()->tested('Lorraine, Ascendant Wings')->covers(['restriction', 'cost', 'ascendant'], 'Require Ascendant and all 2024 reserve before starting.')->register();
}

gaScenario('lorraine-ascendant-permanent-sword-attack-permission')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->globalEffect('LORRAINE_ASCENDANT_SWORDS_AS_ALLY')
    ->seed('myField', 'Training Sword')->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 1, 'Sword attacks as an ally without a champion wielding it')
    ->expectGlobalEffect(1, 'LORRAINE_ASCENDANT_SWORDS_AS_ALLY', 'The permission survives turn boundaries and does not require Lorraine to remain')
    ->tested('Lorraine, Ascendant Wings')->covers(['combat', 'permanent-effect'], 'Sword weapons can attack as though they were allies for the rest of the game.')->register();
