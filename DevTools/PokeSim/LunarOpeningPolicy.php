<?php
/** Experimental own-turn-one sequencing, using only the bot observation. */
function lunarTrialPieces(array $ctx): array {
    return [PokeBotFieldCount($ctx,['me01-074'])+($ctx['hand']['me01-074']??0)>0,
        PokeBotFieldCount($ctx,['me01-075'])+($ctx['hand']['me01-075']??0)>0];
}
function lunarTrialSearch(array $ctx,string $id): float {
    [$luna,$sol]=lunarTrialPieces($ctx);
    if($ctx['lines']>0){
        if($id==='me01-074'&&!$luna)return 430;
        if($id==='me01-075'&&!$sol)return 430;
    }
    if($luna&&$sol&&$ctx['fighting']===0&&PokeCeruledgeIsFightingEnergy($id))return 440;
    return PokeCeruledgeFightingSearchScore($ctx,$id);
}
function lunarTrialDiscard(array $ctx,string $id): float {
    [$luna,$sol]=lunarTrialPieces($ctx);
    if(($luna||$sol)&&!($ctx['own']['lunarCycleUsed']??false)&&$ctx['fighting']<=1&&PokeCeruledgeIsFightingEnergy($id))return -180;
    return PokeCeruledgeDiscardScore($ctx,$id);
}
function lunarTrialSafeCost(array $ctx,string $source): bool {
    $remaining=$ctx['own']['Hand'];
    foreach($remaining as $i=>$c)if($c['ref']===$source){unset($remaining[$i]);--$ctx['hand'][$c['id']];break;}
    for($i=0;$i<2;++$i){
        usort($remaining,fn($a,$b)=>lunarTrialDiscard($ctx,$b['id'])<=>lunarTrialDiscard($ctx,$a['id']));
        $c=array_shift($remaining);if(!$c||lunarTrialDiscard($ctx,$c['id'])<=0)return false;
        --$ctx['hand'][$c['id']];if($c['id']==='mee-002')--$ctx['fire'];if(PokeCeruledgeIsFightingEnergy($c['id']))--$ctx['fighting'];
    }
    return true;
}
function lunarTrialScore(array $ctx,array $a,string $variant): float {
    $base=PokeCeruledgeFightingActionScore($ctx,$a);
    if($ctx['own']['lunarCycleUsed']??false)return $base;
    $id=isset($a['source'])?(PokeBotCard($ctx,$a['source'])['id']??''):'';
    if($a['type']==='ability'&&$id==='me01-074'&&$base>=0)return 500;
    if($a['type']==='bench'&&in_array($id,['me01-074','me01-075'],true)&&!PokeBotFieldCount($ctx,[$id]))return 450;
    if($a['type']==='trainer'&&$ctx['own']['deckCount']>0){
        if($id==='me01-116'&&($ctx['missingLuna']||$ctx['missingSol']||$ctx['lunarReady']&&$ctx['fighting']===0))return 420;
        if($variant!=='t1-order'){
            [$luna,$sol]=lunarTrialPieces($ctx);
            if($id==='me03-081'&&$ctx['lines']>0&&(!$luna||!$sol))return 410;
            if($id==='me01-131'){
                $safe=$variant==='t1-fuel'?lunarTrialSafeCost($ctx,$a['source']):PokeCeruledgeSafeDiscards($ctx,'me01-131');
                if(!$safe)return -20;
                if($ctx['lines']>0&&(!$luna||!$sol))return 400;
            }
        }
    }
    return $base;
}
function lunarTrialChoose(array $view,string $variant): ?array {
    $ctx=PokeCeruledgeFightingContext($view);
    if($ctx['own']['turns']!==1)return PokeBotChoose($view);
    if($view['decision']){
        $d=$view['decision'];$prompt=$d['prompt'];
        if($variant!=='t1-order'&&!($ctx['own']['lunarCycleUsed']??false)){
            $discard=$variant==='t1-fuel'&&str_contains($prompt,'Ultra Ball: discard');
            $search=str_contains($prompt,'Fighting Gong')||str_contains($prompt,'Ultra Ball: search')
                ||str_contains($prompt,'Search your deck')||str_contains($prompt,"Explorer's Guidance: keep");
            if($discard||$search){
                $choices=$d['choices'];$selected=[];$max=$d['type']==='MZMULTICHOOSE'?$d['max']:1;
                while($choices&&count($selected)<$max){
                    $rank=fn($c)=>$discard?lunarTrialDiscard($ctx,$c['card']):lunarTrialSearch($ctx,$c['card']);
                    usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));$c=array_shift($choices);
                    if(count($selected)>=$d['min']&&$rank($c)<0)break;
                    $selected[]=$c['value'];$delta=$discard?-1:1;
                    $ctx['hand'][$c['card']]=($ctx['hand'][$c['card']]??0)+$delta;
                    if($c['card']==='mee-002')$ctx['fire']+=$delta;if(PokeCeruledgeIsFightingEnergy($c['card']))$ctx['fighting']+=$delta;
                }
                return ['type'=>'decision','player'=>$view['viewer'],'value'=>implode('&',$selected)?:'-'];
            }
        }
        return PokeBotChoose($view);
    }
    $actions=$view['actions'];usort($actions,fn($a,$b)=>lunarTrialScore($ctx,$b,$variant)<=>lunarTrialScore($ctx,$a,$variant));
    return $actions[0]??null;
}
