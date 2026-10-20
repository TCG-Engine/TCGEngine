<?php
declare(strict_types=1);
$repo=__DIR__;while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
chdir($repo);require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
InitializeGamestate();SetSeatOrder('12');SetLiveSeats('12');SetTurnPlayer(1);FaBSetState(FaBStateDefaults());
$attack=AddCombatChain(1,CardID:'wounding_blow_red',Owner:1,Controller:1,Role:'ATTACK',FromZone:'Hand');
$state=FaBGetState();$state['combatOpen']=true;$state['attacker']=1;$state['defender']=2;$state['attackUID']=intval($attack->UniqueID);FaBSetState($state);
$expect=function(array $assertion,bool $want,string $description):void {
 [$actual,$message]=RegressionEvaluateAssertion($assertion+['viewerPlayerID'=>1]);
 if($actual!==$want)throw new RuntimeException($description.': '.$message);
};
$check=['type'=>'computed_go_again_equals','mzId'=>'p1CombatChain-0','value'=>0];
$expect($check,true,'Printed no-go-again attack');
FaBWTRTag($attack,'GO_AGAIN');$expect($check,false,'Premature grant must fail expected zero');
$expect(array_replace($check,['value'=>1]),true,'Granted go again');
$attack->removed=true;
$expect($check,false,'Removed current attack cannot equal zero');
$expect(array_replace($check,['value'=>1]),false,'Removed current attack cannot equal one');
$attack->removed=false;
$expect(array_replace($check,['mzId'=>'p1CombatChain-99']),false,'Missing object cannot equal zero');
AddHand(1,CardID:'wounding_blow_red',Owner:1,Controller:1);
$expect(array_replace($check,['mzId'=>'p1Hand-0']),false,'Non-current object cannot equal zero');
[$normalized,$error]=RegressionBuildAssertionFromInput(1,['type'=>'computed_go_again_equals','mzId'=>'p1CombatChain-0','value'=>'1']);
if($error!==null||($normalized['value']??null)!==1)throw new RuntimeException('Go-again assertion normalization lost integer value');
echo "Go-again assertion checks passed: false, true, injected premature grant, missing and unrelated objects.\n";
