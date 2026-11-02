<?php
/** Ceruledge deck card authoring source; setup saves through CardAbilityRepository. */
$cards = [];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro, 'abilityName'=>$name, 'abilityCode'=>$code, 'prereqCode'=>$prereq, 'isImplemented'=>true];
$cards['me02-019'][] = $ability('Attack', 'Gather Strength', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicEnergy');
$chosen = await $player.MZMultiChoose($targets, 0, 2, 'Gather_Strength:_search_for_up_to_two_Basic_Energy');
PokeResolveSearch($player, $chosen);
CODE);
$cards['me02-019'][] = $ability('Attack', 'Chop', 'PokeDealAttackDamage($player, $mzID, 10);');
$cards['sv08-036'][] = $ability('Attack', 'Abyssal Flames', 'PokeDealAttackDamage($player, $mzID, 30 + 20 * count(array_filter(PokeObjects($player, "Discard"), fn($obj) => EffectiveCardType($obj) === "Energy")));');
$cards['sv08-036'][] = $ability('Attack', 'Raging Amethyst', 'PokeDealAttackDamage($player, $mzID, 280); PokeDiscardAttackEnergy($player, $mzID);');
$cards['me02-020'][] = $ability('Attack', 'Infernal Slash', <<<'CODE'
$targets = PokeCandidates($player, 'Hand', 'basicFire');
if ($targets !== '' && count(explode('&', $targets)) >= 4) {
    $chosen = await $player.MZMultiChoose($targets, 4, 4, 'Infernal_Slash:_discard_four_Basic_Fire_Energy');
    PokeDiscardSelection($player, $chosen);
    PokeDealAttackDamage($player, $mzID, 220);
}
CODE);
$cards['sv06-040'][] = $ability('Attack', 'Wrathful Hearth', 'PokeDealAttackDamage($player, $mzID, 20 * intdiv(GetZoneObject($mzID)->Damage, 10));');
$cards['sv06-040'][] = $ability('Attack', 'Dynamic Blaze', <<<'CODE'
$target = PokeFirstRef(3 - $player, 'Active');
$evolution = $target !== '' && !in_array('Basic', EffectiveCardSubtypes(GetZoneObject($target)), true);
PokeDealAttackDamage($player, $mzID, $evolution ? 280 : 140);
if ($evolution) PokeDiscardAttackEnergy($player, $mzID);
CODE);
$cards['me02.5-142'][] = $ability('ActivateAbility', 'Flip the Script', 'PokeSetVar("flipTheScript:" . $player, GetTurnNumber()); PokeDraw($player, 3, false);',
    'return PokeVar("knockedOutOnOpponentTurn:" . $player, -1) === GetTurnNumber() - 1 && PokeVar("flipTheScript:" . $player, -1) !== GetTurnNumber() && PokeCount($player, "Deck") > 0;');
$cards['me02.5-142'][] = $ability('Attack', 'Cruel Arrow', <<<'CODE'
$targets = implode('&', PokeFieldRefs(3 - $player));
$chosen = await $player.MZChoose($targets, 'Cruel_Arrow:_choose_an_opposing_Pokémon');
PokeDealAttackDamage($player, $mzID, 100, false, $chosen);
CODE);
$cards['sv06-145'][] = $ability('TrainerPlayed', 'Carmine', 'PokeDiscardSelection($player, PokeCandidates($player, "Hand")); PokeDraw($player, 5, false);');
$cards['me03-074'][] = $ability('TrainerPlayed', 'Hole-Digging Shovel', <<<'CODE'
for ($i = 0; $i < 2; ++$i) {
    $top = PokeFirstRef($player, 'Deck');
    if ($top !== '') PokeMoveSimple($top, $player, 'Discard');
}
CODE, 'return PokeCount($player, "Deck") > 0;');
$cards['me03-079'][] = $ability('TrainerPlayed', 'Naveen', <<<'CODE'
$targets = PokeCandidates($player, 'Hand');
$maximum = PokeCount($player, 'Hand');
$minimum = max(0, $maximum - 4);
if ($maximum > 0) {
    $chosen = await $player.MZMultiChoose($targets, $minimum, $maximum, 'Naveen:_discard_any_number_of_cards_then_draw_until_five');
    PokeDiscardSelection($player, $chosen);
}
PokeDraw($player, max(0, 5 - PokeCount($player, 'Hand')), false);
CODE, 'return PokeCount($player, "Deck") > 0;');
$cards['me04-086'][] = $ability('EnergyAttached', 'Nitro Fire Energy', 'return;');
$cards['me04-080'][] = $ability('TrainerPlayed', 'Prism Tower', 'PokePlayStadium($player, $mzID);');
$cards['me04-080'][] = $ability('ActivateAbility', 'Prism Tower', <<<'CODE'
PokeSetVar('prismTower:' . $player, GetTurnNumber());
$targets = PokeCandidates($player, 'Hand');
$discard = await $player.MZMultiChoose($targets, 2, 2, 'Prism_Tower:_discard_two_cards_to_draw_one');
PokeDiscardSelection($player, $discard);
PokeDraw($player, 1, false);
CODE, 'return PokeVar("prismTower:" . $player, -1) !== GetTurnNumber() && PokeCount($player, "Hand") >= 2 && PokeCount($player, "Deck") > 0;');
$cards['me02-089'][] = $ability('TrainerPlayed', 'Firebreather', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'basicFire');
$chosen = await $player.MZMultiChoose($targets, 0, 7, 'Firebreather:_search_for_up_to_seven_Basic_Fire_Energy');
PokeResolveSearch($player, $chosen);
CODE);
return $cards;
