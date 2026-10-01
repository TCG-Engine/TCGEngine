<?php
/** Deck policy over a seat observation. Never reads the opposing hand, deck or Prizes. */
function PokeBotContext(array $view): array {
    $seat=$view['viewer']; $own=$view['players'][$seat]; $enemy=$view['players'][3-$seat];
    $field=array_merge($own['Active'],$own['Bench']); $hand=array_count_values(array_column($own['Hand'],'id'));
    $hide=count(array_filter($own['Discard'],fn($c)=>in_array($c['id'],['me05-005','me05-006','me05-034'],true)));
    $energy=($hand['mee-005']??0)+($hand['me03-088']??0);
    $draw=isset($hand['me02.5-192']);
    return compact('seat','own','enemy','field','hand','hide','energy','draw')+['view'=>$view];
}
function PokeBotFieldCount(array $ctx,array $ids): int { return count(array_filter($ctx['field'],fn($c)=>in_array($c['id'],$ids,true))); }
function PokeBotNeedsBlender(array $ctx): bool {
    return $ctx['hide']<4&&!isset($ctx['hand']['sv08-164'])
        &&!in_array('sv08-164',array_column($ctx['own']['Discard'],'id'),true);
}
function PokeBotNeedsDhelmise(array $ctx): bool {
    return PokeBotFieldCount($ctx,['me05-039'])+($ctx['hand']['me05-039']??0)<2;
}
function PokeBotNeedsEnergyAccess(array $ctx): bool {
    if($ctx['energy']>0)return false;
    if(($ctx['hand']['me05-039']??0)>0)return true;
    foreach($ctx['field'] as $card)if(!$card['energy'])return true;
    return false;
}
/** Blender establishes the four-HNS damage threshold, then seeds recovery.
 *  It receives only the choices exposed by the engine, never the hidden deck.
 */
