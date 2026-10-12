<?php
/** Public board and own hand only. Fixed policy for all count experiments. */
function PokeFanContext(array $view): array {
    $aliases=['sv08.5-079'=>'sv09-120','sv08.5-080'=>'sv05-129','me02.5-192'=>'me01-119','me02.5-213'=>'me01-131','me02.5-183'=>'me01-114'];
    foreach ($view['players'] as &$player) foreach (['Hand','Active','Bench','Discard'] as $zone) foreach ($player[$zone] ?? [] as $i=>$c)
        if(isset($aliases[$c['id']]))$player[$zone][$i]['id']=$aliases[$c['id']];
    unset($player);
    return PokeLopunnyContext($view);
}
function PokeFanOpening(array $ctx): bool {
    return $ctx['own']['turns'] <= ($ctx['seat']===$ctx['view']['firstPlayer'] ? 2 : 1);
}
/** A visible Tera attacker is the toolbox cue; no opponent deck/hand inspection. */
function PokeFanWantMine(array $ctx): bool {
    return (bool)array_filter($ctx['enemy']['Active'],fn($c)=>in_array('Tera',EffectiveCardSubtypes($c['id']),true));
}
function PokeFanSearchScore(array $ctx,string $id): float {
    if(CardTrainerType($id)==='Stadium')return !$ctx['view']['stadium']?240+($id==='me02.5-197'&&PokeFanWantMine($ctx)?30:0):0;
    if($id==='sv06.5-057')return !$ctx['view']['stadium']||!$ctx['energy']?260:0;
    if($id==='sv07-118' && PokeFanOpening($ctx) && !PokeBotFieldCount($ctx,[$id]) && !isset($ctx['hand'][$id]))return 300;
    if(in_array($id,['me01-119','me02.5-192'],true))return !isset($ctx['hand']['sv06.5-057'])?180:60;
    return PokeLopunnySearchScore($ctx,$id);
}
function PokeFanActionScore(array $ctx,array $action): float {
    $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
    $target=isset($action['target'])?PokeBotCard($ctx,$action['target']):null;
    $active=$ctx['own']['Active'][0]??null;$opening=PokeFanOpening($ctx);
    if($action['type']==='setup-active')return match($card['id']){'sv07-118'=>300,'sv09-120'=>250,'sv08.5-004'=>200,default=>100};
    if($action['type']==='trainer'){
        $id=$card['id'];
        if($id==='me02.5-197'&&PokeFanWantMine($ctx)&&($ctx['view']['stadium']['id']??'')!==$id)return 270;
        if(CardTrainerType($id)==='Stadium')return !$ctx['view']['stadium']?260:($id==='me02-085'&&($ctx['view']['stadium']['id']??'')==='sv06-153'?125:-20);
        if($id==='sv06.5-057')return $opening&&(!$ctx['view']['stadium']||!$ctx['energy']&&!$ctx['own']['energyUsed'])?245:-20;
        if($id==='me01-119')return count($ctx['own']['Hand'])<6||$opening&&(!$ctx['view']['stadium']||!$ctx['energy']&&!$ctx['own']['energyUsed']||!PokeBotFieldCount($ctx,['sv07-118']))?85:-20;
        if($id==='sv10.5b-084')return !$ctx['own']['supporterUsed']&&!isset($ctx['hand']['sv06.5-057'])&&(!isset($ctx['hand']['me01-119'])||$opening&&(!$ctx['view']['stadium']||!$ctx['energy']))?200:-20;
        if($id==='me03-072')return !$ctx['energy']&&!$ctx['own']['energyUsed']?235:-20;
        if($id==='me01-130'){
            foreach($ctx['own']['Bench'] as $next)if($opening&&$next['id']==='sv07-118'&&$active['id']!=='sv07-118'&&$ctx['view']['stadium']&&count($next['energy']))return 160;
            foreach($ctx['own']['Bench'] as $next)if(PokeLopunnyAttackValue($ctx,$next,true)>PokeLopunnyAttackValue($ctx,$active)+20)return 145;
            return -20;
        }
        if(in_array($id,['me01-131','me03-081'],true)&&$opening&&!PokeBotFieldCount($ctx,['sv07-118'])&&!isset($ctx['hand']['sv07-118']))return 240;
    }
    if($opening){
        if($action['type']==='bench'&&$card['id']==='sv07-118')return !PokeBotFieldCount($ctx,['sv07-118'])?280:-20;
        if($action['type']==='attach'&&$target['id']==='sv07-118'&&!count($target['energy']))return 210;
        if($action['type']==='retreat'&&$target['id']==='sv07-118'&&$active['id']!=='sv07-118'&&$ctx['view']['stadium']&&count($target['energy']))return 190;
        if($action['type']==='attack'&&$card['id']==='sv07-118'&&$ctx['view']['stadium'])return 75;
        if($action['type']==='evolve'&&$target['ref']===$active['ref']&&$card['id']==='sv05-129'&&!PokeBotFieldCount($ctx,['sv07-118']))return -20;
    }
    return PokeLopunnyActionScore($ctx,$action);
}
function PokeFanChoose(array $view): ?array {
    $ctx=PokeFanContext($view);
    if($view['decision']){
        $d=$view['decision'];
        foreach($d['choices'] as &$c)$c['card']=match($c['card']){'sv08.5-079'=>'sv09-120','sv08.5-080'=>'sv05-129',default=>$c['card']};unset($c);
        if(str_contains($d['prompt'],'Colress')||str_contains($d['prompt'],'search for a Supporter')){
            usort($d['choices'],fn($a,$b)=>PokeFanSearchScore($ctx,$b['card'])<=>PokeFanSearchScore($ctx,$a['card']));
            $value=$d['choices'][0]['value']??'-';
        } else $value=PokeFanDecision($ctx,$d);
        return ['type'=>'decision','player'=>$view['viewer'],'value'=>$value];
    }
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeFanActionScore($ctx,$b)<=>PokeFanActionScore($ctx,$a));
    return $actions[0]??null;
}

