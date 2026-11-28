<?php
require __DIR__.'/dragapult-tests.php';
$beforeChecks = $checks;

function aegislashCards(int $player, string $zone): array {
    return array_values(array_map(fn($obj) => $obj->CardID, PokeObjects($player, $zone)));
}
function aegislashDeck(array $ids): void {
    $GLOBALS['p1Deck'] = [];
    foreach ($ids as $id) PokeAdd(1, 'Deck', $id);
}
function aegislashChoices(): array {
    return array_column(PokeDecisionOptions(1)['choices'], 'card');
}

$ids = ['me03-057', 'me03-058', 'me01-167', 'me01-173', 'me02-087', '30th-127', 'sv08-185', 'sv10.5b-067', 'sv10.5w-086'];
$deck = array_map(fn($id) => ['id'=>$id, 'count'=>1], $ids);
$deck[] = ['id'=>'mee-008', 'count'=>51];
verifyDragapult(PokeValidateDeck($deck) === [], 'All nine printings pass deck validation');
$deck[8]['count'] = 5;
$deck[9]['count'] = 47;
verifyDragapult(PokeValidateDeck($deck) === ['More than 4 copies of Ignition Energy'], 'Ignition is Special Energy for deck building');

dragapultBoard('me03-057');
foreach (['me03-056', 'me03-057', 'me03-058', 'mee-008'] as $id) PokeAdd(1, 'Hand', $id);
dragapultAction('attack');
verifyDragapult(aegislashChoices() === ['me03-056', 'me03-057', 'me03-058'], 'Weaponized Swords offers only the named Pokémon');
dragapultAnswer('p1Hand-0&p1Hand-2');
verifyDragapult(GetZoneObject('p2Active-0')->Damage === 120 && PokeCount(1, 'Hand') === 4, 'Two revealed cards deal 120 and stay in hand');
$reveals = array_filter(PokeVar('log'), fn($event) => $event['event'] === 'reveal-hand');
verifyDragapult(array_values(array_column($reveals, 'card')) === ['me03-056', 'me03-058'], 'Only selected cards are revealed');
dragapultBoard('me03-057');
PokeAdd(1, 'Hand', 'me03-056');
dragapultAction('attack'); dragapultAnswer('-');
verifyDragapult(GetZoneObject('p2Active-0')->Damage === 0 && GetTurnPlayer() === 2, 'Weaponized Swords can reveal zero cards');

dragapultBoard('me03-058');
GetZoneObject('p1Active-0')->Energy = ['mee-008', 'mee-008', 'mee-008', 'mee-008'];
dragapultAction('attack', 1);
verifyDragapult(GetZoneObject('p2Active-0')->Damage === 230, 'Metal Slash deals 230');
PokeApplyAction(['player'=>2, 'type'=>'end']);
verifyDragapult(!dragapultHasAction('attack'), 'Metal Slash locks both attacks on the next turn');
dragapultBench(1, 'me03-056');
dragapultPlay('30th-127'); dragapultAnswer('p1Bench-0');
dragapultPlay('30th-127'); dragapultAnswer(PokeFirstRef(1, 'Bench'));
verifyDragapult(dragapultHasAction('attack') && !GetRetreatUsed(1), 'Switch clears the attack lock without using retreat');

dragapultBoard();
aegislashDeck(['me03-056', 'me03-057', 'me03-058', 'mee-008']);
dragapultPlay('me02-087');
foreach (['me03-056', 'me03-057', 'me03-058'] as $i => $id) {
    verifyDragapult(aegislashChoices() === [$id], 'Dawn offers one search per stage');
    dragapultAnswer('p1Deck-'.$i);
}
verifyDragapult(aegislashCards(1, 'Hand') === ['me03-056', 'me03-057', 'me03-058'] && GetSupporterUsed(1), 'Dawn finds and reveals all three stages through saved decisions');
dragapultBoard();
aegislashDeck(['me03-056', 'me03-058']);
dragapultPlay('me02-087'); dragapultAnswer('-');
verifyDragapult(aegislashChoices() === ['me03-058'], 'Dawn can skip a choice and continue past a missing stage');
dragapultAnswer('p1Deck-1');