function PokeBotBlenderDecision(array $ctx,array $d): string {
    $choices=$d['choices'];$selected=[];$limit=min($d['max'],count($choices));
    $needed=min($limit,max(0,4-$ctx['hide']));
    // Banette has no pre-evolution in this deck. Prefer Sinistcha next, leaving
    // Poltchageist available to establish a line when the deck permits it.
    $hns=array_values(array_filter($choices,fn($c)=>in_array($c['card'],['me05-034','me05-006','me05-005'],true)));
    $rank=['me05-034'=>3,'me05-006'=>2,'me05-005'=>1];
    usort($hns,fn($a,$b)=>$rank[$b['card']]<=>$rank[$a['card']]);
    foreach(array_slice($hns,0,$needed) as $choice)$selected[]=$choice['value'];
    $discard=array_column($ctx['own']['Discard'],'id');
    $priority=[];
    if(PokeBotNeedsEnergyAccess($ctx)&&!in_array('mee-005',$discard,true))$priority[]='mee-005';
    if(PokeBotNeedsDhelmise($ctx)&&!in_array('me05-039',$discard,true))$priority[]='me05-039';
    // Special Energy cannot be recovered by Night Stretcher/Energy Retrieval.
    // Do not seed it as an inaccessible recovery target.
    $priority[]='sv08-165';
    foreach($priority as $id){
        if(count($selected)>=$limit)break;
        foreach($choices as $choice)if($choice['card']===$id&&!in_array($choice['value'],$selected,true)){
            $selected[]=$choice['value'];break 2;
        }
    }
    // If HNS is already partly in discard, use spare slots on spent Call Bells
    // rather than sacrificing more Pokémon or useful resources unnecessarily.
    foreach($choices as $choice){
        if(count($selected)>=$limit)break;
        if($choice['card']==='sv08-165'&&!in_array($choice['value'],$selected,true))$selected[]=$choice['value'];
    }
    return implode('&',$selected)?:'-';
}
function PokeBotCard(array $ctx,string $ref): ?array {
    foreach ([$ctx['own'],$ctx['enemy']] as $p) foreach (['Hand','Active','Bench'] as $zone) foreach($p[$zone] as $c) if($c['ref']===$ref)return $c;
    return null;
}
function PokeBotDamage(array $ctx,array $attacker,array $target): int {
    $id=$attacker['id'];
    if(in_array($id,['me05-005','me05-006'],true)) {
        if(in_array($target['id'],['me05-005','me05-006','me05-034'],true))return 0;
        return $id==='me05-005'?10:($ctx['hide']>=6?40:0);
    }
    $damage=$id==='me05-039'?($ctx['hide']>=4?170:30):80;
    foreach($ctx['view']['log'] as $event) if($event['turn']===$ctx['view']['turn']&&($event['player']??0)===$ctx['seat']&&($event['card']??'')==='sv09-144'&&($event['event']??'')==='play-trainer'&&CardSuffix($target['id'])==='ex')$damage+=40;
    foreach(CardWeaknesses($target['id'])??[] as $w)if(in_array($w['type'],explode(',',CardTypes($id)??''),true))$damage=str_contains($w['value'],'×')||str_contains($w['value'],'x')?$damage*(int)preg_replace('/\D/','',$w['value']):$damage+(int)$w['value'];
    foreach(CardResistances($target['id'])??[] as $r)if(in_array($r['type'],explode(',',CardTypes($id)??''),true))$damage+=(int)$r['value'];
    return max(0,$damage);
}
function PokeBotAttackValue(array $ctx,array $card,bool $requireEnergy=true): float {
    if($requireEnergy&&!count($card['energy']))return 0;
    if(!empty($card['conditions']['Asleep'])||!empty($card['conditions']['Paralyzed']))return 0;
    $targets=$card['id']==='me05-006'?array_merge($ctx['enemy']['Active'],$ctx['enemy']['Bench']):$ctx['enemy']['Active'];
    $score=0;
    foreach($targets as $target){$damage=PokeBotDamage($ctx,$card,$target);$score+=min($target['hp']-$target['damage'],$damage);if($damage>0&&$damage>=$target['hp']-$target['damage'])$score+=150;}
    return $score;
}
function PokeBotTrainerScore(array $ctx,string $id): float {
    $needsEnergy=$ctx['energy']===0; $needsBasic=PokeBotFieldCount($ctx,['me05-039'])+($ctx['hand']['me05-039']??0)<2;
    $supporter=!$ctx['own']['supporterUsed']&&!($ctx['seat']===$ctx['view']['firstPlayer']&&$ctx['own']['turns']===1);
    $findBlender=PokeBotNeedsBlender($ctx);
    if($findBlender){
        if($id==='me02.5-207')return 270;
        // On the first player's opening turn, finding Petrel still prepares
        // the next turn; after spending a Supporter, avoid redundant searches.
        if(!isset($ctx['hand']['me02.5-207'])&&!$ctx['own']['supporterUsed']){
            if($id==='me02.5-209')return 240;
            if($id==='sv08-165')return 230;
            if($id==='sv10.5b-084')return 220;
        }
        // Do not shuffle away Petrel for draw before using it to find Blender.
        if($id==='me02.5-192'&&isset($ctx['hand']['me02.5-207']))return -20;
    }
    return match($id){
        'sv08-164'=>$ctx['hide']<4?300:40,
        'me02.5-213'=>$ctx['hide']<6?85:($needsBasic?55:-20),
        'me03-072'=>$needsEnergy&&!$ctx['own']['energyUsed']?80:-20,
        'me03-081'=>$needsBasic||(!PokeBotFieldCount($ctx,['me05-005','me05-006'])&&!isset($ctx['hand']['me05-005']))?70:($ctx['hide']<6?40:-20),
        'me02.5-196'=>count(array_filter($ctx['own']['Discard'],fn($c)=>($needsEnergy&&$c['id']==='mee-005')||($needsBasic&&$c['id']==='me05-039')||(!PokeBotFieldCount($ctx,['me05-005','me05-006'])&&$c['id']==='me05-005')))?65:-20,
        'sv10.5w-082'=>$needsEnergy?65:-20,
        'sv10.5b-084','sv08-165'=>$supporter&&!$ctx['draw']&&!isset($ctx['hand']['me02.5-207'])?75:-20,
        'me02.5-209'=>$supporter&&!$ctx['draw']&&!isset($ctx['hand']['me02.5-207'])?60:-20,
        'me02.5-207'=>!$ctx['draw']?55:($ctx['hide']<4&&!isset($ctx['hand']['sv08-164'])?60:-20),
        'me02.5-192'=>count($ctx['own']['Hand'])<6||$needsEnergy?50:5,
        'me04-082'=>$ctx['enemy']['handCount']>3?70:-20,
        'sv09-144'=>isset($ctx['enemy']['Active'][0])&&CardSuffix($ctx['enemy']['Active'][0]['id'])==='ex'&&isset($ctx['own']['Active'][0])&&count($ctx['own']['Active'][0]['energy'])&&in_array($ctx['own']['Active'][0]['id'],['me05-039','me05-034'],true)?90:-20,
        'me02.5-183'=>PokeBotBossScore($ctx),
        default=>-20,
    };
}
function PokeBotBossScore(array $ctx): float {
    $active=$ctx['own']['Active'][0]??null;if(!$active||!count($active['energy']))return -20;
    $current=$ctx['enemy']['Active'][0]??null;
    if($current&&PokeBotDamage($ctx,$active,$current)>=$current['hp']-$current['damage'])return -20;
    foreach($ctx['enemy']['Bench'] as $target)if(PokeBotDamage($ctx,$active,$target)>=$target['hp']-$target['damage'])return 90;
    return -20;
}
function PokeBotActionScore(array $ctx,array $a): float {
    $card=isset($a['source'])?PokeBotCard($ctx,$a['source']):null;
    $target=isset($a['target'])?PokeBotCard($ctx,$a['target']):null;
    switch($a['type']) {
        case 'setup-active':return $card['id']==='me05-039'?100:80;
        case 'ready':return 0;
        case 'bench':
            if($card['id']==='me05-039')return PokeBotFieldCount($ctx,['me05-039'])<2?35:-10;
            return PokeBotFieldCount($ctx,['me05-005','me05-006'])===0?34:-10;
        case 'evolve':return 40;
        case 'trainer':return PokeBotTrainerScore($ctx,$card['id']);
        case 'attach':
            $active=$ctx['own']['Active'][0]??null;
            $priority=PokeBotAttackValue($ctx,$target,false);
            if(count($target['energy'])===0)return 45+$priority/30+($target['ref']===($active['ref']??'')?5:0)+($card['id']==='me03-088'&&$target['id']==='me05-039'&&PokeBotFieldCount($ctx,['me05-039'])<2?10:0);
            // Dhelmise may need three units to retreat into an online Sinistcha.
            if($active&&$target['ref']===$active['ref']&&$target['id']==='me05-039'&&count($target['energy'])<3)foreach($ctx['own']['Bench'] as $bench)if(PokeBotAttackValue($ctx,$bench)>PokeBotAttackValue($ctx,$active)+40)return 35;
            return -10;
        case 'retreat':
            $active=$ctx['own']['Active'][0];$gain=PokeBotAttackValue($ctx,$target)-PokeBotAttackValue($ctx,$active);
            return $gain>40?42+$gain/20-count($a['payment'])*3:-20;
        case 'attack':return 10+PokeBotAttackValue($ctx,$card)/20;
        case 'end':return 0;
        default:return -20;
    }
}
function PokeBotDiscardScore(array $ctx,string $id): float {
    if($id==='me05-034')return 100;
    if($id==='me05-006')return ($ctx['hand'][$id]??0)>1||PokeBotFieldCount($ctx,['me05-006'])>0||!PokeBotFieldCount($ctx,['me05-005'])?95:65;
    if($id==='me05-005')return PokeBotFieldCount($ctx,['me05-005','me05-006'])>0||($ctx['hand'][$id]??0)>1?98:60;
    if(in_array($id,['mee-005','me03-088'],true))return $ctx['energy']>2?40:-50;
    if($id==='me05-039')return PokeBotFieldCount($ctx,['me05-039'])>=2?45:-30;
    return 30-PokeBotTrainerScore($ctx,$id)/4;
}
function PokeBotSearchScore(array $ctx,string $id): float {
    if($id==='me02.5-207'&&PokeBotNeedsBlender($ctx)&&!isset($ctx['hand'][$id]))return 280;
    if(in_array($id,['mee-005','me03-088'],true))return PokeBotNeedsEnergyAccess($ctx)?180:($ctx['energy']===0?75:5);
    if($id==='sv08-164')return PokeBotNeedsBlender($ctx)?350:0;
    if($id==='me05-039')return PokeBotFieldCount($ctx,['me05-039'])+($ctx['hand'][$id]??0)<2?100:10;
    if($id==='me05-005')return PokeBotFieldCount($ctx,['me05-005','me05-006'])===0&&!isset($ctx['hand'][$id])?90:15;
    if($id==='me05-006')return PokeBotFieldCount($ctx,['me05-005'])>0&&!isset($ctx['hand'][$id])?95:($ctx['hide']<6?50:0);
    if($id==='me05-034')return $ctx['hide']<6?40:-10;
    return PokeBotTrainerScore($ctx,$id);
}
function PokeBotDecision(array $ctx,array $d): string {
    if($d['type']==='NUMBERCHOOSE')return (string)$d['max'];
    $choices=$d['choices'];$prompt=$d['prompt'];
    if($d['type']==='MZMULTICHOOSE'&&str_contains($prompt,'Brilliant Blender'))return PokeBotBlenderDecision($ctx,$d);
    $score=function($c)use($ctx,$prompt){
        $id=$c['card'];
        if($id===null)return 0; // Prize choices are uniform: never inspect face-down cards.
        if(str_contains($prompt,'discard'))return PokeBotDiscardScore($ctx,$id);
        if(str_contains($prompt,'Boss')){$target=PokeBotCard($ctx,$c['value']);$active=$ctx['own']['Active'][0];$damage=PokeBotDamage($ctx,$active,$target);return $damage>=($target['hp']-$target['damage'])?200+$damage:$damage;}
        if(str_contains($prompt,'new Active')){$target=PokeBotCard($ctx,$c['value']);return PokeBotAttackValue($ctx,$target)+($id==='me05-039'?20:0);}
        if(str_contains($prompt,'Telepathic'))return $id==='me05-039'&&PokeBotFieldCount($ctx,['me05-039'])<2?100:0;
        return PokeBotSearchScore($ctx,$id);
    };
    usort($choices,fn($a,$b)=>$score($b)<=>$score($a));
    if($d['type']==='MZMULTICHOOSE') {
        $count=$d['max'];
        if(str_contains($prompt,'Telepathic'))$count=min($count,max(0,2-PokeBotFieldCount($ctx,['me05-039'])));
        $count=max($d['min'],min($count,count($choices)));
        return implode('&',array_column(array_slice($choices,0,$count),'value'))?:'-';
    }
    return $choices[0]['value']??'-';
}
function PokeBotChoose(array $view): ?array {
    $ctx=PokeBotContext($view);$d=$view['decision'];
    if($d)return ['type'=>'decision','player'=>$ctx['seat'],'value'=>PokeBotDecision($ctx,$d)];
    $actions=$view['actions'];if(!$actions)return null;
    usort($actions,fn($a,$b)=>PokeBotActionScore($ctx,$b)<=>PokeBotActionScore($ctx,$a));
    return $actions[0];
}
/** Advance only bot-controlled work; stop at every human decision, including on bot turns. */
function PokeRunBot(int $seat=2,int $budget=150): int {
    $steps=0;
    while(!GetWinner()&&$steps<$budget) {
        // Setup is simultaneous; allow the bot to commit even while the human sets up.
        if((new DecisionQueueController())->AnyQueuePending()&&PokePendingPlayer()!==$seat)break;
        $action=PokeBotChoose(PokeObservation($seat));if($action===null)break;
        PokeApplyAction($action);++$steps;
    }
    if($steps===$budget&&!GetWinner()&&PokeBotChoose(PokeObservation($seat))!==null)throw new RuntimeException('Bot action budget exhausted');
    return $steps;
}
