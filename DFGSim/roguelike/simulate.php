<?php
declare(strict_types=1);
// Offline smoke/balance aid. It plays legal public-observation actions, never fixture wins.
require_once __DIR__.'/Run.php';
$results=[];
foreach([514,741,1203] as $seed){
    $r=RGApply(RGNewRun($seed),['type'=>'starter','item'=>'moth-cloak']);$rng=$seed+13;$steps=0;
    $power=fn($id)=>DFGCatalog()[$id]['attack']+0.6*DFGCatalog()[$id]['block']+(str_contains(DFGCatalog()[$id]['ability'],'ignore')?1:0);
    while(!in_array($r['phase'],['dead','victory'],true)&&$steps++<1200){
        if($r['phase']==='map'){
            $nodes=RGAccessible($r);$pick=$nodes[count($nodes)===3?1:0];
            foreach($nodes as $n)if($n['type']==='camp'){$pick=$n;break;}
            if(!in_array('camp',array_column($nodes,'type'),true))foreach($nodes as $n)if($n['type']==='shrine')$pick=$n;
            $r=RGApply($r,['type'=>'travel','node'=>$pick['id'],'color'=>'Red']);
        }elseif($r['phase']==='fight'){
            $move=DFGBotChoose(DFGObserve($r['battle'],0),0,$rng);$r=RGApply($r,['type'=>'battle','move'=>$move]);
            if(($r['battle']['round']??0)>150)break;
        }elseif($r['phase']==='cardReward'){
            $ids=$r['reward']['cards'];usort($ids,fn($a,$b)=>$power($b)<=>$power($a));$r=RGApply($r,['type'=>'cardReward','card'=>$ids[0]]);
        }elseif($r['phase']==='armorReward'){
            $ids=$r['reward']['armor'];usort($ids,function($a,$b)use($r){$items=RGItems();$score=fn($id)=>(empty($r['equipment'][$items[$id]['slot']])?10:0)+(['attack'=>5,'block'=>4,'victory_heal'=>3,'shield'=>2][$items[$id]['kind']]??0);return $score($b)<=>$score($a);});
            $r=RGApply($r,['type'=>'armorReward','item'=>$ids[0]]);
        }elseif(in_array($r['phase'],['camp','shrine'],true))$r=RGApply($r,['type'=>'rest']);
        elseif($r['phase']==='shop'){
            $ids=array_values(array_filter($r['shop']['cards'],fn($id)=>!in_array('cards:'.$id,$r['shop']['bought'],true)));
            if($r['gold']>=20&&$ids){usort($ids,fn($a,$b)=>$power($b)<=>$power($a));$r=RGApply($r,['type'=>'buy','kind'=>'cards','id'=>$ids[0]]);}else $r=RGApply($r,['type'=>'leave']);
        }
    }
    $results[]=['seed'=>$seed,'phase'=>$r['phase'],'wins'=>$r['wins'],'life'=>$r['battle']['players'][0]['life']??$r['life'],'actions'=>$steps,'lastRound'=>$r['battle']['round']??0];
}
echo json_encode($results,JSON_PRETTY_PRINT)."\n";
if(!array_filter($results,fn($r)=>$r['phase']==='victory'))exit(1);
