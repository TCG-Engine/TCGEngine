<?php
$deckAbilities = require __DIR__ . '/DeckAbilities.php';
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards = [];
$cards['30th-120'][] = $ability('Attack', 'Bouncy Circle', <<<'CODE'
$count = count(array_filter(PokeObjects($player, 'Bench'), fn($obj) => PokeCurrentHP($obj) === 30));
PokeDealAttackDamage($player, $mzID, 30 * $count);
CODE);
$cards['sv06-126'][] = $ability('Attack', 'Find a Friend', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'pokemon');
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, 'Find_a_Friend:_search_for_a_Pokémon');
    PokeResolveSearch($player, $chosen);
} else {
    PokeShuffle($player);
}
CODE);
$cards['sv06-018'][] = $ability('Attack', 'Do the Wave', 'PokeDealAttackDamage($player, $mzID, 20 * PokeCount($player, "Bench"));');
$cards['sv06-018'][] = $ability('RepeatAttackAllowed', 'Festival Lead', 'return (PokeStadium()->CardID ?? null) === "sv06-149" ? 1 : 0;');
$cards['sv06-149'][] = $ability('TrainerPlayed', 'Festival Grounds', <<<'CODE'
PokePlayStadium($player, $mzID);
foreach ([1, 2] as $seat) {
    foreach (PokeFieldRefs($seat) as $ref) PokeClearProtectedConditions(GetZoneObject($ref));
}
PokeResolveKnockouts();
CODE);
$cards['sv10-168'][] = $ability('TrainerPlayed', 'Sacred Ash', <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'pokemon');
$maximum = min(5, count(explode('&', $targets)));
$chosen = await $player.MZMultiChoose($targets, 1, $maximum, 'Sacred_Ash:_return_up_to_five_Pokémon');
PokeResolveSearch($player, $chosen, 'Deck', false);
CODE, 'return PokeCandidates($player, "Discard", "pokemon") !== "";');
$cards['me05-077'][] = $ability('TrainerPlayed', "Gladion's Final Battle",
    'PokeSetVar("gladionFinalBattle:" . $player, GetTurnNumber());',
    'return PokeCount($player, "Hand") === (GetZoneObject($mzID)->Location === "Hand" ? 1 : 0);');
$cards['sv06.5-061'] = $deckAbilities['me02.5-196'];
return $cards;
