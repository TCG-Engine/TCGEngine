<?php
/** Fan Rotom opening deck printings; saved by setup.php, never edit generated macros. */
$lopunny = require __DIR__.'/LopunnyAbilities.php';
$cards = ['sv08.5-080'=>$lopunny['sv05-129']];
$cards['sv08.5-079'] = [
 ['macroName'=>'Attack','abilityName'=>'Gnaw','abilityCode'=>'PokeDealAttackDamage($player, $mzID, 10);','isImplemented'=>true],
 ['macroName'=>'Attack','abilityName'=>'Dig','abilityCode'=>'PokeDealAttackDamage($player, $mzID, 30); if (PokeRandom(1)) GetZoneObject($mzID)->TurnEffects["digThrough"] = GetTurnNumber() + 1;','isImplemented'=>true],
];
foreach (['sv06-153'=>'Jamming Tower','sv08-177'=>'Gravity Mountain','me02.5-197'=>'Nighttime Mine'] as $id=>$name)
 $cards[$id] = [['macroName'=>'TrainerPlayed','abilityName'=>$name,'abilityCode'=>'PokePlayStadium($player, $mzID); PokeResolveKnockouts();','isImplemented'=>true]];
$cards['me02.5-197'][] = ['macroName'=>'AttackCostModifier','abilityName'=>'Nighttime Mine',
 'abilityCode'=>'return in_array("Tera", EffectiveCardSubtypes($subjectObj), true) ? 1 : 0;','isImplemented'=>true];
return $cards;
