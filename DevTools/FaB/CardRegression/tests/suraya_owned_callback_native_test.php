<?php
/** Seeded earned-event integration control, not a recorded gameplay witness. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
set_error_handler(function($n,$m,$f,$l){if(!(error_reporting()&$n))return false;throw new ErrorException($m,0,$n,$f,$l);});
$checks=0;
function SC(bool $ok,string $why):void{++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($why);}
function SA(int $player,int $mode,string $card):void{$r=EngineRunAction(['playerID'=>$player,'mode'=>$mode,'cardID'=>$card,'inputText'=>''],'FaBSim',$GLOBALS['gameName'],['updateCache'=>false]);if(!$r['success'])throw new RuntimeException($r['message']);ParseGamestate('./FaBSim/');}
foreach(['EMPTY','ACCEPT','PASS'] as $case){
 $slug='repair-war-cry-themis-yellow-four-live-20261126-'.($case==='EMPTY'?'empty-soul-owned-attack-layer-eight-damage':'suraya-soul-choice-draw-two-eight-damage');
 $gameName='regression_suraya_owned_component_'.uniqid();$dir=$repo.'/FaBSim/Games/'.$gameName;RegressionEnsureDir($dir);
 file_put_contents($dir.'/Gamestate.txt',RegressionNormalizeGamestateTextForRoot('FaBSim',file_get_contents($repo.'/Tests/Integration/FaBSim/'.$slug.'/initial_gamestate.txt')));
 $GLOBALS['gameName']=$gameName;$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
 try{
  ParseGamestate('./FaBSim/');
  // This deliberately seeds one already-earned event; it does not simulate source destruction.
  $proxy=AddCombatChain(1,CardID:'suraya_archangel_of_erudition',Owner:0,Controller:1,Role:'ATTACK',ChainLink:1,FromZone:'Arena');$sourceUID=intval($proxy->UniqueID);
  SC(FaBQueueOwnedEventMacro(1,$proxy,'AttackDeclared',['attacker'=>'p1CombatChain-0','defender'=>'p4Hero-0'])===1,'Exact Erudition event is admitted');
  $layerUID=intval(FaBStackTop()->UniqueID);$proxy->Remove();
  $s=FaBGetState();$s['combatOpen']=false;$s['window']='PRIORITY';$s['priorityPasses']=0;FaBSetState($s);SetPriorityPlayer(1);WriteGamestate('./FaBSim/');
  SC(FaBFindUID($sourceUID)===null,'Attack proxy is absent before callback begins');
  foreach([1,2,3,4] as $seat)SA($seat,10001,'!CustomInput!Pass');
  if($case!=='EMPTY'){
   SC((GetDecisionQueue(1)[0]->Type??'')==='MZMAYCHOOSE','Real generated optional Soul decision is on captured controller');
   SC(intval(FaBStackTop()->UniqueID??0)===$layerUID,'Earned layer stays on Stack while generated await is pending');
   SC((FaBGetState()['resolvingReaction']['resolutionClass']??'')==='ROS_EVENT','Managed resolver still owns the generated await');
   SC(count(GetHand(1))===2&&count(GetDeck(1))===2&&count(GetSoul(1))===1,'No early payment or draw');
   // A response from another seat cannot retire or steal this controller's choice.
   EngineRunAction(['playerID'=>2,'mode'=>10001,'cardID'=>'!CustomInput!Pass','inputText'=>''],'FaBSim',$gameName,['updateCache'=>false]);ParseGamestate('./FaBSim/');
   SC((GetDecisionQueue(1)[0]->Type??'')==='MZMAYCHOOSE'&&intval(FaBStackTop()->UniqueID??0)===$layerUID,'Foreign pass cannot complete the pending layer');
   SA(1,100,$case==='ACCEPT'?'p1Soul-0':'PASS');
   SC(count(GetSoul(1))===($case==='ACCEPT'?0:1)&&count(GetBanish(1))===($case==='ACCEPT'?1:0),'Optional cost has exact Soul/Banish outcome');
   SC(count(GetHand(1))===($case==='ACCEPT'?4:2)&&count(GetDeck(1))===($case==='ACCEPT'?0:2),'Exactly two cards drawn only on accepted cost');
  }else SC(count(GetHand(1))===2&&count(GetDeck(1))===0&&count(GetSoul(1))===0,'Empty Soul resolves without draw');
  $s=FaBGetState();$vars=json_decode((string)GetDecisionQueueVariables(),true);
  SC(FaBStackTop()===null&&empty($s['resolvingReaction']),'Owned layer completes once after callback');
  SC(!GetDecisionQueue(1)&&!GetDecisionQueue(2)&&!GetDecisionQueue(3)&&!GetDecisionQueue(4)&&empty($vars['__awaitFrames']),'All native queues and await frames clear');
  $before=[count(GetHand(1)),count(GetSoul(1)),count(GetBanish(1)),count(GetDeck(1))];FaBFinishReactionResolution();
  SC($before===[count(GetHand(1)),count(GetSoul(1)),count(GetBanish(1)),count(GetDeck(1))],'Repeated completion cannot duplicate draw or cost');
  echo 'PASS '.$case.' actual generated callback with seeded earned layer and absent proxy'.PHP_EOL;
 }finally{
  $actual=realpath($dir);$base=realpath($repo.'/FaBSim/Games');
  if($actual!==false&&$base!==false&&str_starts_with(str_replace('\\','/',$actual),str_replace('\\','/',$base).'/regression_suraya_owned_component_'))RegressionDeleteDirRecursive($actual);
 }
}
echo 'PASS '.$checks.' seeded native-entry/component checks; no recording, fixture, or coverage credit'.PHP_EOL;

