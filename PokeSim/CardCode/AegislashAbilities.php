<?php
$cards = [];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];

$cards['me03-057'][] = $ability('Attack', 'Weaponized Swords', <<<'CODE'
$targets = PokeCandidates($player, 'Hand', 'swords');
if ($targets !== '') {
    $maximum = count(explode('&', $targets));
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Weaponized_Swords:_reveal_any_number_of_sword_Pokémon');
    PokeDealAttackDamage($player, $mzID, 60 * PokeRevealHandSelection($player, $chosen));
}
CODE);

$cards['me03-058'][] = $ability('Attack', 'Slash', 'PokeDealAttackDamage($player, $mzID, 80);');
$cards['me03-058'][] = $ability('Attack', 'Metal Slash', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 230);
GetZoneObject($mzID)->TurnEffects['noAttackThrough'] = GetTurnNumber() + 2;
CODE);

$cards['me02-087'][] = $ability('TrainerPlayed', 'Dawn', <<<'CODE'
$stages = ['basicPokemon', 'stage1', 'stage2'];
$labels = ['Basic', 'Stage_1', 'Stage_2'];
for ($i = 0; $i < count($stages); ++$i) {
    $targets = PokeCandidates($player, 'Deck', $stages[$i]);
    if ($targets !== '') {
        $prompt = 'Dawn:_search_for_a_' . $labels[$i] . '_Pokémon';
        $chosen = await $player.MZMayChoose($targets, $prompt);
        PokeResolveSearch($player, $chosen, 'Hand', true, false);
    }
}
PokeShuffle($player);
CODE, 'return PokeCount($player, "Deck") > 0;');

$cards['sv08-185'][] = $ability('TrainerPlayed', 'Precious Trolley', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicPokemon');
if ($targets !== '') {
    $maximum = min(5 - PokeCount($player, 'Bench'), count(explode('&', $targets)));
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Precious_Trolley:_choose_Basic_Pokémon_to_Bench');
    PokeResolveSearch($player, $chosen, 'Bench');
} else {
    PokeShuffle($player);
}
CODE, 'return PokeCount($player, "Bench") < 5 && PokeCount($player, "Deck") > 0;');

$cards['sv10.5b-067'][] = $ability('ActivateAbility', 'Metallic Signal', <<<'CODE'
PokeMarkAbilityUsed($mzID);
$targets = PokeCandidates($player, 'Deck', 'metalEvolution');
if ($targets !== '') {
    $maximum = min(2, count(explode('&', $targets)));
    $chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Metallic_Signal:_search_for_up_to_two_Metal_Evolution_Pokémon');
    PokeResolveSearch($player, $chosen);
} else {
    PokeShuffle($player);
}
CODE, 'return PokeAbilityUnused($mzID) && PokeCount($player, "Deck") > 0;');
$cards['sv10.5b-067'][] = $ability('Attack', 'Protect Charge', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 150);
GetZoneObject($mzID)->TurnEffects['attackDamageReduction'] = ['turn'=>GetTurnNumber() + 1, 'amount'=>30];
CODE);

$cards['sv10.5w-086'][] = $ability('EnergyAttached', 'Ignition Energy', 'return;');
$cards['sv10.5w-086'][] = $ability('EnergyUnitModifier', 'Ignition Energy', <<<'CODE'
return isset($subjectObj->CardID) && PokeIsEvolution($subjectObj->CardID) ? 2 : 0;
CODE);
$cards['sv10.5w-086'][] = $ability('DiscardEnergyAtEndTurn', 'Ignition Energy', 'return 1;');

return $cards;
