<?php
/** Mega Lucario package, saved through CardAbilityRepository by setup.php. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me01-076'][]=$ability('Attack','Accelerating Stab', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 30);
GetZoneObject($mzID)->TurnEffects['attackLock:Accelerating Stab'] = GetTurnNumber() + 2;
CODE, 'return (GetZoneObject($mzID)->TurnEffects["attackLock:Accelerating Stab"] ?? -1) !== GetTurnNumber();');
$cards['me01-077'][]=$ability('Attack','Aura Jab', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 130);
$targets = PokeCandidates($player, 'Discard', 'basicFighting');
if ($targets !== '' && PokeCount($player, 'Bench') > 0) {
    $chosen = await $player.MZMultiChoose($targets, 0, 3, 'Aura_Jab:_choose_up_to_three_Basic_Fighting_Energy');
    $energies = explode('&', $chosen);
    for ($i = 0; $i < count($energies); ++$i) {
        $energy = $energies[$i];
        if ($energy !== '' && $energy !== '-' && $energy !== 'PASS') {
            $targets = PokeCandidates($player, 'Bench');
            $target = await $player.MZChoose($targets, 'Aura_Jab:_attach_this_Energy_to_a_Benched_Pokémon');
            PokeAttachEnergyFromZone($player, $energy, $target);
        }
    }
}
CODE);
$cards['me01-077'][]=$ability('Attack','Mega Brave', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 270);
GetZoneObject($mzID)->TurnEffects['attackLock:Mega Brave'] = GetTurnNumber() + 2;
CODE, 'return (GetZoneObject($mzID)->TurnEffects["attackLock:Mega Brave"] ?? -1) !== GetTurnNumber();');
$cards['me01-073'][]=$ability('PokemonEvolved','Heave-Ho Catcher', <<<'CODE'
$targets = PokeCandidates(3 - $player, 'Bench');
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, 'Heave-Ho_Catcher:_switch_in_an_opposing_Benched_Pokémon');
    if ($chosen !== '' && $chosen !== '-' && $chosen !== 'PASS') PokeSwitch(3 - $player, $chosen);
}
CODE);
$cards['me01-073'][]=$ability('Attack','Wild Press', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 210);
GetZoneObject($mzID)->Damage += 70;
GetZoneObject($mzID)->Counters['opposingAttackKO'] = false;
CODE);
$cards['sv05-145'][]=$ability('TrainerPlayed',"Ciphermaniac's Codebreaking", <<<'CODE'
$targets = PokeCandidates($player, 'Deck');
$chosen = await $player.MZMultiChoose($targets, 0, 2, 'Codebreaking:_search_for_up_to_two_cards');
PokeResolveSearch($player, $chosen, 'TempZone', false, true);
$targets = PokeCandidates($player, 'TempZone');
if ($targets !== '') {
    $top = await $player.MZChoose($targets, 'Codebreaking:_choose_the_card_to_put_on_top');
    PokeStackSearchedCards($player, $top);
}
CODE, 'return PokeCount($player, "Deck") > 0;');
$cards['me01-124'][]=$ability('TrainerPlayed','Premium Power Pro', 'PokeSetVar("premiumPowerPro:" . $player . ":" . GetTurnNumber(), PokeVar("premiumPowerPro:" . $player . ":" . GetTurnNumber(), 0) + 30);');
return $cards;
