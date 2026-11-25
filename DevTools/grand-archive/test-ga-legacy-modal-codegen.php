<?php
if (PHP_SAPI !== 'cli') exit;
chdir(dirname(__DIR__, 2));
$generator=file_get_contents('zzGameCodeGenerator.php');
// Load the argument parser without executing the generator or contacting the DB.
$start=strpos($generator,'function ParseAwaitArgs(');
$end=strpos($generator,'function BuildAddDecisionCall(',$start);
eval(substr($generator,$start,$end-$start));
require 'GrandArchiveSim/Custom/CodeGeneration.php';
$source='$choice = await $player.Modal("A:_Rest_CARDNAME&B:_Do_not_rest", "Rest_to_deal_1_damage?");';
$normalized=GAPrepareLegacyModalCode($source);
if(!str_contains($normalized,".Modal(1, 1, '_Rest_CARDNAME&_Do_not_rest'")) throw new RuntimeException('Shorthand not normalized');
$continuation=substr($normalized,strpos($normalized,";\n")+2);
foreach(['0'=>'A','1'=>'B','-'=>'-','PASS'=>'-','999'=>'-'] as $input=>$expected) {
 $choice=(string)$input; eval($continuation);
 if($choice!==$expected) throw new RuntimeException('Incorrect legacy choice mapping');
}
$modern='$choice = await $player.Modal(1, 1, "Draw&Heal", "Choose");';
if(GAPrepareLegacyModalCode($modern)!==$modern) throw new RuntimeException('Modern modal changed');
echo "7 legacy modal compatibility checks passed\n";
