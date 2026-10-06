<?php
/** Draw-focused policy sharing the five-Antique replacement cycle with v1. */
function PokeRelicanthDrawNeedsDraw(array $ctx): bool {
    return PokeRelicanthNeedsAttacker($ctx)||PokeRelicanthNeedsEnergyAccess($ctx)
        ||PokeRelicanthDrawWantsBulwark($ctx)
        ||count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])))<PokeRelicanthFossilLimit($ctx);
}
function PokeRelicanthDrawWantsBulwark(array $ctx): bool {
    return PokeBotFieldCount($ctx,['me05-062'])<PokeRelicanthDrawBulwarkTargetCount($ctx)
        && (isset($ctx['hand']['me05-062']) || isset($ctx['hand']['me01-125']));
}
function PokeRelicanthDrawBulwarkTargetCount(array $ctx): int {
    $enemy=$ctx['enemy']['Active'][0]??null;
    // Do not spend a Bench slot on protection the current attacker already bypasses.
    if(!$enemy||count($enemy['energy'])>2||PokeIsFossil($enemy['id'])||in_array($enemy['id'],['me05-005','me05-006'],true))return 0;
    if(PokeBotFieldCount($ctx,['me05-062'])===1&&($ctx['own']['Active'][0]['tool']??'-')==='sv10.5w-080'&&CardSuffix($enemy['id'])==='ex'){
        $hp=max(1,$enemy['hp']-$enemy['damage']);
        $one=PokeRelicanthDamage($ctx,$enemy,4);$two=PokeRelicanthDamage($ctx,$enemy,3);
        if($two>0&&ceil($hp/$one)===ceil($hp/$two))return 2;
    }
    return 1;
}
function PokeRelicanthDrawWantsBangle(array $ctx): bool {
    return (bool)array_filter(array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']),fn($c)=>CardSuffix($c['id'])==='ex');
}
function PokeRelicanthDrawSearchScore(array $ctx,string $id): float {
    if($id==='me05-062')return PokeRelicanthDrawWantsBulwark($ctx)&&!isset($ctx['hand'][$id])&&isset($ctx['hand']['me01-125'])?215:0;
    if($id==='me05-072'&&PokeRelicanthDrawWantsBulwark($ctx)&&!PokeBotFieldCount($ctx,[$id]))return 195;
    return PokeRelicanthSearchScore($ctx,$id);
}
function PokeRelicanthDrawDiscardScore(array $ctx,string $id): float {
    if(in_array($id,['me05-062','me01-125'],true)){
        $needed=PokeRelicanthDrawBulwarkTargetCount($ctx)-PokeBotFieldCount($ctx,['me05-062']);
        return $needed<=0?10:(($ctx['hand'][$id]??0)>$needed?200:650);
    }
    if($id==='sv10.5w-080')return ($ctx['own']['Active'][0]['tool']??'-')==='-'?350:20;
    if($id==='me05-017')return 1000;
    if(CardType($id)==='Energy')return $id==='sv06-167'?1100:900;
    if(in_array($id,['me03-081','me02.5-196','me03-072','sv10.5w-082'],true))return 600;
    if($id==='me05-076')return ($ctx['view']['stadium']['id']??'')==='me05-076'?0:500;
    if(PokeIsFossil($id))return PokeRelicanthDrawNeedsDraw($ctx)&&count($ctx['own']['Bench'])<5?250:0;
    if($id==='sv06-158')return ($ctx['own']['Active'][0]['tool']??'-')==='-'?300:20;
    return 100;
}
function PokeRelicanthDrawActionScore(array $ctx,array $a): float {
    $card=isset($a['source'])?PokeBotCard($ctx,$a['source']):null;
    if($a['type']==='attach'&&($card['id']??'')==='sv06-167'){
        $score=PokeRelicanthActionScore($ctx,$a);
        return $score>0?$score+(!($ctx['own']['legacyEnergyUsed']??false)?5:0):$score;
    }
    if($a['type']==='trainer'){
        $id=$card['id'];$size=count($ctx['own']['Hand']);$needs=PokeRelicanthDrawNeedsDraw($ctx);
        if($id==='me01-125')return PokeRelicanthDrawWantsBulwark($ctx)&&count(array_filter($ctx['own']['Bench'],fn($c)=>$c['id']==='me05-072'))?205:-20;
        if($id==='me03-081'&&!PokeRelicanthNeedsAttacker($ctx)&&PokeRelicanthDrawSearchScore($ctx,'me05-062')>0)return 170;
        if($id==='me02.5-196'&&PokeRelicanthDrawWantsBulwark($ctx)&&isset($ctx['hand']['me01-125'])&&in_array('me05-062',array_column($ctx['own']['Discard'],'id'),true))return 170;
        if($id==='sv10.5w-080')return PokeRelicanthDrawWantsBangle($ctx)&&count(array_filter($ctx['field'],fn($c)=>$c['id']==='me05-017'&&$c['tool']==='-'))?185:-20;
        if($id==='sv06-158')return count(array_filter($ctx['field'],fn($c)=>$c['id']==='me05-017'&&$c['tool']==='-'))?(isset($ctx['hand']['sv10.5w-080'])&&PokeRelicanthDrawWantsBangle($ctx)?-20:175):-20;
        if(in_array($id,['me01-119','sv07-139','me03-076','me02.5-190'],true)){
            if(!$needs)return $id==='me03-076'&&!isset($ctx['hand']['me05-017'])&&$size<=4&&$ctx['enemy']['handCount']>=8?60:-20;
            if($id==='me02.5-190'){
                $draw=max(0,8-$size);if(!$draw)return -20;
                // Iris keeps a held backup and attachments; prefer it when it draws well.
                return 130+4*$draw+(isset($ctx['hand']['me05-017'])&&$draw>=2?12:0);
            }
            $draw=$id==='me01-119'?($ctx['own']['prizeCount']===6?8:6):($id==='sv07-139'&&$ctx['enemy']['prizeCount']<=3?8:4);
            return 130+4*$draw-($id==='me03-076'?8:0);
        }
    }
    if($a['type']==='ability'&&$card&&PokeIsFossil($card['id'])&&str_contains($card['ref'],'Bench')
        &&count($ctx['own']['Bench'])===5&&PokeRelicanthDrawWantsBulwark($ctx)&&!PokeBotFieldCount($ctx,['me05-072'])&&isset($ctx['hand']['me05-072']))return 190;
    $score=PokeRelicanthActionScore($ctx,$a);
    if($a['type']==='ability'&&($card['id']??'')==='me05-072'&&str_contains($card['ref'],'Bench')&&PokeRelicanthDrawWantsBulwark($ctx))$score-=30;
    if($a['type']==='attack'&&$score===10000.0){
        $target=$ctx['enemy']['Active'][0]??null;
        if($target&&in_array('sv06-167',$target['energy'],true)&&!($ctx['enemy']['legacyEnergyUsed']??false)
            &&PokePrizeValue($target['id'])-1<$ctx['own']['prizeCount'])return 50;
    }
    return $score;
}
function PokeRelicanthDrawDecision(array $ctx,array $d): string {
    if(str_contains($d['prompt'],'new Active')&&PokeRelicanthDrawWantsBulwark($ctx)){
        $choices=$d['choices'];$rank=static fn($c)=>$c['card']==='me05-017'?300:(PokeIsFossil($c['card']??'')?($c['card']==='me05-072'?10:100):0);
        usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));return $choices[0]['value']??'-';
    }
    if(str_contains($d['prompt'],'Rare Candy:')){
        $choices=$d['choices'];usort($choices,fn($a,$b)=>(str_contains($b['value'],'Bench')?100:0)<=>(str_contains($a['value'],'Bench')?100:0));
        return $choices[0]['value']??'-';
    }
    if(str_contains($d['prompt'],'Iris:')){
        $choices=$d['choices'];usort($choices,fn($a,$b)=>PokeRelicanthDrawDiscardScore($ctx,$a['card'])<=>PokeRelicanthDrawDiscardScore($ctx,$b['card']));
        return $choices[0]['value']??'-';
    }
    if(str_contains($d['prompt'],'Lucky Helmet')){
        $choices=$d['choices'];$active=$ctx['own']['Active'][0]['ref']??'';
        $score=static fn($c)=>$c['card']==='me05-017'?($c['value']===$active?300:200):0;
        usort($choices,fn($a,$b)=>$score($b)<=>$score($a));return $choices[0]['value']??'-';
    }
    if(!str_contains($d['prompt'],'new Active')&&!str_contains($d['prompt'],'Boss')&&!str_contains($d['prompt'],'Brave')&&in_array($d['type'],['MZCHOOSE','MZMAYCHOOSE','MZMULTICHOOSE'],true)){
        $choices=$d['choices'];usort($choices,fn($a,$b)=>PokeRelicanthDrawSearchScore($ctx,$b['card']??'')<=>PokeRelicanthDrawSearchScore($ctx,$a['card']??''));
        if($d['type']==='MZMULTICHOOSE'){
            $limit=$d['max'];if(str_contains($d['prompt'],'Fossil Quarry'))$limit=min($limit,max(0,PokeRelicanthFossilLimit($ctx)-count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])))));
            return implode('&',array_column(array_slice($choices,0,$limit),'value'))?:'-';
        }
        return $choices[0]['value']??'-';
    }
    return PokeRelicanthDecision($ctx,$d);
}
function PokeRelicanthDrawChoose(array $view): ?array {
    $ctx=PokeBotContext($view);
    if($view['decision'])return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeRelicanthDrawDecision($ctx,$view['decision'])];
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeRelicanthDrawActionScore($ctx,$b)<=>PokeRelicanthDrawActionScore($ctx,$a));
    return $actions[0]??null;
}
