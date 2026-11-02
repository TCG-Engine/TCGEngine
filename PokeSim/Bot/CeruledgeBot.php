<?php
/** Ceruledge policy: own hand, public board/discard and engine-exposed choices only. */
function PokeCeruledgeContext(array $view): array {
    $ctx=PokeBotContext($view);
    $ctx['fire']=$ctx['hand']['mee-002']??0;
    $ctx['fighting']=count(array_filter($ctx['own']['Hand'],fn($c)=>PokeCeruledgeIsFightingEnergy($c['id'])));
    $ctx['lunarReady']=PokeBotFieldCount($ctx,['me01-074'])>0&&PokeBotFieldCount($ctx,['me01-075'])>0&&!($ctx['own']['lunarCycleUsed']??false);
    $ctx['discardEnergy']=count(array_filter($ctx['own']['Discard'],fn($c)=>CardType($c['id'])==='Energy'));
    $ctx['lines']=PokeBotFieldCount($ctx,['me02-019','sv08-036','me02-020']);
    // The starting deck list supplies this total. This is an upper bound from known
    // cards, not a peek at the deck or face-down Prizes.
    $knownFire=$ctx['fire']+count(array_filter($ctx['own']['Discard'],fn($c)=>$c['id']==='mee-002'));
    foreach($ctx['field'] as $card)$knownFire+=count(array_filter($card['energy'],fn($id)=>$id==='mee-002'));
    $ctx['unseenFire']=max(0,min($ctx['own']['deckCount'],($ctx['own']['initialBasicFire']??20)-$knownFire));
    return $ctx;
}
function PokeCeruledgeIsFightingEnergy(string $id): bool {
    return CardType($id)==='Energy'&&CardEnergyType($id)==='Normal'&&in_array('Fighting',PokeEnergyTypes($id),true);
}
/** A tutor cannot know whether Blender is prized; use only public/own information. */
function PokeCeruledgeNeedsBlender(array $ctx): bool {
    return $ctx['discardEnergy']<15&&!isset($ctx['hand']['sv08-164'])
        &&!in_array('sv08-164',array_column($ctx['own']['Discard'],'id'),true)
        &&$ctx['unseenFire']>($ctx['fire']===0?PokeCeruledgeFireReserve($ctx):0);
}
function PokeCeruledgeCanPay(array $ctx,array $card,int $index): bool {
    $cost=CardAttacks($card['id'])[$index]['cost']??[];
    if(CardStage($card['id'])==='Basic'&&($ctx['enemy']['Active'][0]['id']??'')==='sv07-130'&&empty($ctx['enemy']['Active'][0]['counters']['noAbilities']))$cost[]='Colorless';
    return PokeLopunnyCanPay($card,$cost);
}
/** Keep a hand attachment only when the visible board can still use it. */
function PokeCeruledgeFireReserve(array $ctx): int {
    if(PokeBotFieldCount($ctx,['me02-020'])&&!PokeBotFieldCount($ctx,['sv08-036']))return 4;
    if($ctx['own']['energyUsed'])return 0;
    if(!$ctx['lines'])return 1;
    foreach($ctx['field'] as $card) {
        if(in_array($card['id'],['me02-019','sv08-036','me02-020'],true)&&(!PokeHasAttackEnergy((object)['Energy'=>$card['energy']],['Fire'])||count($card['energy'])<(PokeBotEnemyBulwark($ctx)?3:1)))return 1;
        if($card['id']==='sv07-118'&&str_contains($card['ref'],'Active')&&count($card['energy'])<(int)CardRetreat($card['id']))return 1;
    }
    return 0;
}
function PokeCeruledgeDamage(array $ctx,array $card,int $index,array $target): int {
    if(!empty($card['conditions']['Asleep'])||!empty($card['conditions']['Paralyzed']))return 0;
    $damage=match($card['id']) {
        'sv08-036'=>$index===0?30+20*$ctx['discardEnergy']:280,
        'me02-020'=>$ctx['fire']>=4?220:0,
        'me02-019'=>$index===1?10:0,
        'sv07-118'=>$ctx['view']['stadium']?70:0,
        'sv08.5-004'=>10,
        'me01-074'=>50,
        'me01-075'=>count(array_filter($ctx['own']['Bench'],fn($c)=>$c['id']==='me01-074'))>0?70:0,
        default=>0,
    };
    if($damage===0)return 0;
    if(($target['turnEffects']['digThrough']??-1)>=$ctx['view']['turn'])return 0;
    if(PokeBotEnemyBulwark($ctx)&&count($card['energy'])<=2)return 0;
    if($target['id']==='me03-068'&&empty($target['counters']['noAbilities']))$damage=max(0,$damage-30);
    if($card['id']!=='me01-075') {
        foreach(CardWeaknesses($target['id'])??[] as $w)if(in_array($w['type'],explode(',',CardTypes($card['id'])??''),true))$damage=str_contains($w['value'],'×')||str_contains($w['value'],'x')?$damage*(int)preg_replace('/\D/','',$w['value']):$damage+(int)$w['value'];
        foreach(CardResistances($target['id'])??[] as $r)if(in_array($r['type'],explode(',',CardTypes($card['id'])??''),true))$damage+=(int)$r['value'];
    }
    if(($ctx['enemy']['Active'][0]['id']??'')==='me05-072'&&empty($ctx['enemy']['Active'][0]['counters']['noAbilities']))$damage-=10;
    return max(0,$damage);
}
function PokeCeruledgeAttackValue(array $ctx,array $card,?array $target=null,bool $requireEnergy=true,?int $onlyIndex=null): float {
    $target??=$ctx['enemy']['Active'][0]??null;
    if(!$target)return 0;
    $best=0;
    foreach(CardAttacks($card['id'])??[] as $i=>$attack) {
        if($onlyIndex!==null&&$i!==$onlyIndex||$requireEnergy&&!PokeCeruledgeCanPay($ctx,$card,$i))continue;
        $damage=PokeCeruledgeDamage($ctx,$card,$i,$target);
        $hp=max(0,$target['hp']-$target['damage']);
        $value=min($hp,$damage);
        if($damage>0&&$damage>=$hp)$value+=200*min($ctx['own']['prizeCount'],PokePrizeValue($target['id']))+PokeBotVisibleThreat($ctx,$target);
        if($damage>0&&$damage>=$hp&&PokePrizeValue($target['id'])>=$ctx['own']['prizeCount'])$value+=100000;
        $best=max($best,$value);
    }
    return $best;
}
function PokeCeruledgeSearchScore(array $ctx,string $id): float {
    $hand=$ctx['hand'][$id]??0;
    if(PokeCeruledgeIsFightingEnergy($id))return $ctx['lunarReady']&&$ctx['fighting']===0?390:($ctx['fighting']===0?120:-60);
    return match($id) {
        'me01-074'=>!$hand&&!PokeBotFieldCount($ctx,[$id])?350:-20,
        'me01-075'=>!$hand&&!PokeBotFieldCount($ctx,[$id])?345:-20,
        'sv08.5-004'=>$ctx['own']['turns']<=1&&!$hand&&!PokeBotFieldCount($ctx,[$id])?120:-10,
        'me02.5-142'=>!$hand&&!PokeBotFieldCount($ctx,[$id])&&$ctx['enemy']['prizeCount']<6?240:-20,
        'sv06-167'=>!($ctx['own']['legacyEnergyUsed']??false)&&$ctx['lines']>0?310:(!$ctx['own']['energyUsed']&&PokeCeruledgeFireReserve($ctx)>0?290:-60),
        'me02-019'=>$ctx['lines']+$hand<2?360:($ctx['lines']+$hand<3?95:-20),
        'sv08-036'=>PokeBotFieldCount($ctx,['me02-019'])>$hand?320:($hand===0&&$ctx['lines']<2?110:-20),
        'me02-020'=>!$hand&&PokeBotFieldCount($ctx,['me02-019'])&&$ctx['fire']>=4&&30+20*$ctx['discardEnergy']<220&&!isset($ctx['hand']['sv08-036'])?180:-20,
        'mee-002'=>!$ctx['own']['energyUsed']&&$ctx['fire']===0&&PokeCeruledgeFireReserve($ctx)>0?290:($ctx['fire']<4&&PokeBotFieldCount($ctx,['me02-020'])&&!PokeBotFieldCount($ctx,['sv08-036'])?170:-60),
        'sv08-191'=>!$ctx['own']['energyUsed']&&PokeBotFieldCount($ctx,['sv08-036','me02-020'])?200:-10,
        'sv07-118'=>$ctx['own']['turns']<=1&&!$hand&&!PokeBotFieldCount($ctx,[$id])?120:-10,
        default=>$hand?0:PokeCeruledgeTrainerScore($ctx,$id),
    };
}
function PokeCeruledgeDrawCount(array $ctx): int {
    return ($ctx['hand']['sv06-145']??0)+($ctx['hand']['me02.5-192']??0)+($ctx['hand']['sv08.5-107']??0)+($ctx['hand']['me03-079']??0);
}
/** Higher means expendable. Preserve an attachment, missing evolution and backup line. */
function PokeCeruledgeDiscardScore(array $ctx,string $id): float {
    if(PokeCeruledgeIsFightingEnergy($id))return $ctx['lunarReady']&&$ctx['fighting']<=1?-180:160;
    if(in_array($id,['me01-074','me01-075'],true))return PokeBotFieldCount($ctx,[$id])||($ctx['hand'][$id]??0)>1?110:-150;
    if($id==='sv08.5-004')return $ctx['own']['turns']>2||PokeBotFieldCount($ctx,[$id])?110:-50;
    if($id==='me02.5-142')return !PokeBotFieldCount($ctx,[$id])&&$ctx['enemy']['prizeCount']<6?-130:80;
    if($id==='sv06-167')return !($ctx['own']['legacyEnergyUsed']??false)?-170:($ctx['own']['energyUsed']||PokeCeruledgeFireReserve($ctx)===0?160:-180);
    if($id==='mee-002') {
        $reserve=PokeCeruledgeFireReserve($ctx);
        return $ctx['fire']>$reserve?160:-180;
    }
    if($id==='sv08-036')return PokeBotFieldCount($ctx,['me02-019'])>=($ctx['hand'][$id]??0)?-160:40;
    if($id==='me02-019')return $ctx['lines']<2?-150:65;
    if($id==='me02-020')return PokeBotFieldCount($ctx,['sv08-036'])||isset($ctx['hand']['sv08-036'])?100:-30;
    if($id==='sv07-118')return 110;
    if($id==='me04-080')return $ctx['view']['stadium']?105:20;
    if($id==='me02.5-183')return ($ctx['hand'][$id]??0)>1?60:10;
    // Petrel's fallback tutor score may itself plan a two-card Ultra Ball cost.
    // Do not re-enter that planner while ranking Petrel as a payment card.
    if($id==='me02.5-207')return PokeCeruledgeNeedsBlender($ctx)?-150:(($ctx['hand'][$id]??0)>1?70:-40);
    if(in_array($id,['sv06-145','me02.5-192','sv08.5-107','me03-079'],true)&&PokeCeruledgeDrawCount($ctx)<=1)return -140;
    // Avoid recursive cost planning when another Ultra Ball is a discard candidate.
    if($id==='me01-131')return ($ctx['hand'][$id]??0)>1?80:($ctx['lines']<2||PokeBotFieldCount($ctx,['me02-019'])>($ctx['hand']['sv08-036']??0)?-80:40);
    if(CardTrainerType($id)==='Supporter'&&$ctx['own']['supporterUsed'])return 85;
    // Search scores suppress cards already in hand; they therefore cannot
    // measure the value of the last Trainer we are about to discard.
    if(CardType($id)==='Trainer')return ($ctx['hand'][$id]??0)>1?70:40-PokeCeruledgeTrainerScore($ctx,$id)/2;
    return $id==='sv08-191'&&PokeBotFieldCount($ctx,['sv08-036','me02-020'])?-60:40;
}
/** Sequentially check both payments, including the resources left after the first. */
function PokeCeruledgeSafeDiscards(array $ctx,?string $resolvingCard=null): bool {
    $remaining=$ctx['own']['Hand'];$after=$ctx;
    if($resolvingCard!==null)foreach($remaining as $i=>$card)if($card['id']===$resolvingCard){unset($remaining[$i]);--$after['hand'][$resolvingCard];break;}
    for($i=0;$i<2;++$i){
        usort($remaining,fn($a,$b)=>PokeCeruledgeDiscardScore($after,$b['id'])<=>PokeCeruledgeDiscardScore($after,$a['id']));
        $next=array_shift($remaining);
        if(!$next||PokeCeruledgeDiscardScore($after,$next['id'])<=0)return false;
        --$after['hand'][$next['id']];if($next['id']==='mee-002')--$after['fire'];
        if(PokeCeruledgeIsFightingEnergy($next['id']))--$after['fighting'];
    }
    return true;
}
function PokeCeruledgeBossPlan(array $ctx): ?array {
    $active=$ctx['own']['Active'][0]??null;
    if(!$active||!PokeBotCanAttack($ctx,$active))return null;
    $baseline=PokeCeruledgeAttackValue($ctx,$active);$best=null;
    foreach($ctx['enemy']['Bench'] as $target) {
        $gust=PokeBotGustContext($ctx,$target);
        $value=PokeCeruledgeAttackValue($gust,$active,$target);
        $ko=false;
        foreach(CardAttacks($active['id'])??[] as $i=>$attack)if(PokeCeruledgeCanPay($gust,$active,$i)&&PokeCeruledgeDamage($gust,$active,$i,$target)>=max(1,$target['hp']-$target['damage']))$ko=true;
        if($ko&&$value>$baseline+40&&(!$best||$value>$best['value']))$best=['ref'=>$target['ref'],'value'=>$value,'prizes'=>PokePrizeValue($target['id'])];
    }
    return $best;
}
function PokeCeruledgeTrainerScore(array $ctx,string $id): float {
    $count=count($ctx['own']['Hand']);$deck=$ctx['own']['deckCount'];
    $needEvolution=PokeBotFieldCount($ctx,['me02-019'])>($ctx['hand']['sv08-036']??0);
    switch($id) {
        case 'me01-116':return $deck>0&&((!PokeBotFieldCount($ctx,['me01-074'])&&!isset($ctx['hand']['me01-074']))||(!PokeBotFieldCount($ctx,['me01-075'])&&!isset($ctx['hand']['me01-075']))||($ctx['lunarReady']&&$ctx['fighting']===0))?280:-20;
        case 'me03-074':return $ctx['discardEnergy']<15&&$ctx['lines']>0&&$deck>6?200:-20;
        case 'sv05-144':return $ctx['lines']<2&&count($ctx['own']['Bench'])<5&&$deck>0?255:-20;
        case 'me03-081':return $ctx['lines']+($ctx['hand']['me02-019']??0)<2&&$deck>0?255:-20;
        case 'sv10.5w-084':return $needEvolution&&$deck>0?300:-20;
        case 'me02.5-207':
            if(PokeCeruledgeNeedsBlender($ctx))return 320;
            if(!isset($ctx['hand']['me04-080'])&&!$ctx['view']['stadium'])return 155;
            if($needEvolution&&!isset($ctx['hand']['me01-131'])&&PokeCeruledgeSafeDiscards($ctx,'me02.5-207'))return 165;
            return -20;
        case 'sv08-164':return $ctx['discardEnergy']<15&&$ctx['unseenFire']>($ctx['fire']===0?PokeCeruledgeFireReserve($ctx):0)?360:-20;
        case 'sv10.5b-084':return $deck>3?(!$ctx['own']['supporterUsed']&&PokeCeruledgeDrawCount($ctx)===0?250:200):-20;
        case 'me04-080':return !$ctx['view']['stadium']?220:(($ctx['view']['stadium']['id']??'')!==$id?120:-20);
        case 'me01-131':
            if(!PokeCeruledgeSafeDiscards($ctx,'me01-131'))return -20;
            return $ctx['lines']<2||$needEvolution?210:($ctx['fire']>=2+PokeCeruledgeFireReserve($ctx)&&$deck>8?160:-20);
        case 'me02.5-196':
            foreach($ctx['own']['Discard'] as $c)if((CardType($c['id'])==='Pokemon'||CardType($c['id'])==='Energy'&&CardEnergyType($c['id'])==='Normal')&&PokeCeruledgeSearchScore($ctx,$c['id'])>=170)return 230;
            return -20;
        case 'sv06-145':return $deck>3&&($ctx['fire']>=2||$count<5||$needEvolution||$ctx['lines']<2)?170+min(50,10*$ctx['fire']):-20;
        case 'me03-079':return $deck>3&&($ctx['fire']>=2||$count<5||$needEvolution||$ctx['lines']<2)?175+min(50,10*$ctx['fire']):-20;
        case 'me02.5-192':return $deck>3&&($count<6||$needEvolution||$ctx['lines']<2||$ctx['fire']>=3&&!isset($ctx['hand']['sv06-145'])&&!isset($ctx['hand']['sv08.5-107']))?130:-20;
        case 'sv08.5-107':return $deck>3?185:-20;
        case 'me02-089':return $ctx['discardEnergy']<15&&$ctx['unseenFire']>=4&&$ctx['fire']<4?245:-20;
        case 'me02.5-183':$plan=PokeCeruledgeBossPlan($ctx);return $plan?($plan['value']>100000?200000:($plan['prizes']>=2?240:70)):-20;
    }
    return -20;
}
function PokeCeruledgeActionScore(array $ctx,array $action): float {
    $card=isset($action['source'])?PokeBotCard($ctx,$action['source']):null;
    $target=isset($action['target'])?PokeBotCard($ctx,$action['target']):null;
    $active=$ctx['own']['Active'][0]??null;
    switch($action['type']) {
        case 'setup-active':return $card['id']==='sv08.5-004'?400:($card['id']==='me02-019'?300:($card['id']==='me01-075'?200:($card['id']==='me01-074'?150:100)));
        case 'bench':
            if(in_array($card['id'],['me01-074','me01-075'],true))return !PokeBotFieldCount($ctx,[$card['id']])?320:(count($ctx['field'])===1?80:-20);
            if($card['id']==='me02-019')return $ctx['lines']<2?340:($ctx['lines']<3?100:-20);
            if($card['id']==='me02.5-142')return PokeBotFieldCount($ctx,[$card['id']])?-20:($ctx['enemy']['prizeCount']<6&&$ctx['view']['phase']!=='SETUP'?245:(count($ctx['field'])===1?80:-20));
            if($card['id']==='sv08.5-004')return !PokeBotFieldCount($ctx,[$card['id']])&&$ctx['own']['turns']<=1?250:(count($ctx['field'])===1?80:-20);
            if($ctx['view']['phase']==='SETUP')return -20;
            if($ctx['own']['turns']<=1&&!PokeBotFieldCount($ctx,['sv07-118']))return 250;
            // A spare Basic prevents an immediate loss when the lone Active falls.
            return count($ctx['field'])===1?80:-20;
        case 'evolve':
            if($card['id']==='sv08-036')return 330;
            if($card['id']==='me02-020')return $ctx['fire']>=4&&30+20*$ctx['discardEnergy']<220&&!isset($ctx['hand']['sv08-036'])?325:-20;
            return -20;
        case 'attach':
            if(PokeCeruledgeIsFightingEnergy($card['id'])&&$target['id']==='me01-075'&&count($target['energy'])<(PokeBotEnemyBulwark($ctx)?3:1))return $target['ref']===($active['ref']??'')?300:180;
            // Secure the once-per-game prize reduction before hand-reset effects.
            // A Ceruledge line can also attack using Legacy as its Fire Energy.
            if($card['id']==='sv06-167'&&!($ctx['own']['legacyEnergyUsed']??false)&&in_array($target['id'],['sv08-036','me02-019'],true))return $target['id']==='sv08-036'?325:322;
            if($card['id']==='mee-002'&&$ctx['fire']<=4&&($active['id']??'')==='me02-020'&&PokeCeruledgeCanPay($ctx,$active,0)&&!PokeBotFieldCount($ctx,['sv08-036']))return -20;
            if($card['id']==='sv08-191'&&in_array($target['id'],['sv08-036','me02-020'],true))return PokeCeruledgeCanPay($ctx,$target,0)?310:180;
            if($target['id']==='sv07-118'&&$target['ref']===($active['ref']??'')&&count($target['energy'])<(int)CardRetreat($target['id'])&&$ctx['lines']>0)return 315;
            if(in_array($target['id'],['sv08-036','me02-020','me02-019'],true)) {
                $hasFire=PokeHasAttackEnergy((object)['Energy'=>$target['energy']],['Fire']);
                $addsFire=in_array('Fire',PokeEnergyTypes($card['id']),true);
                if(!$hasFire&&!$addsFire)return -20;
                if(!$hasFire&&$addsFire)return 290+($target['ref']===($active['ref']??'')?10:0);
                $needed=PokeBotEnemyBulwark($ctx)?3:1;
                if(count($target['energy'])<$needed)return 290+($target['ref']===($active['ref']??'')?10:0);
            }
            if($active&&$target['ref']===$active['ref']&&count($target['energy'])<(int)CardRetreat($target['id']))foreach($ctx['own']['Bench'] as $b)if(PokeCeruledgeAttackValue($ctx,$b)>PokeCeruledgeAttackValue($ctx,$active)+30)return 250;
            if($target['id']==='sv07-118'&&!$target['energy']&&$target['ref']===($active['ref']??'')&&$ctx['view']['stadium'])return 90;
            if($target['id']==='me01-074'&&$target['ref']===($active['ref']??'')&&PokeCeruledgeIsFightingEnergy($card['id'])&&count($target['energy'])<2)return 100;
            return -20;
        case 'trainer':return PokeCeruledgeTrainerScore($ctx,$card['id']);
        case 'ability':
            if($card['id']==='me01-074')return 350;
            if($card['id']==='sv07-118')return 260;
            if($card['id']==='me02.5-142')return 260;
            if($card['id']==='me04-080') {
                if(!PokeCeruledgeSafeDiscards($ctx))return -20;
                // Draw before Carmine discards the hand or Lillie shuffles it away.
                return $ctx['own']['deckCount']>2?265:-20;
            }
            return -20;
        case 'retreat':
            if($target['id']==='sv08.5-004'&&$active['id']==='me02-019'&&$ctx['own']['turns']<=2&&PokeBotCanAttack($ctx,$target))return 285;
            // Keep the early Item lock until the replacement can deal meaningful
            // damage or take a KO; Budew retreats for free when that is ready.
            if($active['id']==='sv08.5-004'&&$ctx['own']['turns']<=2&&$target['id']==='sv08-036'){
                $damage=PokeCeruledgeDamage($ctx,$target,0,$ctx['enemy']['Active'][0]);
                $hp=$ctx['enemy']['Active'][0]['hp']-$ctx['enemy']['Active'][0]['damage'];
                if($damage<$hp&&$damage<210)return -20;
            }
            $gain=PokeCeruledgeAttackValue($ctx,$target)-PokeCeruledgeAttackValue($ctx,$active);
            // Legal evolutions are played before this choice. A remaining
            // Charcadet cannot evolve now; do not expose it just to end the turn.
            return $gain>20?270+$gain/100:-20;
        case 'attack':
            if($card['id']==='sv08.5-004')return $ctx['own']['turns']<=2?85:55;
            $value=PokeCeruledgeAttackValue($ctx,$card,null,true,$action['index']);
            if($card['id']==='me02-019'&&$action['index']===0)return $ctx['fire']<3&&$ctx['own']['deckCount']>2?45:1;
            return $value>0?20+$value/20:-10;
        case 'end':case 'ready':return 0;
    }
    return -20;
}
function PokeCeruledgeDecision(array $ctx,array $decision): string {
    if($decision['type']==='NUMBERCHOOSE')return (string)$decision['max'];
    if($decision['type']==='YESNO')return 'YES';
    if(str_contains($decision['prompt'],'Brilliant Blender')){
        $energies=array_values(array_filter($decision['choices'],fn($c)=>CardType($c['card']??'')==='Energy'));
        $fire=count(array_filter($energies,fn($c)=>$c['card']==='mee-002'));
        $reserve=$ctx['fire']===0&&PokeCeruledgeFireReserve($ctx)>0?1:0;$selected=[];
        foreach($energies as $choice){
            if(count($selected)>=$decision['max'])break;
            if($choice['card']==='mee-002'){if($fire<=$reserve)continue;--$fire;}
            $selected[]=$choice['value'];
        }
        return implode('&',$selected)?:'-';
    }
    $choices=$decision['choices'];$prompt=$decision['prompt'];$discard=str_contains($prompt,'discard');
    $selected=[];
    $maximum=$decision['type']==='MZMULTICHOOSE'?$decision['max']:1;
    while($choices&&count($selected)<$maximum) {
        $rank=function($choice)use($ctx,$prompt,$discard) {
            $id=$choice['card'];if($id===null)return 0;
            if($discard)return PokeCeruledgeDiscardScore($ctx,$id);
            if(str_contains($prompt,"Explorer's Guidance")) {
                // The first keep may establish a future evolution line. Treat
                // that Charcadet as a partner for the second keep, not just the
                // Basic Pokemon already on the field. Keep useful Blender first.
                if($id==='sv08-164'&&PokeCeruledgeTrainerScore($ctx,$id)>0)return 500;
                $liveBasics=PokeBotFieldCount($ctx,['me02-019']);
                $basics=$ctx['hand']['me02-019']??0;
                $evolutions=$ctx['hand']['sv08-036']??0;
                if($liveBasics===0&&$basics>$evolutions) {
                    if($id==='sv08-036')return 400;
                    if($id==='me02-019')return 120;
                }
            }
            // Pad stops at two evolution lines. Poffin's optional second pick
            // should follow the same limit instead of automatically adding a third.
            if(str_contains($prompt,'Buddy-Buddy Poffin')&&$id==='me02-019'&&$ctx['lines']+($ctx['hand'][$id]??0)>=2)return -20;
            if(str_contains($prompt,'Boss'))return $choice['value']===(PokeCeruledgeBossPlan($ctx)['ref']??'')?100000:0;
            if(str_contains($prompt,'new Active'))return PokeCeruledgeAttackValue($ctx,PokeBotCard($ctx,$choice['value']))+($id==='sv08-036'?60:($id==='me02-019'?40:0));
            if(str_contains($prompt,'Gather Strength')||str_contains($prompt,'Firebreather')||str_contains($prompt,'Fan Call'))return 100;
            return PokeCeruledgeSearchScore($ctx,$id);
        };
        usort($choices,fn($a,$b)=>$rank($b)<=>$rank($a));
        $choice=array_shift($choices);
        if(count($selected)>=$decision['min']&&$rank($choice)<0)break;
        $selected[]=$choice['value'];
        if($choice['card']) {
            $id=$choice['card'];$ctx['hand'][$id]=($ctx['hand'][$id]??0)+($discard?-1:1);
            if($id==='mee-002')$ctx['fire']+=$discard?-1:1;
            if(PokeCeruledgeIsFightingEnergy($id))$ctx['fighting']+=$discard?-1:1;
        }
    }
    return implode('&',$selected)?:'-';
}
function PokeCeruledgeChoose(array $view): ?array {
    // Unchosen Pokégear cards return to the deck before a fetched Supporter
    // can be played. Rank that future play using the returned-card count.
    if(str_contains($view['decision']['prompt']??'','Pokégear'))$view['players'][$view['viewer']]['deckCount']+=max(0,count($view['players'][$view['viewer']]['TempZone'])-1);
    $ctx=PokeCeruledgeContext($view);
    if($view['decision'])return ['type'=>'decision','player'=>$view['viewer'],'value'=>PokeCeruledgeDecision($ctx,$view['decision'])];
    $actions=$view['actions'];usort($actions,fn($a,$b)=>PokeCeruledgeActionScore($ctx,$b)<=>PokeCeruledgeActionScore($ctx,$a));
    return $actions[0]??null;
}
