<?php
/** Versioned card authoring source. setup.php saves these through CardAbilityRepository. */
$cards = [];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
foreach (['me05-005','me05-006','me05-034'] as $id) {
    foreach (['AttackEffectProtection','AbilityEffectProtection'] as $macro) $cards[$id][] = $ability($macro, "Hide 'n' Sneak", 'return 1;');
}
$cards['me05-005'][] = $ability('Attack','Furtive Drop', 'PokePlaceDamageCounters($mzID, PokeFirstRef(3 - $player, "Active"), 1);');
$cards['me05-006'][] = $ability('Attack','Matcha Spin', <<<'CODE'
if (PokeCountHideSneak($player) >= 6) {
    foreach (PokeFieldRefs(3 - $player) as $target) PokePlaceDamageCounters($mzID, $target, 4);
}
CODE);
$cards['me05-039'][] = $ability('Attack','Vengeful Anchor', 'PokeDealAttackDamage($player, $mzID, 30 + (PokeCountHideSneak($player) >= 4 ? 140 : 0));');
$cards['me05-034'][] = $ability('Attack','Puppet Pull', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 80);
$targets = PokeCandidates($player, 'Deck');
$chosen = await $player.MZMayChoose($targets, "Puppet_Pull:_choose_a_card");
PokeResolveSearch($player, $chosen, 'Hand', false);
CODE);
$searches = [
    'me02.5-207'=>["Team Rocket's Petrel",'trainer'], 'me02.5-209'=>["Team Rocket's Transceiver",'rocketSupporter'],
    'me03-072'=>['Energy Search','basicEnergy'], 'me03-081'=>['Poké Pad','nonRuleBox'], 'sv08-165'=>['Call Bell','supporter'],
];
foreach ($searches as $id => [$name,$filter]) {
    $code = '$targets = PokeCandidates($player, "Deck", "'.$filter.'");' . "\n" . '$chosen = await $player.MZMayChoose($targets, "Search_your_deck");' . "\n" . 'PokeResolveSearch($player, $chosen);';
    $prereq = $id === 'sv08-165' ? 'return $player !== GetFirstPlayer() && GetPlayerTurns($player) === 1;' : '';
    $cards[$id][] = $ability('TrainerPlayed',$name,$code,$prereq);
}
$cards['me02.5-213'][] = $ability('TrainerPlayed','Ultra Ball', <<<'CODE'
$targets = PokeCandidates($player, 'Hand');
$discard = await $player.MZMultiChoose($targets, 2, 2, "Ultra_Ball:_discard_two_cards");
PokeDiscardSelection($player, $discard);
$targets = PokeCandidates($player, 'Deck', 'pokemon');
$chosen = await $player.MZMayChoose($targets, "Ultra_Ball:_search_for_a_Pokémon");
PokeResolveSearch($player, $chosen);
CODE, 'return PokeCount($player, "Hand") >= (GetZoneObject($mzID)->Location === "Hand" ? 3 : 2);');
$cards['sv08-164'][] = $ability('TrainerPlayed','Brilliant Blender', <<<'CODE'
$targets = PokeCandidates($player, 'Deck');
$maximum = min(5, PokeCount($player, 'Deck'));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Brilliant_Blender:_discard_up_to_five_cards");
PokeDiscardSelection($player, $chosen);
PokeShuffle($player);
CODE);
$cards['me02.5-196'][] = $ability('TrainerPlayed','Night Stretcher', <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'pokemonOrEnergy');
$chosen = await $player.MZChoose($targets, "Night_Stretcher:_recover_a_Pokémon_or_basic_Energy");
PokeResolveRecovery($player, $chosen);
CODE, 'return PokeCandidates($player, "Discard", "pokemonOrEnergy") !== "";');
$cards['sv10.5w-082'][] = $ability('TrainerPlayed','Energy Retrieval', <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'basicEnergy');
$maximum = min(2, count(explode('&', $targets)));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Energy_Retrieval:_recover_up_to_two_basic_Energy");
PokeResolveRecovery($player, $chosen);
CODE, 'return PokeCandidates($player, "Discard", "basicEnergy") !== "";');
$cards['me02.5-183'][] = $ability('TrainerPlayed',"Boss's Orders", <<<'CODE'
$targets = PokeCandidates(3 - $player, 'Bench');
$chosen = await $player.MZChoose($targets, "Boss's_Orders:_choose_an_opposing_Benched_Pokémon");
PokeSwitch(3 - $player, $chosen);
CODE, 'return PokeCount(3 - $player, "Bench") > 0;');
$cards['me02.5-192'][] = $ability('TrainerPlayed',"Lillie's Determination", 'PokeShuffleHandDraw($player, PokeCount($player, "Prizes") === 6 ? 8 : 6);');
$cards['me04-082'][] = $ability('TrainerPlayed','Special Red Card', 'PokeShuffleHandDraw(3 - $player, 3, true);', 'return PokeCount(3 - $player, "Prizes") <= 3 && PokeCount(3 - $player, "Hand") > 0;');
$cards['sv09-144'][] = $ability('TrainerPlayed',"Black Belt's Training", 'PokeBlackBelt($player);');
$cards['sv10.5b-084'][] = $ability('TrainerPlayed','Pokégear 3.0', <<<'CODE'
PokeRevealTop($player, 7);
$targets = PokeCandidates($player, 'TempZone', 'supporter');
$chosen = await $player.MZMayChoose($targets, "Pokégear_3.0:_choose_a_Supporter");
PokeResolveRecovery($player, $chosen);
PokeReturnTemp($player);
CODE);
$cards['me03-088'][] = $ability('EnergyAttached','Telepathic Psychic Energy', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicPsychic');
$maximum = min(2, 5 - PokeCount($player, 'Bench'));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Telepathic_Psychic_Energy:_Bench_up_to_two_basic_Psychic_Pokémon");
PokeResolveSearch($player, $chosen, 'Bench');
CODE, 'return str_contains(EffectiveCardElement(GetZoneObject($mzID)), "Psychic") && PokeCount($player, "Bench") < 5;');
$cards['mee-005'] = [];
return $cards;
