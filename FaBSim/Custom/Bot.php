<?php
// Legal-action heuristics use only this bot's hand and public board information.
function GameBotControllerMode(){return GetBotControllerPlayers()?'bot':'';}
function GetBotControllerPlayers(){return array_values(array_filter(array_map('intval',array_keys(FaBGetState()['botProfiles']??[])),fn($p)=>FaBSeatIsLive($p)));}
function BotControllerPendingPlayerForClient(){
    if(intval(GetWinner()))return 0;
    $bots=GetBotControllerPlayers();
    foreach(FaBLiveSeats() as $p)if(GetDecisionQueue($p))return in_array($p,$bots,true)?$p:0;
    $p=intval(GetPriorityPlayer());return in_array($p,$bots,true)?$p:0;
}
function FaBBotKeepValue(object $o,int $p): float {
    $v=floatval(CardPower($o->CardID))-floatval(CardCost($o->CardID));
    if(FaBPrintedKeywordIsActive($o->CardID,'Go again'))$v+=2;
    if($o->CardID==='art_of_war_yellow')$v+=4;
    if($o->CardID==='ancestral_empowerment_red')$v+=3;
    if(intval(CardPitch($o->CardID))===3)$v+=1;
    return $v;
}
function FaBBotChoice(int $p,object $d): ?string {
    if($d->Type==='MZREARRANGE')return $d->Param;
    if($d->Type==='MZMODAL'){
        $parts=explode('|',$d->Param,3);$n=intval($parts[0]);
        // Art of War: pump the whole chain and exchange an attack for two cards.
        if(str_contains($d->Tooltip,'Art_of_War'))return '0,3';
        return implode(',',range(0,max(0,$n-1)));
    }
    if(in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true)){
        $refs=[];
        foreach(explode('&',$d->Param) as $ref){$f=FaBIdentityFromMZ($ref);if($f!==null)$refs[]=$ref;}
        if(!$refs)return $d->Type==='MZMAYCHOOSE'?'PASS':null;
        usort($refs,function($a,$b)use($p){
            $fa=FaBIdentityFromMZ($a);$fb=FaBIdentityFromMZ($b);
            $score=function($f)use($p){if($f['zone']==='Hero')return $f['player']===$p?-1000:100-intval(GetHealth($f['player']));return -FaBBotKeepValue($f['object'],$p);};
            return $score($fb)<=>$score($fa);
        });return $refs[0];
    }
    return null;
}
function FaBBotAct(int $p): bool {
    $dq=new DecisionQueueController();$d=$dq->NextDecision($p);
    if($d){
        if(in_array($d->Type,['CUSTOM','SYSTEM','PASSPARAMETER','MZMOVE'],true)){$dq->ExecuteStaticMethods($p);return true;}
        $answer=FaBBotChoice($p,$d);if($answer===null)return false;
        if(function_exists('GameValidateDecisionAnswer')&&!GameValidateDecisionAnswer($p,$answer))return false;
        $dq->PopDecision($p);$dq->ExecuteStaticMethods($p,$answer);return true;
    }
    $s=FaBGetState();$candidates=[];
    foreach(['Hand','Arsenal','Banish','Weapons','Equipment','Hero'] as $z)foreach(FaBChoiceRefs($p,$z) as $ref){
        $o=FaBIdentityFromMZ($ref)['object'];$keep=FaBBotKeepValue($o,$p);
        if(CanPitchCard($p,$ref))$candidates[]=[100+intval(CardPitch($o->CardID))*5-$keep,'PITCH',$ref];
        if(FaBCanBlock($p,$ref)){
            $remaining=FaBAttackPower($s)-FaBDefenseValue($s);
            $defense=FaBCurrentDefense($o,$p);
            // Preserve attack fuel unless damage is significant or threatens lethal.
            $lethal=$remaining>=intval(GetHealth($p));
            $preserveEquipment=in_array($o->CardID,['fyendals_spring_tunic','mask_of_momentum'],true)&&!$lethal;
            if(!$preserveEquipment&&$remaining>0&&$defense>0&&(intval(GetHealth($p))<9||$remaining>=4||$z==='Equipment'))$candidates[]=[min($remaining,$defense)*3-$keep-($z==='Equipment'?2:0),'BLOCK',$ref];
        }
        if(CanPlayCard($p,$ref)){
            $v=$keep+2;
            if(FaBWTRIsAttackAction($o)){
                $go=FaBAttackHasGoAgain(array_replace($s,['attacker'=>$p]),$o);
                if($o->CardID==='blaze_headlong_red')$go=count(array_filter($s['cardsPlayedThisTurn'][(string)$p]??[],fn($id)=>intval(CardPitch($id))===1))>0;
                $v+=$go?6:0;
                if($o->CardID==='phoenix_flame_red')$v+=FaBFaiChainCount($p)>=2?3:-5;
                if(in_array($o->CardID,['breaking_point_red','red_hot_red'],true))$v+=intval($s['chainLink'])>=3?5:-3;
                if($o->CardID==='salt_the_wound_yellow')$v+=intval($s['chainHits']??0);
                if(!$go&&FaBHandCount($p)>2)$v-=3;
            }elseif($o->CardID==='art_of_war_yellow')$v=($p===intval(GetTurnPlayer())&&FaBHandCount($p)>=3&&$s['window']==='ACTION')?20:-100;
            elseif($o->CardID==='rise_from_the_ashes_red')$v=FaBHandCount($p)>1?14:-100;
            $candidates[]=[$v,'PLAY',$ref];
        }
        if(FaBWTRCanActivate($p,$ref)){
            $v=-100;
            if($z==='Weapons')$v=FaBAttackHasGoAgain(array_replace($s,['attacker'=>$p]),$o)?12:1;
            if($o->CardID==='fai'&&$p===intval(GetTurnPlayer())&&FaBFaiFlames($p)!==''&&FaBFaiChainCount($p)>=3)$v=18;
            if($o->CardID==='fyendals_spring_tunic'&&$p===intval(GetTurnPlayer())&&intval(GetResources($p))<1)$v=17;
            if($o->CardID==='stubby_hammerers'&&FaBHandCount($p)>=3)$v=18;
            if($o->CardID==='snapdragon_scalers'&&FaBHandCount($p)>0){$attack=FaBFindUID(intval($s['attackUID']));if($attack&&!FaBAttackHasGoAgain($s,$attack['object']))$v=15;}
            $candidates[]=[$v,'ACTIVATE',$ref];
        }
        if(FaBCanArsenal($p,$ref))$candidates[]=[$keep+10,'ARSENAL',$ref];
    }
    usort($candidates,fn($a,$b)=>$b[0]<=>$a[0]);
    foreach($candidates as [$score,$verb,$ref]){
        if($score<0)continue;
        if($verb==='PLAY'){PlayCard($p,$ref);return true;}
        $ok=match($verb){'PITCH'=>DoPitchCard($p,$ref),'BLOCK'=>FaBDeclareBlock($p,$ref),'ACTIVATE'=>FaBWTRActivate($p,$ref),'ARSENAL'=>FaBArsenalCard($p,$ref)};
        if($ok)return true;
    }
    return FaBPassPriority($p);
}
function ProcessBotControllerStep($requestingPlayer=0,$folderPath='',$gameNameOverride=''){
    if($folderPath!==''&&$folderPath!=='FaBSim')return ['success'=>false,'applied'=>false,'retryable'=>false];
    $p=BotControllerPendingPlayerForClient();if(!$p)return ['success'=>true,'applied'=>false,'retryable'=>false];
    global $playerID;$previous=$playerID;$playerID=$p;
    try{$applied=FaBBotAct($p);if($applied)GameAfterEngineAction([],[]);}finally{$playerID=$previous;}
    return ['success'=>$applied,'applied'=>$applied,'writeGamestate'=>$applied,'updateCache'=>$applied,'retryable'=>false,'message'=>$applied?'':'No supported legal bot action.'];
}