function PokeFanDecision(array $ctx,array $decision): string {
    $prompt=$decision['prompt'];
    if ($decision['type']==='NUMBERCHOOSE') return (string)$decision['max'];
    if ($decision['type']==='YESNO') return str_contains($prompt,'Snow Sink')&&($ctx['view']['stadium']['id']??'')==='me02-085'?'NO':'YES';
    $score=function($choice) use($ctx,$prompt) {
        $id=$choice['card']; if ($id===null) return 0;
        $card=PokeBotCard($ctx,$choice['value']);
        if (str_contains($prompt,'discard')) return PokeLopunnyDiscardScore($ctx,$id);
        if (str_contains($prompt,'Wally')) return $card['damage'];
        if (str_contains($prompt,'Air Balloon')) return ($card['ref']===($ctx['own']['Active'][0]['ref']??'')?200:0)+($id==='me02-084'?100:($id==='sv09-120'||$id==='sv05-129'?80:10));
        if (str_contains($prompt,'Boss')) return PokeLopunnyAttackValue(PokeBotGustContext($ctx,$card),$ctx['own']['Active'][0],false,$card);
        if (str_contains($prompt,'new Active')||str_contains($prompt,'switch')||str_contains($prompt,'Switch')) return PokeLopunnyAttackValue($ctx,$card,true)+($id==='30th-066'?20:0);
        return PokeFanSearchScore($ctx,$id);
    };
    $choices=$decision['choices']; usort($choices,fn($a,$b)=>$score($b)<=>$score($a));
    if ($decision['type']==='MZMULTICHOOSE') {
        $selected=[];
        // Recompute search ranks after each virtual choice to find a balanced
        // pair of Buneary/Dunsparce rather than duplicate every search target.
        while ($choices&&count($selected)<$decision['max']) {
            usort($choices,fn($a,$b)=>$score($b)<=>$score($a));
            $choice=array_shift($choices);
            if (count($selected)>=$decision['min']&&$score($choice)<=0) break;
            $selected[]=$choice['value'];
            if (!str_contains($prompt,'discard')&&$choice['card']) {
                $ctx['hand'][$choice['card']]=($ctx['hand'][$choice['card']]??0)+1;
                $score=fn($c)=>$c['card']===null?0:PokeFanSearchScore($ctx,$c['card']);
            }
        }
        return implode('&',$selected)?:'-';
    }
    if ($decision['min']===0&&(!$choices||$score($choices[0])<=0)) return '-';
    return $choices[0]['value']??'-';
}
