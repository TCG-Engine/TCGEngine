<?php
/** Fossil deck authoring source, saved through setup.php and regenerated. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me05-017'][]=$ability('Attack','Fossil Beatdown','PokeDealAttackDamage($player, $mzID, 10 + 30 * PokeFossilBenchCount($player));');
foreach(['me05-072','me05-073','sv10.5w-079','sv07-130','sv07-129'] as $id){
    $cards[$id][]=$ability('TrainerPlayed','Play Fossil','PokePlayBasicFromZone($player, $mzID);','return PokeCount($player, "Bench") < 5;');
    $cards[$id][]=$ability('ActivateAbility','Discard Fossil','PokeDiscardFossil($player, $mzID);');
}
$cards['sv07-129'][]=$ability('AttackEffectProtection','Protective Cover','return 1;');
$cards['sv10.5w-079'][]=$ability('BenchDamageProtection','Plume Protection','return $subjectObj->CardID === "sv10.5w-079" && $subjectObj->Location === "Bench" ? 1 : 0;');
$cards['me05-076'][]=$ability('TrainerPlayed','Fossil Quarry','PokePlayStadium($player, $mzID);');
$cards['me05-076'][]=$ability('ActivateAbility','Fossil Quarry', <<<'CODE'
PokeSetVar('quarry:' . $player, GetTurnNumber());
$targets = PokeCandidates($player, 'Deck', 'antiqueItem');
$maximum = min(2, 5 - PokeCount($player, 'Bench'));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Fossil_Quarry:_Bench_up_to_two_Antique_Items');
PokeResolveSearch($player, $chosen, 'Bench');
CODE, 'return PokeVar("quarry:" . $player) !== GetTurnNumber() && PokeCount($player, "Bench") < 5;');
$cards['sv06.5-057'][]=$ability('TrainerPlayed',"Colress's Tenacity", <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'stadium');
$chosen = await $player.MZMayChoose($targets, 'Colress:_choose_a_Stadium');
PokeResolveSearch($player, $chosen, 'Hand', true, false);
$targets = PokeCandidates($player, 'Deck', 'energy');
$chosen = await $player.MZMayChoose($targets, 'Colress:_choose_an_Energy');
PokeResolveSearch($player, $chosen);
CODE);
$cards['sv10.5w-080'][]=$ability('TrainerPlayed','Brave Bangle', <<<'CODE'
$targets = PokeToolTargets($player);
$chosen = await $player.MZChoose($targets, 'Brave_Bangle:_attach_to_a_Pokémon');
PokeAttachTool($player, $mzID, $chosen);
CODE, 'return PokeToolTargets($player) !== "";');
$cards['sv10.5w-080'][]=$ability('AttackDamageModifier','Brave Bangle','return !PokeHasRuleBox($sourceObj->CardID) && $subjectObj->Location === "Active" && CardSuffix($subjectObj->CardID) === "ex" ? 30 : 0;');
$cards['sv06-163'][]=$ability('TrainerPlayed','Secret Box', <<<'CODE'
$targets = PokeCandidates($player, 'Hand');
$discard = await $player.MZMultiChoose($targets, 3, 3, 'Secret_Box:_discard_three_cards');
PokeDiscardSelection($player, $discard);
$targets = PokeCandidates($player, 'Deck', 'item');
$chosen = await $player.MZMayChoose($targets, 'Secret_Box:_choose_an_Item');
PokeResolveSearch($player, $chosen, 'Hand', true, false);
$targets = PokeCandidates($player, 'Deck', 'tool');
$chosen = await $player.MZMayChoose($targets, 'Secret_Box:_choose_a_Pokémon_Tool');
PokeResolveSearch($player, $chosen, 'Hand', true, false);
$targets = PokeCandidates($player, 'Deck', 'supporter');
$chosen = await $player.MZMayChoose($targets, 'Secret_Box:_choose_a_Supporter');
PokeResolveSearch($player, $chosen, 'Hand', true, false);
$targets = PokeCandidates($player, 'Deck', 'stadium');
$chosen = await $player.MZMayChoose($targets, 'Secret_Box:_choose_a_Stadium');
PokeResolveSearch($player, $chosen);
CODE, 'return PokeCount($player, "Hand") >= (GetZoneObject($mzID)->Location === "Hand" ? 4 : 3);');
return $cards;
