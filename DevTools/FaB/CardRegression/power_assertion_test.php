<?php
declare(strict_types=1);
$repo=__DIR__;while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
chdir($repo);require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
InitializeGamestate();SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);FaBSetState(FaBStateDefaults());
AddHero(1,CardID:'ira_crimson_haze',Owner:1,Controller:1);AddHero(2,CardID:'ira_crimson_haze',Owner:2,Controller:2);
$attack=AddCombatChain(1,CardID:'wounding_blow_red',Owner:1,Controller:1,Role:'ATTACK',FromZone:'Hand');
$state=FaBGetState();$state['combatOpen']=true;$state['attacker']=1;$state['defender']=2;$state['attackUID']=intval($attack->UniqueID);FaBSetState($state);
$checks=0;
$expect=function(array $assertion,bool $want,string $description)use(&$checks):void {
 [$actual,$message]=RegressionEvaluateAssertion($assertion+['viewerPlayerID'=>1]);
 if($actual!==$want)throw new RuntimeException($description.': '.$message);
 ++$checks;
};
$check=['type'=>'computed_power_equals','mzId'=>'p1CombatChain-0','value'=>4];
$expect($check,true,'Printed attack action power4');
FaBWTRTag($attack,'WTR_POWER:2');$expect($check,false,'Power change fails expected printed value');
$expect(array_replace($check,['value'=>6]),true,'Current modifiers counted');
$attack->CardID='dawnblade';$attack->TurnEffects=[];
$expect(array_replace($check,['value'=>3]),true,'Active weapon base power3');
$attack->removed=true;$expect(array_replace($check,['value'=>3]),false,'Removed object rejected');$attack->removed=false;
$expect(array_replace($check,['mzId'=>'p1CombatChain-99','value'=>0]),false,'Missing cannot equal zero');
AddHand(1,CardID:'wounding_blow_red',Owner:1,Controller:1);
$expect(array_replace($check,['mzId'=>'p1Hand-0']),false,'Unrelated object rejected');
$attack->Role='DEFENSE';$expect(array_replace($check,['value'=>3]),false,'Defending card not current attack');$attack->Role='ATTACK';
$state=FaBGetState();$state['combatOpen']=false;FaBSetState($state);
$expect(array_replace($check,['value'=>3]),false,'Closed combat rejected');
echo "Native power assertion checks passed: $checks.\n";
