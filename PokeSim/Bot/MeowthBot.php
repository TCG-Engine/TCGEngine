<?php
/** Uses only the seat observation and legal search candidates. */
function PokeMeowthDamage(array $ctx, array $card, int $index, ?array $target=null): int {
    $target ??= $ctx['enemy']['Active'][0] ?? null;
    if (!$target) return 0;
    $damage=(int)(CardAttacks($card['id'])[$index]['damage'] ?? 0);
    if ($card['id']==='30th-101' && $index===1) $damage=10*count($ctx['own']['Hand']);
    if ($card['id']==='me01-094') $damage=$index===0?80*(6-$ctx['own']['prizeCount']):($target['damage']>0?30:260);
    if ($card['id']==='sv07-106' && $index===1) $damage=80+$card['damage'];
    $types=explode(',',CardTypes($card['id'])??'');
    $noWeakness=($target['turnEffects']['noWeaknessThrough']??-1)>=$ctx['view']['turn'];
    foreach ($noWeakness?[]:(CardWeaknesses($target['id'])??[]) as $w) if (in_array($w['type'],$types,true))
        $damage=str_contains($w['value'],'×')||str_contains($w['value'],'x')?$damage*(int)preg_replace('/\D/','',$w['value']):$damage+(int)$w['value'];
    foreach (CardResistances($target['id'])??[] as $r) if (in_array($r['type'],$types,true)) $damage+=(int)$r['value'];
    return max(0,$damage);
}
function PokeMeowthAttackValue(array $ctx,array $card,?array $target=null): float {
    $target ??= $ctx['enemy']['Active'][0]??null; $best=0;
    foreach (CardAttacks($card['id'])??[] as $index=>$attack) {
        if (!PokeBotCanAttack($ctx,$card) || !PokeLopunnyCanPay($card,$attack['cost']??[])) continue;
        $damage=PokeMeowthDamage($ctx,$card,$index,$target);
        $value=$damage;
        if ($target && $damage>0 && $damage >= $target['hp']-$target['damage']) $value+=1000*PokePrizeValue($target['id']);
        $best=max($best,$value);
    }
    return $best;
}
function PokeMeowthEnergyPriority(array $ctx,array $card): float {
    $n=count($card['energy']);
    if ($card['id']==='30th-101') return $n===0?300:0;
    if ($card['id']==='me01-094') return $n<2?($ctx['own']['prizeCount']<=3?320:150):($n<3?60:0);
    if (in_array($card['id'],['sv07-106','sv08-130'],true)) return $n<3?250:0;
    return 0;
}
function PokeMeowthSearchScore(array $ctx,string $id): float {
    $hand=$ctx['hand']; $field=array_column($ctx['field'],'id');
    return match($id) {
        '30th-101'=>!in_array($id,$field,true)&&empty($hand[$id])?400:5,
        'mee-008'=>empty($hand[$id])?350:20,
        'me02.5-258'=>count($ctx['own']['Hand'])>=6?330:250,
        'me02.5-192'=>count($ctx['own']['Hand'])<6?340:200,
        'sv05-129'=>in_array('sv09-120',$field,true)?310:30,
        'sv08-130'=>in_array('sv07-106',$field,true)&&empty($hand[$id])?300:20,
        'sv07-106'=>!in_array($id,$field,true)&&!in_array('sv08-130',$field,true)?280:10,
        'sv09-120'=>PokeBotFieldCount($ctx,['sv09-120'])<2?270:10,
        'me01-094'=>!in_array($id,$field,true)&&$ctx['own']['prizeCount']<=4?290:30,
        'me03-072'=>empty($hand['mee-008'])?320:5,
        'me05-078'=>250,
        'me02.5-183'=>100,
        default=>50,
    };
}
function PokeMeowthDiscardScore(array $ctx,string $id): float {
    if ($id==='mee-008') return count(array_filter($ctx['own']['Discard'],fn($c)=>$c['id']===$id))<2&&PokeBotFieldCount($ctx,['sv07-106','sv08-130'])?400:($ctx['hand'][$id]>1?150:-100);
    return ($ctx['hand'][$id]??0)>1?200:100-PokeMeowthSearchScore($ctx,$id)/4;
}
function PokeMeowthActionScore(array $ctx,array $a): float {
    $c=isset($a['source'])?PokeBotCard($ctx,$a['source']):null;
    $t=isset($a['target'])?PokeBotCard($ctx,$a['target']):null;
    $active=$ctx['own']['Active'][0]??null;
    switch ($a['type']) {
        case 'setup-active':return match($c['id']) {'30th-101'=>500,'sv09-120'=>300,'sv08.5-004'=>100,default=>50};
        case 'ready':return 0;
        case 'bench':
            if ($ctx['view']['phase']==='MAIN' && $ctx['own']['turns']===1 && $active && $active['id']==='30th-101' && count($ctx['own']['Hand'])<=7) return -5;
            return match($c['id']) {
                '30th-101'=>PokeBotFieldCount($ctx,[$c['id']])===0?420:-10,
                'sv09-120'=>PokeBotFieldCount($ctx,[$c['id']])<2?180:-10,
                'sv07-106'=>PokeBotFieldCount($ctx,['sv07-106','sv08-130'])<1?190:-10,
                'me01-094'=>PokeBotFieldCount($ctx,[$c['id']])===0&&$ctx['own']['prizeCount']<=4?180:-10,
                default=>-10};
        case 'evolve':return $c['id']==='sv05-129'?450:440;
        case 'ability':return $c['id']==='sv05-129'?460:0;
        case 'attach':return PokeMeowthEnergyPriority($ctx,$t);
        case 'retreat':return $t&&PokeMeowthAttackValue($ctx,$t)>PokeMeowthAttackValue($ctx,$active)+30?500:-10;
        case 'attack':
            $damage=PokeMeowthDamage($ctx,$c,$a['index']); $enemy=$ctx['enemy']['Active'][0];
            return 100+$damage+($damage>0&&$damage>=$enemy['hp']-$enemy['damage']?1000*PokePrizeValue($enemy['id']):0);
        case 'trainer':
            $hand=count($ctx['own']['Hand']);
            if ($c['id']==='me02.5-258') return $hand>=6?520:470;
            if ($c['id']==='me02.5-192') return $hand<=8?510:-10;
            if ($c['id']==='me05-078') return count(array_filter($ctx['own']['Hand'],fn($p)=>CardType($p['id'])==='Pokemon'&&!PokeHasRuleBox($p['id'])))>=2?530:-10;
            if ($c['id']==='me01-130') return $active&&array_filter($ctx['own']['Bench'],fn($p)=>PokeMeowthAttackValue($ctx,$p)>PokeMeowthAttackValue($ctx,$active)+30)?550:-10;
            if ($c['id']==='me02.5-183') return $active&&array_filter($ctx['enemy']['Bench'],fn($p)=>PokeMeowthAttackValue($ctx,$active,$p)>PokeMeowthAttackValue($ctx,$active)+500)?600:-10;
            if ($c['id']==='me03-072') return empty($ctx['hand']['mee-008'])?430:-10;
            if ($c['id']==='sv10.5b-084') return !$ctx['own']['supporterUsed']&&!isset($ctx['hand']['me02.5-258'])&&!isset($ctx['hand']['me02.5-192'])?480:-10;
            if ($c['id']==='me03-081') return PokeBotFieldCount($ctx,['30th-101'])===0||PokeBotFieldCount($ctx,['sv09-120'])<2||PokeBotFieldCount($ctx,['sv07-106','sv08-130'])===0?400:-10;
            if ($c['id']==='me02.5-213') {
                if ($ctx['own']['turns']===1 && $ctx['own']['supporterUsed'] && $active && $active['id']==='30th-101' && $active['energy'] && $hand<=9) return -10;
                return $hand>=5&&PokeBotFieldCount($ctx,['sv09-120','sv07-106'])>0?390:-10;
            }
            if ($c['id']==='me02.5-196') return 350;
            return -10;
        case 'end':return 0;
        default:return -5;
    }
}
function PokeMeowthChoose(array $view): ?array {
    $ctx=PokeBotContext($view);
    if ($d=$view['decision']) {
        if ($d['type']==='NUMBERCHOOSE') return ['type'=>'decision','player'=>$ctx['seat'],'value'=>(string)$d['max']];
        if ($d['type']==='YESNO') return ['type'=>'decision','player'=>$ctx['seat'],'value'=>'YES'];
        $prompt=str_replace('_',' ',$d['prompt']); $choices=$d['choices'];
        if (str_contains($prompt,'Prize')) return ['type'=>'decision','player'=>$ctx['seat'],'value'=>PokeBotDecision($ctx,$d)];
        $score=static function($choice) use($ctx,$prompt) {
            $id=$choice['card']; if (!$id) return 0;
            $card=PokeBotCard($ctx,$choice['value']);
            if (str_contains($prompt,'attach to')) return PokeMeowthEnergyPriority($ctx,$card);
            if (str_contains($prompt,'discard')) return PokeMeowthDiscardScore($ctx,$id);
            if (str_contains($prompt,'Boss')) return PokeMeowthAttackValue($ctx,$ctx['own']['Active'][0],$card);
            if (str_contains($prompt,'Switch:')||str_contains($prompt,'new Active')||str_contains($prompt,'Switch to')) return PokeMeowthAttackValue($ctx,$card)+($id==='30th-101'?20:0);
            return PokeMeowthSearchScore($ctx,$id);
        };
        usort($choices,fn($a,$b)=>$score($b)<=>$score($a));
        $value=$choices[0]['value']??'-';
        if ($d['type']==='MZMULTICHOOSE') $value=implode('&',array_column(array_slice($choices,0,$d['max']),'value'))?:'-';
        return ['type'=>'decision','player'=>$ctx['seat'],'value'=>$value];
    }
    $actions=$view['actions']; usort($actions,fn($a,$b)=>PokeMeowthActionScore($ctx,$b)<=>PokeMeowthActionScore($ctx,$a));
    return $actions[0]??null;
}
