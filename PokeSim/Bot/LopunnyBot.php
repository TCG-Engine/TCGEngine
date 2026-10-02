<?php
/** Brisbane policy: public board + this seat's hand and legal chooser candidates. */
function PokeLopunnyContext(array $view): array {
    $ctx=PokeBotContext($view); $ctx['energy']=0;
    foreach ($ctx['own']['Hand'] as $c) if (CardType($c['id'])==='Energy') ++$ctx['energy'];
    return $ctx;
}
function PokeLopunnyLines(array $ctx,string $line): int {
    return PokeBotFieldCount($ctx,$line==='bunny'?['me02-083','me02-084']:['sv09-120','sv05-129','sv09-121']);
}
function PokeLopunnyCanPay(array $card,array $cost): bool {
    return PokeHasAttackEnergy((object)['Energy'=>$card['energy']],$cost);
}
function PokeLopunnyDamage(array $ctx,array $attacker,string $attackID,int $index,array $target,bool $moved=false): int {
    $attack=CardAttacks($attackID)[$index]??[];
    $damage=(int)($attack['damage']??0);
    if ($attackID==='me02-084' && $index===0) $damage=($moved||($attacker['counters']['movedActiveTurn']??-1)===$ctx['view']['turn'])?230:60;
    if ($attackID==='sv09-121' && $index===0) $damage=60*count(array_filter(array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']),fn($c)=>preg_match('/ ex$/i',$c['name'])===1));
    if ($attackID==='sv09-056') $damage=20+20*(count($ctx['own']['Bench'])+count($ctx['enemy']['Bench']));
    if ($attackID==='sv07-118' && !$ctx['view']['stadium']) $damage=0;
    $types=explode(',',CardTypes($attacker['id'])??'');
    $weaknesses=CardWeaknesses($target['id'])??[];
    if (str_contains(CardTypes($target['id'])??'','Dragon')&&PokeBotFieldCount($ctx,['sv09-056'])) $weaknesses=[['type'=>'Psychic','value'=>'×2']];
    foreach ($weaknesses as $w) if (in_array($w['type'],$types,true)) $damage=str_contains($w['value'],'×')||str_contains($w['value'],'x')?$damage*(int)preg_replace('/\D/','',$w['value']):$damage+(int)$w['value'];
    foreach (CardResistances($target['id'])??[] as $r) if (in_array($r['type'],$types,true)) $damage+=(int)$r['value'];
    return max(0,$damage);
}
function PokeLopunnyAttackValue(array $ctx,array $card,bool $moved=false,?array $target=null,bool $requireEnergy=true): float {
    $target??=$ctx['enemy']['Active'][0]??null; if (!$target) return 0;
    $best=0; $sources=[$card];
    if ($card['id']==='30th-066') $sources=array_merge($sources,$ctx['own']['Bench']);
    foreach ($sources as $source) foreach (CardAttacks($source['id'])??[] as $index=>$attack) {
        if ($requireEnergy&&!PokeLopunnyCanPay($card,$attack['cost']??[])) continue;
        $damage=PokeLopunnyDamage($ctx,$card,$source['id'],$index,$target,$moved);
        $value=min($damage,max(0,$target['hp']-$target['damage']));
        if ($damage>0&&$damage>=$target['hp']-$target['damage']) $value+=150*PokePrizeValue($target['id']);
        $best=max($best,$value);
    }
    return $best;
}
function PokeLopunnyNeedsEvolution(array $ctx): bool {
    foreach ($ctx['field'] as $c) if ($c['id']==='me02-083'&&!isset($ctx['hand']['me02-084'])||$c['id']==='sv09-120'&&!isset($ctx['hand']['sv05-129'])) return true;
    return false;
}
function PokeLopunnyTrainerScore(array $ctx,string $id): float {
    $active=$ctx['own']['Active'][0]??null;
    $needEnergy=$ctx['energy']===0&&!$ctx['own']['energyUsed'];
    $needEvolution=PokeLopunnyNeedsEvolution($ctx);
    $draw=count($ctx['own']['Hand'])<5||$needEnergy||$needEvolution;
    switch ($id) {
        case 'me01-132':
            if ($ctx['own']['energyUsed']) return -20;
            foreach ($ctx['field'] as $c) if ($c['id']==='me02-084'&&$c['damage']>=100) return 170+$c['damage']/10;
            return -20;
        case 'sv10.5w-084': return $needEvolution||$needEnergy?120:-20;
        case 'me01-119': return $draw?65:-20;
        case 'sv05-144': return count($ctx['own']['Bench'])<5&&(PokeLopunnyLines($ctx,'bunny')<2||PokeLopunnyLines($ctx,'draw')<2)?135:-20;
        case 'me03-081': return $needEvolution||PokeLopunnyLines($ctx,'bunny')<2||PokeLopunnyLines($ctx,'draw')<2?100:-20;
        case 'me01-131': return $needEvolution||PokeLopunnyLines($ctx,'bunny')<1?90:-20;
        case 'sv01-186': return !$ctx['own']['supporterUsed']&&$draw&&!isset($ctx['hand']['sv10.5w-084'])&&!isset($ctx['hand']['me01-119'])?80:-20;
        case 'me02.5-196':
            foreach ($ctx['own']['Discard'] as $c) if ((CardType($c['id'])==='Pokemon'||CardType($c['id'])==='Energy'&&CardEnergyType($c['id'])==='Normal') && PokeLopunnySearchScore($ctx,$c['id'])>=80) return 95;
            return -20;
        case 'me02.5-181':
            foreach ($ctx['field'] as $c) if (($c['tool']??'-')==='-'&&($c['ref']===($active['ref']??'')||in_array($c['id'],['me02-083','me02-084','sv09-120','sv05-129'],true))) return 145;
            return -20;
        case 'me02-085': return !$ctx['view']['stadium']?110:-20;
        case 'me04-082': return $ctx['enemy']['handCount']>3?105:-20;
        case 'me01-114':
            if (!$active||($ctx['seat']===$ctx['view']['firstPlayer']&&$ctx['own']['turns']===1)) return -20;
            $current=PokeLopunnyAttackValue($ctx,$active);
            foreach ($ctx['enemy']['Bench'] as $target) if (PokeLopunnyAttackValue($ctx,$active,false,$target)>$current+80) return 85;
            return -20;
    }
    return -20;
}
function PokeLopunnySearchScore(array $ctx,string $id): float {
    $hand=$ctx['hand'][$id]??0;
    if (CardType($id)==='Energy') return $ctx['energy']===0?($id==='sv08-191'?210:160):($ctx['energy']<2?65:-5);
    switch ($id) {
        case 'me02-083': return PokeLopunnyLines($ctx,'bunny')+($ctx['hand'][$id]??0)<2?175:0;
        case 'me02-084': return PokeBotFieldCount($ctx,['me02-083'])>$hand?190:($hand===0&&PokeLopunnyLines($ctx,'bunny')<2?55:0);
        case 'sv09-120': return PokeLopunnyLines($ctx,'draw')+$hand<2?145:0;
        case 'sv05-129': return PokeBotFieldCount($ctx,['sv09-120'])>$hand?165:0;
        case 'sv09-121':
            $ex=count(array_filter(array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']),fn($c)=>preg_match('/ ex$/i',$c['name'])===1));
            return $ex>=3&&PokeBotFieldCount($ctx,['sv09-120'])&&!$hand&&!PokeBotFieldCount($ctx,[$id])?195:0;
        case 'me03-062': return !$ctx['own']['supporterUsed']&&!$hand&&!PokeBotFieldCount($ctx,[$id])&&!isset($ctx['hand']['sv10.5w-084'])&&!isset($ctx['hand']['me01-119'])?75:0;
        case 'sv07-118': return $ctx['own']['turns']<=1&&!PokeBotFieldCount($ctx,[$id])&&!$hand?180:0;
        case '30th-066': return !PokeBotFieldCount($ctx,[$id])&&!$hand&&PokeLopunnyLines($ctx,'bunny')>0?40:0;
        case 'sv09-056': return str_contains(CardTypes($ctx['enemy']['Active'][0]['id']??'')??'','Dragon')&&!$hand&&!PokeBotFieldCount($ctx,[$id])?100:0;
        case 'sv10-010': case 'me02.5-039': case 'sv08-056': return 0;
    }
    if ($hand) return -5;
    return PokeLopunnyTrainerScore($ctx,$id);
}
function PokeLopunnyActionScore(array $ctx,array $action): float {
    $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
    $target=isset($action['target'])?PokeBotCard($ctx,$action['target']):null;
    $active=$ctx['own']['Active'][0]??null;
    switch ($action['type']) {
        case 'setup-active': return match($card['id']) {'30th-066'=>200,'sv07-118'=>190,'sv09-120'=>160,'me02-083'=>150,default=>20};
        case 'ready': case 'end': return 0;
        case 'bench':
            $id=$card['id'];
            if ($id==='me02-083') return PokeLopunnyLines($ctx,'bunny')<2?150:-20;
            if ($id==='sv09-120') return PokeLopunnyLines($ctx,'draw')<2?140:-20;
            // Leave Meowth in hand during setup so Last-Ditch Catch can trigger.
            if ($ctx['view']['phase']==='SETUP') return -20;
            if ($id==='sv07-118') return $ctx['own']['turns']===1&&!PokeBotFieldCount($ctx,[$id])?200:-20;
            if ($id==='me03-062') return !$ctx['own']['supporterUsed']&&!PokeBotFieldCount($ctx,[$id])&&!isset($ctx['hand']['sv10.5w-084'])&&!isset($ctx['hand']['me01-119'])&&count($ctx['own']['Bench'])<4?95:-20;
            if ($id==='30th-066') return !PokeBotFieldCount($ctx,[$id])&&count($ctx['own']['Bench'])<4?30:-20;
            if ($id==='sv09-056') return str_contains(CardTypes($ctx['enemy']['Active'][0]['id']??'')??'','Dragon')&&!PokeBotFieldCount($ctx,[$id])?45:-20;
            return -20;
        case 'evolve':
            if ($card['id']==='me02-084') return 185;
            if ($card['id']==='sv05-129') return 155;
            $ex=count(array_filter(array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']),fn($c)=>preg_match('/ ex$/i',$c['name'])===1));
            return $ex>=3&&!PokeBotFieldCount($ctx,['sv09-121'])?160:-20;
        case 'ability':
            if ($card['id']==='sv07-118') return 220;
            if ($card['id']==='sv05-129') return $ctx['own']['deckCount']>4&&count($ctx['own']['Hand'])<11&&count($ctx['field'])>1?75:-20;
            return -20;
        case 'trainer': return PokeLopunnyTrainerScore($ctx,$card['id']);
        case 'attach':
            $future=$target;
            $future['energy'][]=$card['id'];
            $willMove=$target['ref']!==($active['ref']??'');
            $before=PokeLopunnyAttackValue($ctx,$target,$willMove);
            $after=PokeLopunnyAttackValue($ctx,$future,$willMove);
            $value=($after-$before)/10;
            if ($after>$before) $value+=40;
            if (in_array($target['id'],['me02-083','me02-084'],true)&&count($target['energy'])<1) $value+=95;
            if ($target['id']==='sv09-121'&&$after>$before) $value+=65;
            if ($target['id']==='sv07-118'&&$ctx['own']['turns']===1&&$target['ref']===$active['ref']&&$ctx['view']['stadium']) $value+=45;
            if ($target['id']==='30th-066'&&$after>$before) $value+=45;
            if ($card['id']==='sv08-191') $value+=55;
            if ($target['ref']===$active['ref']) $value+=5;
            return $value>20?$value:-20;
        case 'retreat':
            $next=$target;
            $gain=PokeLopunnyAttackValue($ctx,$next,true)-PokeLopunnyAttackValue($ctx,$active);
            if ($gain>20) return 50+$gain/15-count($action['payment'])*5;
            // Pivot out of a Dudunsparce before recycling it with Run Away Draw.
            if ($active['id']==='sv05-129'&&$target['id']==='me02-084'&&count($target['energy'])) return 80;
            return -20;
        case 'attack':
            $id=isset($action['copySource'])?PokeBotCard($ctx,$action['copySource'])['id']:$card['id'];
            $enemy=$ctx['enemy']['Active'][0]??null;
            $damage=$enemy?PokeLopunnyDamage($ctx,$card,$id,$action['index'],$enemy):0;
            $value=$enemy?min($damage,$enemy['hp']-$enemy['damage'])/10:0;
            if ($enemy&&$damage>0&&$damage>=$enemy['hp']-$enemy['damage']) $value+=20*PokePrizeValue($enemy['id']);
            return $damage>0?10+$value:-5;
    }
    return -20;
}
function PokeLopunnyDiscardScore(array $ctx,string $id): float {
    if (in_array($id,['me02.5-039','sv08-056','sv10-010'],true)) return 150;
    if ($id==='me02-085'&&$ctx['view']['stadium']) return 140;
    if (($ctx['hand'][$id]??0)>1&&CardTrainerType($id)==='Supporter') return 120;
    if (CardType($id)==='Energy') return $ctx['energy']>2?100:-80;
    if (in_array($id,['me02-084','sv05-129'],true)) return PokeLopunnySearchScore($ctx,$id)>80?-100:60;
    return 50-PokeLopunnySearchScore($ctx,$id)/3;
}
function PokeLopunnyDecision(array $ctx,array $decision): string {
    $prompt=$decision['prompt'];
    if ($decision['type']==='NUMBERCHOOSE') return (string)$decision['max'];
    if ($decision['type']==='YESNO') return str_contains($prompt,'Snow Sink')&&($ctx['view']['stadium']['id']??'')==='me02-085'?'NO':'YES';
    $score=function($choice) use($ctx,$prompt) {
        $id=$choice['card']; if ($id===null) return 0;
        $card=PokeBotCard($ctx,$choice['value']);
        if (str_contains($prompt,'discard')) return PokeLopunnyDiscardScore($ctx,$id);
        if (str_contains($prompt,'Wally')) return $card['damage'];
        if (str_contains($prompt,'Air Balloon')) return ($card['ref']===($ctx['own']['Active'][0]['ref']??'')?200:0)+($id==='me02-084'?100:($id==='sv09-120'||$id==='sv05-129'?80:10));
        if (str_contains($prompt,'Boss')) return PokeLopunnyAttackValue($ctx,$ctx['own']['Active'][0],false,$card);
        if (str_contains($prompt,'new Active')||str_contains($prompt,'switch')||str_contains($prompt,'Switch')) return PokeLopunnyAttackValue($ctx,$card,true)+($id==='30th-066'?20:0);
        return PokeLopunnySearchScore($ctx,$id);
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
                $score=fn($c)=>$c['card']===null?0:PokeLopunnySearchScore($ctx,$c['card']);
            }
        }
        return implode('&',$selected)?:'-';
    }
    if ($decision['min']===0&&(!$choices||$score($choices[0])<=0)) return '-';
    return $choices[0]['value']??'-';
}
function PokeLopunnyChoose(array $view): ?array {
    $ctx=PokeLopunnyContext($view);
    if ($view['decision']) return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeLopunnyDecision($ctx,$view['decision'])];
    $actions=$view['actions']; usort($actions,fn($a,$b)=>PokeLopunnyActionScore($ctx,$b)<=>PokeLopunnyActionScore($ctx,$a));
    return $actions[0]??null;
}
