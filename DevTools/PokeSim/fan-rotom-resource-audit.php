<?php
/** Independent audit: count accepted, resolved opening attacks and their resources. */
require __DIR__.'/fan-rotom-experiment.php';
$deck=fanVariants()['no-jamming'];$result=[];
$noStadium=in_array('--no-opponent-stadium',$argv,true);
foreach($noStadium?[2]:[1,2] as $first){
 $eligible=$first===1?2:1;$sum=['games'=>500,'rotom'=>0,'colressBeforeSuccess'=>0,'lillieBeforeSuccess'=>0,'opponentStadiumSuccess'=>0,'naturalEnergyAtTurn1'=>0,'invalidResources'=>0];$examples=[];
 for($seed=20001;$seed<=20500;$seed++){
  PokeCreateGame($deck,PokeNamedDeck('brisbane-lopunny'),$seed,$first);
  $played=[];$usage=[];$seenStart=false;$steps=0;
  while(!GetWinner()&&$steps++<600){
   $seat=PokePendingPlayer();$view=PokeObservation($seat);
   if($noStadium&&$seat===2&&!$view['decision'])$view['actions']=array_values(array_filter($view['actions'],fn($a)=>$a['type']!=='trainer'||CardTrainerType(GetZoneObject($a['source'])->CardID)!=='Stadium'));
   $a=PokeBotChoose($view);if(!$a)throw new RuntimeException('No action');
   $turn=GetPlayerTurns(1);
   if(!$seenStart&&$seat===1&&$view['phase']==='MAIN'){$seenStart=true;$sum['naturalEnergyAtTurn1']+=(int)(bool)array_filter(PokeObjects(1,'Hand'),fn($c)=>CardType($c->CardID)==='Energy');}
   if($seat===1&&$a['type']==='trainer'){$id=GetZoneObject($a['source'])->CardID;$played[$id]=true;if(CardTrainerType($id)==='Supporter'&&($usage[$turn]['supporter']=($usage[$turn]['supporter']??0)+1)>1)throw new RuntimeException('Multiple Supporters');}
   if($seat===1&&$a['type']==='attach'&&($usage[$turn]['attach']=($usage[$turn]['attach']??0)+1)>1)throw new RuntimeException('Multiple attachments');
   $success=$seat===1&&$a['type']==='attack'&&$turn===$eligible&&GetZoneObject($a['source'])->CardID==='sv07-118'&&PokeStadium()!==null;
   if($success){
    $obj=GetZoneObject($a['source']);$cost=PokeAttackCost($obj,CardAttacks($obj->CardID)[$a['index']]['cost']);
    if(!$obj->Energy||!PokeHasAttackEnergy($obj,$cost)||$a['source']!==PokeFirstRef(1,'Active'))throw new RuntimeException('Invalid attack resources');
    $example=['seed'=>$seed,'ownTurn'=>$turn,'energy'=>$obj->Energy,'stadium'=>PokeStadium()->CardID,'stadiumOwner'=>PokeStadium()->Controller,'colress'=>isset($played['sv06.5-057']),'lillie'=>isset($played['me02.5-192'])];
   }
   PokeApplyAction($a);
   if($success){
    $row=PokeOpeningResults()[0];if(!$row['attackResolved']||!$row['fullyEnabledAttack'])throw new RuntimeException('Attack did not resolve');
    ++$sum['rotom'];$sum['colressBeforeSuccess']+=(int)$example['colress'];$sum['lillieBeforeSuccess']+=(int)$example['lillie'];$sum['opponentStadiumSuccess']+=(int)($example['stadiumOwner']===2);
    if(count($examples)<4)$examples[]=$example;
    break;
   }
   if(GetPlayerTurns(1)>$eligible||$seat===1&&$turn===$eligible&&in_array($a['type'],['attack','end'],true))break;
  }
  if($steps>=600)throw new RuntimeException('Audit action cap');
 }
 $result[$first===1?'first':'second']=['counts'=>$sum,'examples'=>$examples];
 echo json_encode([$first,$sum])."\n";
}
file_put_contents(__DIR__.'/fixtures/fan-rotom-resource-audit'.($noStadium?'-no-opponent-stadium':'').'.json',json_encode($result,JSON_PRETTY_PRINT)."\n");
