<?php
/** Brisbane Lopunny authoring source; setup saves via CardAbilityRepository. */
$cards = []; $base = require __DIR__.'/DeckAbilities.php';
$ability = static fn($macro,$name,$code,$prereq='') => ['macroName'=>$macro,'abilityName'=>$name,'abilityCode'=>$code,'prereqCode'=>$prereq,'isImplemented'=>true];
foreach (['me01-114'=>'me02.5-183','me01-119'=>'me02.5-192','me01-131'=>'me02.5-213','sv01-186'=>'sv10.5b-084'] as $id=>$original) $cards[$id]=$base[$original];
foreach (['sv09-120','me02-083'] as $id) $cards[$id][]=$ability('Attack','Switch to Bench', <<<'CODE'
$targets = PokeCandidates($player, 'Bench');
if ($targets !== '') {
    $chosen = await $player.MZChoose($targets, 'Switch_to_a_Benched_Pokémon');
    PokeSwitch($player, $chosen);
}
CODE);
$cards['me02-084'][]=$ability('Attack','Gale Thrust','PokeDealAttackDamage($player, $mzID, 60 + (PokeMovedToActiveThisTurn($mzID) ? 170 : 0));');
$cards['me02-084'][]=$ability('Attack','Spiky Hopper','PokeDealAttackDamage($player, $mzID, 160, true);');
$cards['sv09-121'][]=$ability('Attack','Tenacious Tail','PokeDealAttackDamage($player, $mzID, 60 * PokeCountEx(3 - $player));');
$cards['sv09-121'][]=$ability('Attack','Destructive Drill','PokeDealAttackDamage($player, $mzID, 150, true);');
$cards['sv05-129'][]=$ability('ActivateAbility','Run Away Draw', <<<'CODE'
PokeMarkAbilityUsed($mzID);
$before = PokeCount($player, 'Hand');
PokeDraw($player, 3, false);
if (PokeCount($player, 'Hand') > $before) PokeReturnPokemonStack($player, $mzID, 'Deck');
CODE, 'return PokeAbilityUnused($mzID);');
$cards['sv07-118'][]=$ability('ActivateAbility','Fan Call', <<<'CODE'
PokeSetVar('FanCall:' . $player, GetTurnNumber());
$targets = PokeCandidates($player, 'Deck', 'fanCall');
$chosen = await $player.MZMultiChoose($targets, 0, 3, 'Fan_Call:_search_up_to_three_small_Colorless_Pokémon');
PokeResolveSearch($player, $chosen);
CODE, 'return GetPlayerTurns($player) === 1 && PokeVar("FanCall:" . $player) !== GetTurnNumber();');
$cards['sv07-118'][]=$ability('Attack','Assault Landing','if (PokeStadium()) PokeDealAttackDamage($player, $mzID, 70);');
$cards['30th-066'][]=$ability('AttackCopyAllowed','Memory Helix','return 1;');
$cards['30th-066'][]=$ability('Attack','Teleportation Burst', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 30);
$targets = PokeCandidates($player, 'Bench');
if ($targets !== '') {
    $chosen = await $player.MZMayChoose($targets, 'Teleportation_Burst:_switch_to_Bench');
    if ($chosen !== '-') PokeSwitch($player, $chosen);
}
CODE);
$cards['sv10-010'][]=$ability('BenchDamageProtection','Flower Curtain','return $subjectObj->Location === "Bench" && !PokeHasRuleBox($subjectObj->CardID) ? 1 : 0;');
$cards['me02.5-039'][]=$ability('SelfKnockoutAbilityLock','Damp','return 1;');
$cards['sv09-056'][]=$ability('DragonPsychicWeakness','Fairy Zone','return str_contains(EffectiveCardElement($subjectObj), "Dragon") ? 1 : 0;');
$cards['sv09-056'][]=$ability('Attack','Full Moon Rondo','PokeDealAttackDamage($player, $mzID, 20 + 20 * (PokeCount($player, "Bench") + PokeCount(3 - $player, "Bench")));');
$cards['sv08-056'][]=$ability('PokemonBenched','Snow Sink', <<<'CODE'
if (PokeStadium()) {
    $chosen = await $player.YesNo('Snow_Sink:_discard_the_Stadium');
    if ($chosen === 'YES') PokeDiscardStadium();
}
CODE);
$cards['sv08-056'][]=$ability('Attack','Icicle Loop', <<<'CODE'
PokeDealAttackDamage($player, $mzID, 120);
$maximum = count(GetZoneObject($mzID)->Energy) - 1;
$index = await $player.NumberChoose(0, $maximum, 'Icicle_Loop:_Energy_index_to_return');
PokeReturnAttachedEnergy($player, $mzID, (int)$index);
CODE);
$cards['me03-062'][]=$ability('PokemonBenched','Last-Ditch Catch', <<<'CODE'
PokeSetVar('LastDitch:' . $player, GetTurnNumber());
$targets = PokeCandidates($player, 'Deck', 'supporter');
$chosen = await $player.MZMayChoose($targets, 'Last-Ditch_Catch:_search_for_a_Supporter');
PokeResolveSearch($player, $chosen);
CODE, 'return PokeVar("LastDitch:" . $player) !== GetTurnNumber();');
$cards['me03-062'][]=$ability('Attack','Tuck Tail', 'PokeDealAttackDamage($player, $mzID, 60); PokeReturnPokemonStack($player, $mzID, "Hand");');
$cards['sv05-144'][]=$ability('TrainerPlayed','Buddy-Buddy Poffin', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'poffin');
$maximum = min(2, 5 - PokeCount($player, 'Bench'));
$chosen = await $player.MZMultiChoose($targets, 0, $maximum, 'Buddy-Buddy_Poffin:_Bench_small_Basic_Pokémon');
PokeResolveSearch($player, $chosen, 'Bench');
CODE, 'return PokeCount($player, "Bench") < 5;');
$cards['sv10.5w-084'][]=$ability('TrainerPlayed','Hilda', <<<'CODE'
$targets = PokeCandidates($player, 'Deck', 'evolution');
$chosen = await $player.MZMayChoose($targets, 'Hilda:_find_an_Evolution_Pokémon');
PokeResolveSearch($player, $chosen, 'Hand', true, false);
$targets = PokeCandidates($player, 'Deck', 'energy');
$chosen = await $player.MZMayChoose($targets, 'Hilda:_find_an_Energy');
PokeResolveSearch($player, $chosen);
CODE);
$cards['me01-132'][]=$ability('TrainerPlayed',"Wally's Compassion", <<<'CODE'
$targets = PokeMegaTargets($player);
$chosen = await $player.MZChoose($targets, 'Wally:_heal_a_Mega_Evolution_Pokémon_ex');
PokeHealMega($player, $chosen);
CODE, 'return PokeMegaTargets($player) !== "";');
$cards['me02.5-181'][]=$ability('TrainerPlayed','Air Balloon', <<<'CODE'
$targets = PokeToolTargets($player);
$chosen = await $player.MZChoose($targets, 'Air_Balloon:_attach_to_a_Pokémon');
PokeAttachTool($player, $mzID, $chosen);
CODE, 'return PokeToolTargets($player) !== "";');
$cards['me02.5-181'][]=$ability('RetreatCostModifier','Air Balloon','return -2;');
$cards['me02-085'][]=$ability('TrainerPlayed','Battle Cage','PokePlayStadium($player, $mzID);');
$cards['me02-085'][]=$ability('DamageCounterProtection','Battle Cage','return $subjectObj->Location === "Bench" ? 1 : 0;');
$cards['sv05-161'][]=$ability('EnergyAttached','Mist Energy','return;');
$cards['sv05-161'][]=$ability('AttackEffectProtection','Mist Energy','return 1;');
$cards['sv08-191'][]=$ability('EnergyAttached','Enriching Energy','PokeDraw($player, 4, false);');
return $cards;
