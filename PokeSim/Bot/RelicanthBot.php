<?php
/** Uses only this seat's observation and legal search candidates. */
function PokeRelicanthDamage(array $ctx,array $target,?int $fossils=null): int {
    $active=$ctx['own']['Active'][0]??null;
    if(!$active||$active['id']!=='me05-017')return 0;
    $damage=10+30*($fossils??count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id']))));
    if(($active['tool']??'-')==='sv10.5w-080'&&CardSuffix($target['id'])==='ex')$damage+=30;
    foreach(CardWeaknesses($target['id'])??[] as $w)if($w['type']==='Water')$damage=str_contains($w['value'],'×')||str_contains($w['value'],'x')?$damage*(int)preg_replace('/\D/','',$w['value']):$damage+(int)$w['value'];
    foreach(CardResistances($target['id'])??[] as $r)if($r['type']==='Water')$damage+=(int)$r['value'];
    if($target['id']==='me05-072'&&empty($target['counters']['noAbilities']))$damage-=10;
    return max(0,$damage);
}
function PokeRelicanthEnergyNeeded(array $ctx,?array $card=null): int {
    $card??=$ctx['own']['Active'][0]??null;
    $root=$ctx['enemy']['Active'][0]??null;
    return max(0,1+($root&&$root['id']==='sv07-130'&&empty($root['counters']['noAbilities'])?1:0)-count($card['energy']??[]));
}
/** Prepare tomorrow's attachment as well as today's attack. */
function PokeRelicanthNeedsEnergyAccess(array $ctx): bool {
    if(count(array_filter($ctx['own']['Hand'],fn($c)=>CardType($c['id'])==='Energy')))return false;
    foreach($ctx['field'] as $card)if($card['id']==='me05-017'&&PokeRelicanthEnergyNeeded($ctx,$card)>0)return true;
    return isset($ctx['hand']['me05-017'])||PokeBotFieldCount($ctx,['me05-017'])<2;
}
function PokeRelicanthNeedsAttacker(array $ctx): bool {
    return PokeBotFieldCount($ctx,['me05-017'])+($ctx['hand']['me05-017']??0)<2;
}
/** Keep replacements in hand while an Antique can bridge an Active knockout.
 * Bench one only to replace a fossil Active, or prevent an empty-field loss. */
function PokeRelicanthWantBackup(array $ctx): bool {
    if(count(array_filter($ctx['own']['Bench'],fn($c)=>$c['id']==='me05-017')))return false;
    $active=$ctx['own']['Active'][0]??null;
    if(!$active||$active['id']!=='me05-017')return true;
    return !count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])))
        &&!count(array_filter($ctx['own']['Hand'],fn($c)=>PokeIsFossil($c['id'])))
        &&!array_filter($ctx['view']['actions'],fn($a)=>$a['type']==='ability'&&str_starts_with($a['source']??'','Stadium-'));
}
function PokeRelicanthFossilLimit(array $ctx): int {
    $nonFossils=count(array_filter($ctx['own']['Bench'],fn($c)=>!PokeIsFossil($c['id'])));
    // Fossils are disposable: do not leave a slot empty before the backup arrives.
    return 5-$nonFossils-(PokeRelicanthWantBackup($ctx)&&isset($ctx['hand']['me05-017'])?1:0);
}
function PokeRelicanthTargetScore(array $ctx,array $target): float {
    $damage=PokeRelicanthDamage($ctx,$target);
    return min($damage,$target['hp']-$target['damage'])+($damage>=$target['hp']-$target['damage']?150*PokePrizeValue($target['id']):0);
}
function PokeRelicanthSearchScore(array $ctx,string $id): float {
    $active=$ctx['own']['Active'][0]??null;
    $energy=count(array_filter($ctx['own']['Hand'],fn($c)=>CardType($c['id'])==='Energy'));
    $basics=PokeBotFieldCount($ctx,['me05-017'])+($ctx['hand']['me05-017']??0);
    if($id==='me05-017')return $basics===0?400:($basics<2?220:0);
    if(CardType($id)==='Energy')return PokeRelicanthNeedsEnergyAccess($ctx)?200:($energy<2?30:0);
    if(PokeIsFossil($id))return count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])))<PokeRelicanthFossilLimit($ctx)?($id==='sv10.5w-079'?140:120):0;
    if($id==='me05-076')return ($ctx['view']['stadium']['id']??'')!=='me05-076'&&!isset($ctx['hand'][$id])?190:0;
    if($id==='sv06.5-057')return !isset($ctx['hand'][$id])&&(($ctx['view']['stadium']['id']??'')!=='me05-076'||!$energy)?170:0;
    if($id==='me01-119')return !isset($ctx['hand'][$id])?130:0;
    if($id==='me03-081')return PokeRelicanthNeedsAttacker($ctx)&&!isset($ctx['hand'][$id])?240:0;
    if($id==='me02.5-196')return PokeRelicanthNeedsAttacker($ctx)&&!isset($ctx['hand'][$id])&&count(array_filter($ctx['own']['Discard'],fn($c)=>$c['id']==='me05-017'))?260:0;
    if($id==='me03-072'||$id==='sv10.5w-082')return PokeRelicanthNeedsEnergyAccess($ctx)&&!isset($ctx['hand'][$id])?210:0;
    return 10;
}
function PokeRelicanthDecision(array $ctx,array $decision): string {
    if($decision['type']==='NUMBERCHOOSE')return (string)$decision['max'];
    if($decision['type']==='YESNO')return 'YES';
    if(str_contains($decision['prompt'],'discard three')){
        $choices=$decision['choices'];
        // Preserve attackers, attachments and recovery; redundant fossils pay the cost.
        $keep=static function($c)use($ctx){
            $id=$c['card'];
            if($id==='me05-017'||CardType($id)==='Energy')return 1000;
            if(PokeIsFossil($id))return 0;
            if($id==='me05-076'&&($ctx['view']['stadium']['id']??'')==='me05-076')return 0;
            if(in_array($id,['me03-081','me02.5-196','me03-072','sv10.5w-082'],true))return 500;
            return PokeRelicanthSearchScore($ctx,$id);
        };
        usort($choices,fn($a,$b)=>$keep($a)<=>$keep($b));
        return implode('&',array_column(array_slice($choices,0,3),'value'));
    }
    $score=function($choice)use($ctx,$decision){
        $id=$choice['card'];if(!$id)return 0;
        $card=PokeBotCard($ctx,$choice['value']);$prompt=$decision['prompt'];
        if(str_contains($prompt,'Boss'))return $card?PokeRelicanthTargetScore($ctx,$card):0;
        if(str_contains($prompt,'Brave'))return $id==='me05-017'?200:0;
        if(str_contains($prompt,'new Active'))return $id==='me05-017'?200+10*count($card['energy']):($id==='me05-072'?30:10);
        return PokeRelicanthSearchScore($ctx,$id);
    };
    $choices=$decision['choices'];usort($choices,fn($a,$b)=>$score($b)<=>$score($a));
    if($decision['type']==='MZMULTICHOOSE'){
        $limit=$decision['max'];
        if(str_contains($decision['prompt'],'Fossil Quarry'))$limit=min($limit,max(0,PokeRelicanthFossilLimit($ctx)-count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])))));
        return implode('&',array_column(array_slice($choices,0,min($limit,count($choices))),'value'))?:'-';
    }
    return $choices[0]['value']??'-';
}
function PokeRelicanthActionScore(array $ctx,array $action): float {
    $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
    $target=isset($action['target'])?PokeBotCard($ctx,$action['target']):null;
    $active=$ctx['own']['Active'][0]??null;
    $bench=count($ctx['own']['Bench']);
    $wantBackup=PokeRelicanthWantBackup($ctx);
    $fossils=count(array_filter($ctx['own']['Bench'],fn($c)=>PokeIsFossil($c['id'])));
    $needFossils=$fossils<PokeRelicanthFossilLimit($ctx);
    switch($action['type']){
        case 'setup-active':return 100;
        case 'ready':return 0;
        case 'bench':return $wantBackup?240:-20;
        case 'attach':return $target['id']==='me05-017'&&PokeRelicanthEnergyNeeded($ctx,$target)>0?($target['ref']===($active['ref']??'')?300:100):-20;
        case 'ability':
            if(str_starts_with($action['source'],'Stadium-'))return $needFossils?210:-20;
            // Release an Active fossil without awarding a Prize, then promote Relicanth.
            if($card&&PokeIsFossil($card['id'])){
                if($card['ref']===($active['ref']??'')&&count(array_filter($ctx['own']['Bench'],fn($c)=>$c['id']==='me05-017')))return 350;
                if($bench===5&&$wantBackup&&isset($ctx['hand']['me05-017']))return $card['id']==='sv10.5w-079'?255:260;
            }
            return -20;
        case 'trainer':
            $id=$card['id'];
            if(PokeIsFossil($id))return $needFossils?180:-20;
            if($id==='me05-076')return ($ctx['view']['stadium']['id']??'')!=='me05-076'?200:-20;
            if($id==='sv06.5-057')return ($ctx['view']['stadium']['id']??'')!=='me05-076'||PokeRelicanthNeedsEnergyAccess($ctx)?160:-20;
            if($id==='me03-072'||$id==='sv10.5w-082')return PokeRelicanthNeedsEnergyAccess($ctx)?280:-20;
            if($id==='me03-081')return PokeBotFieldCount($ctx,['me05-017'])+($ctx['hand']['me05-017']??0)<2?170:-20;
            if($id==='me02.5-196'){
                foreach($ctx['own']['Discard'] as $c)if(PokeRelicanthSearchScore($ctx,$c['id'])>=200)return 290;
                return -20;
            }
            if($id==='sv10.5w-080')return count(array_filter($ctx['field'],fn($c)=>$c['id']==='me05-017'&&($c['tool']??'-')==='-'))?150:-20;
            if($id==='sv01-186'||$id==='sv08-165')return !$ctx['own']['supporterUsed']&&!isset($ctx['hand']['me01-119'])?120:-20;
            if($id==='sv06-163')return $needFossils||PokeRelicanthNeedsEnergyAccess($ctx)||PokeRelicanthNeedsAttacker($ctx)?165:-20;
            if($id==='me01-119')return $needFossils||PokeRelicanthNeedsEnergyAccess($ctx)||PokeRelicanthNeedsAttacker($ctx)?130:5;
            if($id==='me01-114'&&$active&&count($active['energy'])){
                $current=isset($ctx['enemy']['Active'][0])?PokeRelicanthTargetScore($ctx,$ctx['enemy']['Active'][0]):0;
                foreach($ctx['enemy']['Bench'] as $c)if(PokeRelicanthTargetScore($ctx,$c)>$current+80)return 140;
            }
            return -20;
        case 'attack':
            $enemy=$ctx['enemy']['Active'][0]??null;
            return $enemy&&PokeRelicanthDamage($ctx,$enemy)>=$enemy['hp']-$enemy['damage']&&PokePrizeValue($enemy['id'])>=$ctx['own']['prizeCount']?10000:50;
        case 'retreat':return $target['id']==='me05-017'&&count($target['energy'])&&!count($active['energy'])?90:-20;
        case 'end':return -5;
    }
    return -20;
}
function PokeRelicanthChoose(array $view): ?array {
    $ctx=PokeBotContext($view);
    if($view['decision'])return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeRelicanthDecision($ctx,$view['decision'])];
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeRelicanthActionScore($ctx,$b)<=>PokeRelicanthActionScore($ctx,$a));
    return $actions[0]??null;
}
