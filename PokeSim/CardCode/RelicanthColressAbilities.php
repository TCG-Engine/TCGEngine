<?php
/** Authoring source for Relicanth v4 - Colress; retained by setup.php. */
$cards=[];
$ability=static fn($macro,$name,$code,$prereq='')=>['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
$cards['me03-068'][]=$ability('TrainerPlayed','Play Fossil','PokePlayBasicFromZone($player, $mzID);','return PokeCount($player, "Bench") < 5;');
$cards['me03-068'][]=$ability('ActivateAbility','Discard Fossil','PokeDiscardFossil($player, $mzID);');
$cards['me03-068'][]=$ability('IncomingAttackDamageModifier','Intimidating Jaw','return $subjectObj->Location === "Active" && $subjectObj->Controller !== $sourceObj->Controller ? -30 : 0;');
$cards['30th-126'][]=$ability('TrainerPlayed','Poké Pad', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'nonRuleBox');
$chosen = await $player.MZMayChoose($targets, 'Search_your_deck');
PokeResolveSearch($player, $chosen);
CODE);
$cards['sv08.5-114'][]=$ability('TrainerPlayed','Lacey','PokeShuffleHandDraw($player, PokeCount(3 - $player, "Prizes") <= 3 ? 8 : 4);');
$cards['sv06-165'][]=$ability('TrainerPlayed','Unfair Stamp','PokeShuffleHandDraw($player, 5); PokeShuffleHandDraw(3 - $player, 2);',
    'return PokeVar("knockedOutOnOpponentTurn:" . $player, -1) === GetTurnNumber() - 1;');
$cards['sv09-159'][]=$ability('EnergyAttached','Spiky Energy','return;');
$cards['sv09-159'][]=$ability('AttackDamageCounterReflect','Spiky Energy','return $subjectObj->Location === "Active" && $subjectObj->Controller !== $sourceObj->Controller ? 2 : 0;');
$cards['sv08.5-107'][]=$ability('TrainerPlayed',"Explorer's Guidance", <<<'CODE'
PokeRevealTop($player, 6);
$targets = PokeCandidates($player, 'TempZone');
$count = min(2, PokeCount($player, 'TempZone'));
$chosen = await $player.MZMultiChoose($targets, $count, $count, "Explorer's_Guidance:_keep_two_cards");
PokeResolveExplorersGuidance($player, $chosen);
CODE, 'return PokeCount($player, "Deck") > 0;');
$cards['sv06-155'][]=$ability('TrainerPlayed',"Lana's Aid", <<<'CODE'
$targets = PokeCandidates($player, 'Discard', 'nonRuleBoxOrBasicEnergy');
$maximum = min(3, count(explode('&', $targets)));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Lana's_Aid:_recover_up_to_three_Pokémon_or_basic_Energy");
PokeResolveRecovery($player, $chosen);
CODE, 'return PokeCandidates($player, "Discard", "nonRuleBoxOrBasicEnergy") !== "";');
return $cards;
