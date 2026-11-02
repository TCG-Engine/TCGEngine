<?php
/** Lunatone/Solrock package for the Fighting splash. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me01-074'][]=$ability('ActivateAbility','Lunar Cycle', <<<'CODE'
PokeSetVar('lunarCycle:' . $player, GetTurnNumber());
$targets = PokeCandidates($player, 'Hand', 'basicFighting');
$chosen = await $player.MZChoose($targets, 'Lunar_Cycle:_discard_a_Basic_Fighting_Energy_to_draw_three');
PokeDiscardSelection($player, $chosen);
PokeDraw($player, 3, false);
CODE, <<<'CODE'
return PokeVar('lunarCycle:' . $player, -1) !== GetTurnNumber()
    && PokeCount($player, 'Deck') > 0
    && PokeCandidates($player, 'Hand', 'basicFighting') !== ''
    && count(array_filter(PokeFieldRefs($player), fn($ref) => CardName(GetZoneObject($ref)->CardID) === 'Solrock')) > 0;
CODE);
$cards['me01-074'][]=$ability('Attack','Power Gem','PokeDealAttackDamage($player, $mzID, 50);');
$cards['me01-075'][]=$ability('Attack','Cosmic Beam', <<<'CODE'
$lunatone = array_filter(PokeObjects($player, 'Bench'), fn($obj) => CardName($obj->CardID) === 'Lunatone');
if (count($lunatone) > 0) PokeDealAttackDamage($player, $mzID, 70, false, null, true);
CODE);
$cards['me01-116'][]=$ability('TrainerPlayed','Fighting Gong', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'fightingGong');
$chosen = await $player.MZMayChoose($targets, 'Fighting_Gong:_find_a_Basic_Fighting_Energy_or_Basic_Fighting_Pokémon');
PokeResolveSearch($player, $chosen);
CODE);
return $cards;
