<?php
/** Native API rejection contracts. These are probes, not saved rejected-action recordings. */
declare(strict_types=1);
$repo=getenv('FAB_REGRESSION_REPO')?:__DIR__;
while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
if(!is_file($repo.'/Core/EngineActionRunner.php'))throw new RuntimeException('Set FAB_REGRESSION_REPO');
chdir($repo);require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
$fixture=$repo.'/Tests/Integration/FaBSim/card-pummel-red-hand-cost-two-hit-defender-chooses-0';
$gameName='regression_fab_contract_'.uniqid();$gameDir=$repo.'/FaBSim/Games/'.$gameName;$results=[];
function GHRequire(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
function GHSemanticState():array{
    $zones=[];
    foreach([1,2]as$seat)foreach(['Hand','Graveyard']as$zone){
        $cards=[];foreach(GetZone('p'.$seat.$zone)as$obj)if(is_object($obj)&&empty($obj->removed))$cards[]=['id'=>$obj->CardID,'uid'=>intval($obj->UniqueID),'owner'=>intval($obj->Owner),'controller'=>intval($obj->Controller)];
        $zones['p'.$seat.$zone]=$cards;
    }
    $stack=[];foreach(GetStack()as$obj)if(is_object($obj)&&empty($obj->removed))$stack[]=get_object_vars($obj);
    $queue=[];foreach([1,2]as$seat)$queue[$seat]=array_map(fn($row)=>get_object_vars($row),GetDecisionQueue($seat));
    return ['zones'=>$zones,'stack'=>$stack,'queues'=>$queue,'resources'=>[GetResources(1),GetResources(2)],'ap'=>[GetActionPoints(1),GetActionPoints(2)],'health'=>[GetHealth(1),GetHealth(2)],'priority'=>GetPriorityPlayer(),'passes'=>GetConsecutivePasses(),'state'=>FaBGetState(),'variables'=>GetDecisionQueueVariables()];
}
function GHAction(array $action):array{return EngineRunAction($action,'FaBSim',$GLOBALS['gameName'],['updateCache'=>false,'disableRecording'=>true]);}
try{
    RegressionEnsureDir($gameDir);copy($fixture.'/initial_gamestate.txt',$gameDir.'/Gamestate.txt');ParseGamestate('./FaBSim/');
    // Valid test starting zones make foreign-seat and non-Hand refs live rather than absent.
    AddHand(1,CardID:'wounding_blow_red',Owner:1,Controller:1);
    AddGraveyard(2,CardID:'wounding_blow_blue',Owner:2,Controller:2);
    WriteGamestate('./FaBSim/');
    $prefix=0;
    foreach(json_decode(file_get_contents($fixture.'/actions.json'),true,512,JSON_THROW_ON_ERROR)as$action){
        if(FaBIsDiscardRequestDecision(2))break;
        $result=GHAction($action);GHRequire(!empty($result['success']),'Prefix action rejected');++$prefix;
    }
    // Compare two hydrated request states, not freshly allocated row metadata.
    ParseGamestate('./FaBSim/');
    GHRequire(FaBIsDiscardRequestDecision(2),'Expected exact native mandatory discard pair');
    GHRequire(FaBIdentityFromMZ('p1Hand-0')!==null,'Expected live foreign hand card');
    GHRequire(FaBIdentityFromMZ('p2Graveyard-0')!==null,'Expected live non-Hand card');
    $before=GHSemanticState();
    foreach(['PASS','-','p1Hand-0','p2Graveyard-0','p2Hand-99','p2Hand-0@payload','p2Hand-0&p2Hand-1']as$answer){
        $result=GHAction(['playerID'=>2,'mode'=>100,'cardID'=>$answer,'inputText'=>'']);
        GHRequire(empty($result['success']),'Invalid answer unexpectedly accepted: '.$answer);
        GHRequire(FaBIsDiscardRequestDecision(2),'Invalid answer popped mandatory queue: '.$answer);
        $after=GHSemanticState();
        if($after!==$before){foreach($before as$key=>$value)if($after[$key]!==$value)echo json_encode(['changed_component'=>$key,'before'=>$value,'after'=>$after[$key]],JSON_THROW_ON_ERROR)."\n";}
        GHRequire($after===$before,'Invalid answer changed semantic state: '.$answer);
        $results[]=['answer'=>$answer,'success'=>false,'semantic_state_unchanged'=>true];
    }
    $valid=GHAction(['playerID'=>2,'mode'=>100,'cardID'=>'p2Hand-1','inputText'=>'']);
    GHRequire(!empty($valid['success']),'Valid current Hand answer rejected');
    GHRequire(count(FaBChoiceRefs(2,'Hand'))===1,'Valid answer did not discard exactly one card');
    GHRequire(FaBIdentityFromMZ(FaBChoiceRefs(2,'Hand')[0])['object']->CardID==='wounding_blow_red','Wrong hand survivor');
    $gy=array_map(fn($ref)=>FaBIdentityFromMZ($ref)['object']->CardID,FaBChoiceRefs(2,'Graveyard'));
    GHRequire($gy===['wounding_blow_blue','wounding_blow_yellow'],'Wrong discard identity/destination');
    GHRequire(count(GetDecisionQueue(1))===0&&count(GetDecisionQueue(2))===0,'Queue did not drain');
    GHRequire(intval(GetPriorityPlayer())===1&&intval(GetConsecutivePasses())===0&&FaBGetState()['window']==='DAMAGE','Unexpected pre-pass boundary');
    $pass=GHAction(['playerID'=>1,'mode'=>10001,'cardID'=>'!CustomInput!Pass','inputText'=>'']);
    GHRequire(!empty($pass['success']),'Post-choice real pass rejected');
    GHRequire(intval(GetPriorityPlayer())===2&&intval(GetConsecutivePasses())===1&&FaBGetState()['window']==='DAMAGE','Post-choice pass did not change priority/passes');
    echo json_encode(['status'=>'PASS','evidence_kind'=>'native_api_contract_probe_not_recording_coverage','prefix_actions'=>$prefix,'rejected_answers'=>$results,'valid_answer'=>'p2Hand-1','subsequent_real_pass'=>true],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
}finally{
    GHRequire(dirname($gameDir)===$repo.'/FaBSim/Games'&&str_starts_with(basename($gameDir),'regression_fab_contract_'),'Unsafe cleanup target');
    RegressionClearGamestateMemory($gameName);RegressionDeleteDirRecursive($gameDir);
}