foreach (['me01-167', 'sv08-185'] as $id) {
    dragapultBoard();
    for ($i = 0; $i < 4; ++$i) dragapultBench(1, 'me03-056');
    aegislashDeck(['me03-056', 'sv10.5b-067', 'me03-057']);
    dragapultPlay($id);
    verifyDragapult(aegislashChoices() === ($id === 'me01-167' ? ['me03-056'] : ['me03-056', 'sv10.5b-067']), 'Bench search uses the card’s own Basic Pokémon filter');
    verifyDragapult(PokeDecisionOptions(1)['max'] === 1, 'Bench search respects the final open slot');
    dragapultAnswer($id === 'me01-167' ? 'p1Deck-0' : 'p1Deck-1');
    verifyDragapult(PokeCount(1, 'Bench') === 5, 'Bench search puts the selected Pokémon directly into play');
}
dragapultBoard();
foreach (['sv10.5w-086', 'me03-057', 'mee-008'] as $id) PokeAdd(1, 'Discard', $id);
dragapultPlay('me01-173');
verifyDragapult(aegislashChoices() === ['me03-057', 'mee-008'], 'Night Stretcher excludes Ignition Energy');
dragapultAnswer('p1Discard-2');
verifyDragapult(aegislashCards(1, 'Hand') === ['mee-008'], 'Night Stretcher recovers the selected card');

dragapultBoard('sv10.5b-067');
aegislashDeck(['me03-056', 'me03-057', 'me03-058', 'me02.5-159']);
dragapultAction('ability');
verifyDragapult(aegislashChoices() === ['me03-057', 'me03-058'], 'Metallic Signal finds only Metal Evolutions');
dragapultAnswer('p1Deck-1&p1Deck-2');
verifyDragapult(PokeCount(1, 'Hand') === 2 && !dragapultHasAction('ability'), 'Metallic Signal finds up to two and is once per turn');
$genesect = GetZoneObject('p1Active-0');
$genesect->Energy = ['mee-008', 'mee-008', 'mee-008'];
GetZoneObject('p2Active-0')->CardID = 'sv08-036';
dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage === 150, 'Protect Charge deals 150');
verifyDragapult(GetTurnPlayer() === 2, 'Protect Charge advances to the opponent’s turn');
PokeDealAttackDamage(2, 'p2Active-0', 50);
verifyDragapult(GetZoneObject('p1Active-0')->Damage === 70, 'Protect Charge subtracts 30 after Fire Weakness doubles damage');
PokeDealAttackDamage(2, 'p2Active-0', 20, true);
verifyDragapult(GetZoneObject('p1Active-0')->Damage === 110, 'Attacks that ignore effects bypass Protect Charge');

dragapultBoard('me03-058');
$active = GetZoneObject('p1Active-0');
$active->Energy = ['sv10.5w-086'];
$benchRef = dragapultBench(1, 'me03-056');
$bench = GetZoneObject($benchRef);
$bench->Energy = ['sv10.5w-086', 'mee-008', 'sv10.5w-086'];
GetZoneObject('p2Active-0')->Energy = ['sv10.5w-086'];
verifyDragapult(PokeHasAttackEnergy($active, ['Colorless', 'Colorless', 'Colorless']), 'Ignition provides three Energy on an Evolution Pokémon');
verifyDragapult(!PokeHasAttackEnergy($active, ['Metal']) && !PokeHasAttackEnergy($bench, array_fill(0, 4, 'Colorless')), 'Ignition provides only Colorless and counts as one on a Basic');
verifyDragapult(PokeRetreatPayments($active) === [[0]], 'A single Ignition pays a three-Energy retreat cost');
dragapultAction('attack');
verifyDragapult(GetZoneObject('p2Active-0')->Damage === 80, 'Ignition can pay the full cost of Slash');
verifyDragapult(GetZoneObject('p1Active-0')->Energy === [] && GetZoneObject($benchRef)->Energy === ['mee-008'], 'End of turn discards all own Ignition Energy from Active and Bench');
verifyDragapult(GetZoneObject('p2Active-0')->Energy === ['sv10.5w-086'] && PokeCount(1, 'Discard') === 3, 'End of turn preserves the opponent’s Ignition Energy');

dragapultBoard('me03-058');
GetZoneObject('p1Active-0')->Energy = [];
$target = dragapultBench(1, 'me03-056');
$card = PokeAdd(1, 'Hand', 'sv10.5w-086');
PokeApplyAction(['player'=>1, 'type'=>'attach', 'source'=>PokeRef(1, 'Hand', $card->mzIndex), 'target'=>'p1Active-0']);
PokeApplyAction(['player'=>1, 'type'=>'retreat', 'target'=>$target, 'payment'=>[0]]);
verifyDragapult(aegislashCards(1, 'Active') === ['me03-056'] && aegislashCards(1, 'Discard') === ['sv10.5w-086'], 'Retreat discards one Ignition card to pay three Energy');

echo 'PASS '.($checks - $beforeChecks)." Aegislash card checks\n";
