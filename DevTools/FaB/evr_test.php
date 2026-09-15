<?php
require_once __DIR__.'/arc_test.php';
$failures=[];
$snapshot=json_decode(file_get_contents(__DIR__.'/evr_abilities.json'),true);
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$catalog=json_decode(file_get_contents(__DIR__.'/evr_catalog.json'),true);
$check(count($snapshot)===198&&array_column($catalog,'id')===array_column($snapshot,'cardId'),'EVR snapshot does not cover the set.');
$macroChecks=0;
foreach([2,4] as $seatCount)foreach($snapshot as $entry)foreach($entry['abilities'] as $ability){
    $arcReset($seatCount); foreach(FaBSeatOrder() as $seat){$soul=&GetSoul($seat);$soul=[];}$actor=$seatCount;SetTurnPlayer($actor);SetPriorityPlayer($actor);AddResources($actor,30);
    foreach(['autumns_touch_blue','winters_grasp_blue','heavens_claws_blue','passing_mirage_blue','rally_the_rearguard_red','bingo_red','crazy_brew_blue'] as $id){AddHand($actor,CardID:$id);AddDeck($actor,CardID:$id);AddGraveyard($actor,CardID:$id);AddSoul($actor,CardID:$id);AddBanish($actor,CardID:$id);}
    AddArsenal($actor,CardID:'ridge_rider_shot_red');
    $id=$entry['cardId'];$macro=$ability['macroName'];if(str_ends_with($macro,'Modifier'))continue;
    if(in_array($macro,['Hit','AttackDeclared'],true))$o=AddCombatChain($actor,CardID:$id,Owner:$actor,Controller:$actor,Role:'ATTACK',ChainLink:1);
    elseif($macro==='PrepareCard')$o=AddStack(CardID:$id,Controller:$actor,Kind:FaBHasType($id,'Attack')?'ATTACK':'ACTION',SourceZone:'Hand');
    elseif($macro==='CardPitched')$o=AddPitch($actor,CardID:$id);
    else $o=AddArena($actor,CardID:$id,Owner:$actor,Controller:$actor);
    $uid=intval($o->UniqueID);$s=FaBGetState();$s['attacker']=$actor;$s['defender']=1;$s['chainLink']=1;$s['attackUID']=$uid;$s['attackTarget']=['type'=>'HERO','player'=>1,'uid'=>GetHero(1)[0]->UniqueID,'zone'=>'Hero'];
    if($macro==='PrepareCard')$s['pendingPayment']=['player'=>$actor,'uid'=>$uid,'cost'=>0,'kind'=>'ACTION','fromZone'=>'Hand','returnWindow'=>'ACTION','returnCombatStep'=>'NONE'];
    $s['combatOpen']=true; FaBSetState($s);foreach(['Earth','Ice','Lightning'] as $e)FaBARCSetCard($uid,'eleFuse'.$e,true);FaBARCSetCard($uid,'eleFused',true);FaBARCSetCard($uid,'target',1);FaBARCSetCard($uid,'target2',1);
    DecisionQueueController::StoreVariable('fabSourceZone','Arsenal');
    FaBRunSourceMacro($macro,$actor,$id,['mzID'=>FaBFindUID($uid)['mzID'],'eleTarget'=>1,'eleRepeats'=>1,'amount'=>2,'attacker'=>$actor,'defender'=>1]);
    for($step=0;$step<50;++$step){
        $pending=0;foreach([1,2,3,4] as $seat)if(GetDecisionQueue($seat)){$pending=$seat;break;}
        if(!$pending)break;
        $d=GetDecisionQueue($pending)[0];
        if($d->Type==='CUSTOM'){(new DecisionQueueController())->ExecuteStaticMethods($pending);continue;}
        $value=match($d->Type){
            'MZCHOOSE','MZMAYCHOOSE'=>explode('&',$d->Param)[0],
            'MZMODAL'=>implode(',',range(0,max(0,intval(explode('|',$d->Param)[0])-1))),
            'MZREARRANGE'=>$d->Param,
            'MZMULTICHOOSE'=>implode('&',array_slice(explode('&',explode('|',$d->Param,3)[2]??''),0,intval(explode('|',$d->Param)[0]))),
            'NUMBERCHOOSE'=>strval(max(1,intval(explode('|',$d->Param)[0]))),
            'NAMECARD'=>'Zap',
            default=>throw new RuntimeException('Unhandled '.$d->Type.' for '.$id.' '.$macro)
        };
        $answer($pending,$value);
    }
    $check(!FaBHasPendingDecision(),'Continuation failed to finish: '.$id.' '.$macro);
    ++$macroChecks;
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "EVR continuations passed; $macroChecks authored continuations exercised in duels and UPF.\n";



