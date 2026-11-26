<?php
/** Isolated diagnostic through the normal entry point; no recording or fixture writes. */
declare(strict_types=1);
$repo=str_replace('\\','/',dirname(__DIR__,4));chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
set_error_handler(function($level,$message,$file,$line){if(!(error_reporting()&$level))return false;throw new ErrorException($message,0,$level,$file,$line);});
$slug='repair-emerging-avalanche-red-four-live-20261125-declined-fusion-declared-target-false-owned-entry-layer-no-frostbite';
$fixture=$repo.'/Tests/Integration/FaBSim/'.$slug;
$gameName='regression_emerging_shortcut_'.uniqid();$gameDir=$repo.'/FaBSim/Games/'.$gameName;
RegressionEnsureDir($gameDir);file_put_contents($gameDir.'/Gamestate.txt',RegressionNormalizeGamestateTextForRoot('FaBSim',file_get_contents($fixture.'/initial_gamestate.txt')));
$GLOBALS['gameName']=$gameName;$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
$actions=json_decode(file_get_contents($fixture.'/actions.json'),true,512,JSON_THROW_ON_ERROR);
$checks=0;
function EC(bool $ok,string $why):void{++$GLOBALS['checks'];if(!$ok)throw new RuntimeException($why);}
function EA(array $a):void{$r=EngineRunAction($a,'FaBSim',$GLOBALS['gameName'],['updateCache'=>false]);if(!$r['success'])throw new RuntimeException($r['message']);ParseGamestate('./FaBSim/');}
try{
 ParseGamestate('./FaBSim/');
 // Legal midgame setup variant: no extra playable Potion remains after paying the Aura.
 foreach(GetHand(1) as $o)if(intval($o->UniqueID)===7)$o->Remove();
 WriteGamestate('./FaBSim/');
 $prefs=GetShortcutPreferencesState(1);$prefs['holdPriority']=false;$prefs['windows']['ACTION_PRIORITY']=true;
 EA(['playerID'=>1,'mode'=>10015,'inputText'=>json_encode($prefs)]);
 EC(ShouldAutoPassShortcutWindow(1,'ACTION_PRIORITY'),'Actual Action shortcut must be enabled');
 EC(count(GetHand(1))===1,'Only real Ice funding card remains in Hand');
 foreach(array_slice($actions,0,7) as $index=>$a){if($index>=3)$a['playerID']=intval(GetPriorityPlayer());EA($a);}
 $s=FaBGetState();$q=GetDecisionQueue(1);$source=FaBFindUID(5);
 echo json_encode(['observation'=>'after_fourth_source_response','turn'=>GetTurnNumber(),'priority'=>GetPriorityPlayer(),'window'=>$s['window']??null,'queue_type'=>$q[0]->Type??null,'capsules'=>$s['eleEmergingDeclarations']??null,'source_zone'=>$source['zone']??null]).PHP_EOL;
 EC(intval(GetTurnNumber())===2&&intval(GetTurnPlayer())===1&&GetCurrentPhase()==='MAIN','No automatic turn or phase advancement before declaration');
 EC(($s['window']??'')==='ACTION'&&intval(GetPriorityPlayer())===1,'No automatic priority pass may outrun mandatory target declaration');
 EC(intval(GetActionPoints(1))===1&&intval(GetResources(1))===1,'Printed go again and actual assets completed first');
 EC($source&&$source['zone']==='Arena'&&(string)$source['object']->CardID==='emerging_avalanche_red','Physical source has completed resolution');
 EC(FaBStackTop()===null&&count(GetHand(1))===0,'No premature trigger layer and no remaining Hand action');
 EC(($q[0]->Type??'')==='MZCHOOSE'&&($q[0]->Param??'')==='p2Hero-0&p4Hero-0','Controller receives legal adjacent target choices before priority');
 EC(($s['eleEmergingDeclarations']['5:1']['stage']??'')==='DECLARING','Native declaration capsule advanced');
 EC(!GetDecisionQueue(2)&&!GetDecisionQueue(3)&&!GetDecisionQueue(4),'Choice is on captured controller only');
 echo json_encode(['status'=>'PASS','checks'=>$checks,'normal_entry_point'=>'EngineRunAction','shortcut_enabled'=>true,'remaining_hand_actions'=>0,'turn'=>GetTurnNumber(),'priority'=>GetPriorityPlayer(),'window'=>$s['window'],'choice'=>$q[0]->Type,'source_zone'=>$source['zone'],'no_saved_fixture'=>true]).PHP_EOL;
}finally{
 $real=realpath($gameDir);$base=realpath($repo.'/FaBSim/Games');
 if($real!==false&&$base!==false&&str_starts_with(str_replace('\\','/',$real),str_replace('\\','/',$base).'/regression_emerging_shortcut_'))RegressionDeleteDirRecursive($real);
}
