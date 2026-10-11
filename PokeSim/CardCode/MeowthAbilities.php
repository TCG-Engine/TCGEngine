<?php
/** Versioned authoring source, saved by setup.php. */
$cards = [];
$ability = static fn($macro, $name, $code, $prereq = '') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['30th-101'][] = $ability('Attack', 'Pay Day', 'PokeDealAttackDamage($player, $mzID, 10); PokeDraw($player, 1, false);');
$cards['30th-101'][] = $ability('Attack', 'Treasure Rush', 'PokeDealAttackDamage($player, $mzID, 10 * PokeCount($player, "Hand"));');
$cards['sv07-106'][] = $ability('Attack', 'Hammer In', 'PokeDealAttackDamage($player, $mzID, 30);');
$cards['sv07-106'][] = $ability('Attack', 'Raging Hammer', 'PokeDealAttackDamage($player, $mzID, 80 + GetZoneObject($mzID)->Damage);');
$cards['me01-094'][] = $ability('Attack', 'Gobble Down', 'PokeDealAttackDamage($player, $mzID, 80 * (6 - PokeCount($player, "Prizes")));');
$cards['me01-094'][] = $ability('Attack', 'Huge Bite', 'PokeDealAttackDamage($player, $mzID, GetZoneObject(PokeFirstRef(3 - $player, "Active"))->Damage > 0 ? 30 : 260);');
$cards['sv08-130'][] = $ability('Attack', 'Metal Defender', 'PokeDealAttackDamage($player, $mzID, 220); GetZoneObject($mzID)->TurnEffects["noWeaknessThrough"] = GetTurnNumber() + 1;');
$cards['sv08-130'][] = $ability('PokemonEvolved', 'Assemble Alloy', <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'basicMetal');
if ($targets !== '') {
    $selected = await $player.MZMultiChoose($targets, 0, 2, 'Assemble_Alloy:_choose_up_to_two_Basic_Metal_Energy');
    $energies = explode('&', $selected);
    for ($i = 0; $i < count($energies); ++$i) {
        $energy = $energies[$i];
        if ($energy !== '-' && $energy !== '' && $energy !== 'PASS') {
            $targets = PokeMetalTargets($player);
            $target = await $player.MZChoose($targets, 'Assemble_Alloy:_attach_to_a_Metal_Pokémon');
            PokeAttachDiscardMetal($player, $energy, $target);
        }
    }
}
CODE);
$cards['me02.5-258'][] = $ability('TrainerPlayed', 'Cheren', 'PokeDraw($player, 3, false);');
$cards['me05-078'][] = $ability('TrainerPlayed', 'Gwynn', <<<'CODE'
$targets = PokeCandidates($player, 'Hand', 'nonRuleBox');
$selected = await $player.MZMultiChoose($targets, 0, 2, 'Gwynn:_discard_up_to_two_non_Rule_Box_Pokémon');
$count = count(array_filter(explode('&', $selected), static fn($ref)=>$ref !== '' && $ref !== '-' && $ref !== 'PASS'));
PokeDiscardSelection($player, $selected);
PokeDraw($player, 3 * $count, false);
CODE);
$cards['sv08.5-004'][] = $ability('Attack', 'Itchy Pollen', 'PokeDealAttackDamage($player, $mzID, 10); PokeSetVar("itemLock:" . (3 - $player), GetTurnNumber() + 1);');
$cards['me01-130'][] = $ability('TrainerPlayed', 'Switch', <<<'CODE'
$targets = PokeCandidates($player, 'Bench');
$selected = await $player.MZChoose($targets, 'Switch:_choose_a_Benched_Pokémon');
PokeSwitch($player, $selected);
CODE, 'return PokeCount($player, "Bench") > 0;');
return $cards;
