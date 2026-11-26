<?php
/** Normal entry-point integration with production entropy; no snapshot order or fixture credit. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
set_error_handler(function($n,$m,$f,$l){if(!(error_reporting()&$n))return false;throw new ErrorException($m,0,$n,$f,$l);});
$fixture=$repo.'/Tests/Integration/FaBSim/repair-lessons-learned-blue-four-live-20261126-one-reaction-returned-to-deck';
$actions=json_decode(file_get_contents($fixture.'/actions.json'),true,512,JSON_THROW_ON_ERROR);$checks=0;
function NC(bool $ok,string $why):void{++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($why);}
function NA(array $a):void{$r=EngineRunAction($a,'FaBSim',$GLOBALS['gameName'],['updateCache'=>false]);if(!$r['success'])throw new RuntimeException($r['message']);ParseGamestate('./FaBSim/');}
function NU(array $zone):array{$ids=array_map(fn($o)=>intval($o->UniqueID),array_filter($zone,fn($o)=>empty($o->removed)));sort($ids);return $ids;}
foreach(['THREE_WITH_DUPLICATE_RETRY','ZERO']as$case){
 $gameName='regression_lessons_native_component_'.uniqid();$dir=$repo.'/FaBSim/Games/'.$gameName;RegressionEnsureDir($dir);
 file_put_contents($dir.'/Gamestate.txt',RegressionNormalizeGamestateTextForRoot('FaBSim',file_get_contents($fixture.'/initial_gamestate.txt')));
 $GLOBALS['gameName']=$gameName;$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
 try{
  ParseGamestate('./FaBSim/');
  // Legal midgame assets from the original diagnostic; each actual effect uses native game actions.
  AddDeck(1,CardID:'wounding_blow_red',Owner:1,Controller:1);AddDeck(1,CardID:'wounding_blow_blue',Owner:1,Controller:1);
  if($case==='THREE_WITH_DUPLICATE_RETRY')AddGraveyard(1,CardID:'pummel_blue',Owner:1,Controller:1);
  WriteGamestate('./FaBSim/');
  foreach(array_slice($actions,0,5)as$a)NA($a);
  NC((GetDecisionQueue(1)[0]->Type??'')==='MZMULTICHOOSE','Actual generated manual choice is pending');
  NC(intval(FaBStackTop()->UniqueID??0)===5,'Physical Lessons remains on Stack during its await');
  NC(NU(GetDeck(1))===[9,10]&&count(GetHand(1))===0,'No early movement or draw');
  if($case==='THREE_WITH_DUPLICATE_RETRY'){
   NA(['playerID'=>1,'mode'=>100,'cardID'=>'p1Graveyard-1&p1Graveyard-3','inputText'=>'']);
   NC((GetDecisionQueue(1)[0]->Type??'')==='MZMULTICHOOSE'&&intval(FaBStackTop()->UniqueID??0)===5,'Same-name pitch siblings require a fresh valid choice without retiring source');
   NC(NU(GetGraveyard(1))===[6,7,8,11]&&NU(GetDeck(1))===[9,10],'Invalid complete selection moves no partial card');
   NA(['playerID'=>1,'mode'=>100,'cardID'=>'p1Graveyard-0&p1Graveyard-1&p1Graveyard-2','inputText'=>'']);
   NC(NU(GetDeck(1))===[6,7,8,9,10],'All three distinct physical reactions joined the original deck');
   NC(NU(GetGraveyard(1))===[5,11],'Unselected sibling stays in Graveyard and source completes there');
  }else{
   NA(['playerID'=>1,'mode'=>100,'cardID'=>'-','inputText'=>'']);
   NC(NU(GetDeck(1))===[9,10]&&NU(GetGraveyard(1))===[5,6,7,8],'Zero choice keeps all reaction assets and completes source');
  }
  $state=FaBGetState();$vars=json_decode((string)GetDecisionQueueVariables(),true);
  NC(FaBStackTop()===null&&empty($state['resolvingReaction'])&&empty($vars['__awaitFrames']),'Source lifecycle and generated frame complete');
  NC(!GetDecisionQueue(1)&&!GetDecisionQueue(2)&&!GetDecisionQueue(3)&&!GetDecisionQueue(4),'All native queues clear');
  NC(intval(GetActionPoints(1))===1&&intval(GetResources(1))===0&&intval(GetHealth(1))===20,'Instant neither spends nor grants an action/resource/life');
  echo 'PASS '.$case.' native outcome; production shuffle order intentionally not asserted'.PHP_EOL;
 }finally{
  $actual=realpath($dir);$base=realpath($repo.'/FaBSim/Games');
  if($actual!==false&&$base!==false&&str_starts_with(str_replace('\\','/',$actual),str_replace('\\','/',$base).'/regression_lessons_native_component_'))RegressionDeleteDirRecursive($actual);
 }
}
echo 'PASS '.$checks.' native-entry integration checks, no saved-fixture credit; multi-card order replay unqualified'.PHP_EOL;
