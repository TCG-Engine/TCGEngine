<?php
require_once __DIR__.'/LopunnyBot.php';
require_once __DIR__.'/PrizeLogic.php';
require_once __DIR__.'/RelicanthBot.php';
require_once __DIR__.'/RelicanthDrawBot.php';
require_once __DIR__.'/RelicanthMetaTuneBot.php';
require_once __DIR__.'/RelicanthColressBot.php';
/** Deck policy over a seat observation. Never reads the opposing hand, deck or Prizes. */
function PokeBotEnemyBulwark(array $ctx): bool {
    return (bool)array_filter($ctx['enemy']['Bench'],fn($c)=>$c['id']==='me05-062'&&empty($c['counters']['noAbilities']));
}
/** Boss changes which Bastiodon are on the Bench before damage is evaluated. */
function PokeBotGustContext(array $ctx,array $target): array {
    $ctx['enemy']['Bench']=array_values(array_filter($ctx['enemy']['Bench'],fn($c)=>$c['ref']!==$target['ref']));
    foreach($ctx['enemy']['Active'] as $old)$ctx['enemy']['Bench'][]=$old;
    $ctx['enemy']['Active']=[$target];
    return $ctx;
}
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
/** Desired Supporter access for Last-Ditch Catch, based only on this seat's hand
 * and board. Holding Meowth during setup preserves its bench-from-hand trigger. */
function PokeBotNeedsMeowth(array $ctx): bool {
    if($ctx['own']['supporterUsed']||count($ctx['own']['Bench'])>=5||PokeBotFieldCount($ctx,['me03-062']))return false;
    if(PokeBotOpeningNeedsDraw($ctx))return !isset($ctx['hand']['me02.5-192']);
    if(PokeBotNeedsBlender($ctx))return !isset($ctx['hand']['me02.5-207']);
    return !$ctx['draw']&&count($ctx['own']['Hand'])<5;
}
function PokeBotNeedsDhelmise(array $ctx): bool {
    return PokeBotFieldCount($ctx,['me05-039'])+($ctx['hand']['me05-039']??0)<2;
}
function PokeBotNeedsPoweredBackup(array $ctx): bool {
    return !count(array_filter($ctx['own']['Bench'],fn($c)=>$c['id']==='me05-039'&&count($c['energy'])>0));
}
/** Petrel -> Blender cannot also tutor two missing resources with one fifth
 * discard slot and one Stretcher. Draw before committing the Supporter in
 * these openings; this uses hand/discard information, never hidden deck IDs.
 */
function PokeBotOpeningNeedsDraw(array $ctx): bool {
    if($ctx['own']['turns']!==1||$ctx['seat']===$ctx['view']['firstPlayer']||$ctx['own']['supporterUsed']||$ctx['hide']>=4)return false;
    $pokemon=PokeBotFieldCount($ctx,['me05-039'])+($ctx['hand']['me05-039']??0)>0
        ||isset($ctx['hand']['me03-081'])||isset($ctx['hand']['me02.5-213'])&&count($ctx['own']['Hand'])>=3;
    $energy=$ctx['energy']>0||count(array_filter($ctx['field'],fn($c)=>$c['id']==='me05-039'&&count($c['energy'])>0))>0
        ||isset($ctx['hand']['me03-072'])||isset($ctx['hand']['sv10.5w-082']);
    $missing=(int)!$pokemon+(int)!$energy;
    $recovery=$ctx['hand']['me02.5-196']??0;
    if($missing===1)return $recovery===0;
    if($missing===2)return $recovery<2||!count(array_filter($ctx['own']['Discard'],fn($c)=>in_array($c['id'],['me05-039','mee-005'],true)));
    return false;
}
function PokeBotNeedsEnergyAccess(array $ctx): bool {
    if($ctx['energy']>0)return false;
    if(($ctx['hand']['me05-039']??0)>0)return true;
    foreach($ctx['field'] as $card)if($card['id']==='me05-039'&&!$card['energy'])return true;
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
    if (($ctx['view']['stadium']['ref']??'')===$ref) return $ctx['view']['stadium'];
    foreach ([$ctx['own'],$ctx['enemy']] as $p) foreach (['Hand','Active','Bench'] as $zone) foreach($p[$zone] as $c) if($c['ref']===$ref)return $c;
    return null;
}
function PokeBotDamage(array $ctx,array $attacker,array $target): int {
    $id=$attacker['id'];
    if (!in_array($id,['me05-005','me05-006'],true)&&PokeBotEnemyBulwark($ctx)&&count($attacker['energy'])<=2) return 0;
    if(in_array($id,['me05-005','me05-006'],true)) {
        if(in_array($target['id'],['me05-005','me05-006','me05-034'],true)&&empty($target['counters']['noAbilities']))return 0;
        if(in_array('sv05-161',$target['energy'],true))return 0;
        if(str_contains($target['ref'],'Bench')&&($ctx['view']['stadium']['id']??'')==='me02-085')return 0;
        return $id==='me05-005'?10:($ctx['hide']>=6?40:0);
    }
    $damage=$id==='me05-039'?($ctx['hide']>=4?170:30):($id==='me03-062'?60:80);
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
    foreach($targets as $target){$damage=PokeBotDamage($ctx,$card,$target);$score+=min($target['hp']-$target['damage'],$damage);if($damage>0&&$damage>=$target['hp']-$target['damage'])$score+=150*min($ctx['own']['prizeCount'],PokePrizeValue($target['id']));}
    return $score;
}
function PokeBotTrainerScore(array $ctx,string $id): float {
    $needsEnergy=PokeBotNeedsEnergyAccess($ctx); $needsBasic=PokeBotNeedsDhelmise($ctx);
    $needsSetup=$ctx['hide']<4||$needsBasic||PokeBotNeedsPoweredBackup($ctx);
    $handHns=($ctx['hand']['me05-005']??0)+($ctx['hand']['me05-006']??0)+($ctx['hand']['me05-034']??0);
    $canDiscardCombo=min(2,$handHns)>=4-$ctx['hide']||($ctx['hand']['me02.5-213']??0)>=2&&$handHns>=3;
    $supporter=!$ctx['own']['supporterUsed']&&!($ctx['seat']===$ctx['view']['firstPlayer']&&$ctx['own']['turns']===1);
    $findBlender=PokeBotNeedsBlender($ctx);
    if($findBlender&&PokeBotOpeningNeedsDraw($ctx)){
        if($id==='me02.5-192')return 290;
        if(!isset($ctx['hand']['me02.5-192'])&&in_array($id,['sv08-165','sv10.5b-084'],true))return 285;
    }
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
        'me02.5-213'=>$ctx['hide']<4?($canDiscardCombo?150:85):($needsBasic?150:-20),
        'me03-072'=>$needsEnergy&&!$ctx['own']['energyUsed']?150:-20,
        // Once the discard combo is online, obtain a Dhelmise without spending
        // two extra hand cards. Ultra Ball keeps priority for useful HNS discards.
        'me03-081'=>$needsBasic?($ctx['hide']>=4?155:150):($ctx['hide']<4?40:-20),
        'me02.5-196'=>count(array_filter($ctx['own']['Discard'],fn($c)=>($needsEnergy&&$c['id']==='mee-005')||($needsBasic&&$c['id']==='me05-039')))?150:-20,
        'sv10.5w-082'=>$needsEnergy?150:-20,
        'sv10.5b-084','sv08-165'=>$supporter&&!$ctx['draw']&&!isset($ctx['hand']['me02.5-207'])?75:-20,
        'me02.5-209'=>$supporter&&!$ctx['draw']&&!isset($ctx['hand']['me02.5-207'])?60:-20,
        'me02.5-207'=>!$ctx['draw']?55:($ctx['hide']<4&&!isset($ctx['hand']['sv08-164'])?60:-20),
        'me02.5-192'=>$needsSetup?120:(count($ctx['own']['Hand'])<6?50:5),
        'me04-082'=>$ctx['enemy']['handCount']>3?70:-20,
        'sv09-144'=>isset($ctx['enemy']['Active'][0])&&CardSuffix($ctx['enemy']['Active'][0]['id'])==='ex'&&isset($ctx['own']['Active'][0])&&count($ctx['own']['Active'][0]['energy'])&&in_array($ctx['own']['Active'][0]['id'],['me05-039','me05-034'],true)?90:-20,
        'me02.5-183'=>PokeBotBossScore($ctx),
        default=>-20,
    };
}
function PokeBotBossScore(array $ctx): float {
    $plan=PokeBotBossPlan($ctx);
    // Only a winning gust overrides combo/recovery/draw. Spending the
    // Supporter on an ordinary KO must not strand the next Dhelmise.
    return !$plan?-20:($plan['win']?10000:90);
}
function PokeBotActionScore(array $ctx,array $a): float {
    $card=isset($a['source'])?PokeBotCard($ctx,$a['source']):null;
    $target=isset($a['target'])?PokeBotCard($ctx,$a['target']):null;
    switch($a['type']) {
        case 'setup-active':return $card['id']==='me05-039'?100:($card['id']==='me03-062'?10:80);
        case 'ready':return 0;
        case 'bench':
            if($card['id']==='me03-062')return $ctx['view']['phase']==='SETUP'?-20:(PokeBotNeedsMeowth($ctx)?290:-20);
            // Put the main attacker on the board before committing this turn's
            // attachment. A Dhelmise still in hand cannot compete as an Energy target.
            if($card['id']==='me05-039')return PokeBotFieldCount($ctx,['me05-039'])===0?180:(PokeBotFieldCount($ctx,['me05-039'])<2?140:-10);
            // Do not take HNS fodder out of circulation by benching an optional
            // Poltchageist line before the four-card combo is established.
            if($ctx['hide']<4&&PokeBotFieldCount($ctx,['me05-039'])>0)return -10;
            return PokeBotFieldCount($ctx,['me05-005','me05-006'])===0?34:-10;
        case 'evolve':return 40;
        case 'trainer':return PokeBotTrainerScore($ctx,$card['id']);
        case 'attach':
            $active=$ctx['own']['Active'][0]??null;
            if(PokeBotEnemyBulwark($ctx)&&$target['id']==='me05-039'&&count($target['energy'])<3)
                return $target['ref']===($active['ref']??'')?190:155;
            $priority=PokeBotAttackValue($ctx,$target,false);
            if($target['id']==='me05-039'&&!$target['energy'])return 160+$priority/30+($target['ref']===($active['ref']??'')?10:0)+($card['id']==='me03-088'&&PokeBotNeedsDhelmise($ctx)?20:0);
            // Poltchageist retreats for free. Power it only after all productive
            // setup/search/draw actions have been exhausted and no Dhelmise needs
            // this attachment. Its attack is the fallback, not the opening plan.
            if($target['id']==='me05-005'){
                if(PokeBotFieldCount($ctx,['me05-039'])===0&&($ctx['hand']['me05-039']??0)===0)return !$target['energy']&&$target['ref']===($active['ref']??'')?2:-10;
                return -10;
            }
            if(count($target['energy'])===0)return 45+$priority/30+($target['ref']===($active['ref']??'')?5:0)+($card['id']==='me03-088'&&$target['id']==='me05-039'&&PokeBotFieldCount($ctx,['me05-039'])<2?10:0);
            // Dhelmise may need three units to retreat into an online Sinistcha.
            if($active&&$target['ref']===$active['ref']&&$target['id']==='me05-039'&&count($target['energy'])<3)foreach($ctx['own']['Bench'] as $bench)if(PokeBotAttackValue($ctx,$bench)>PokeBotAttackValue($ctx,$active)+40)return 35;
            return -10;
        case 'retreat':
            $active=$ctx['own']['Active'][0];$gain=PokeBotAttackValue($ctx,$target)-PokeBotAttackValue($ctx,$active);
            if($active['id']==='me05-005'&&$target['id']==='me05-039'&&PokeBotAttackValue($ctx,$target)>0)return 95;
            return $gain>40?42+$gain/20-count($a['payment'])*3:-20;
        case 'attack':
            if(isset($ctx['enemy']['Active'][0])&&PokeBotPrizeTarget($ctx,$card,$ctx['enemy']['Active'][0])['win'])return 10000;
            return 10+PokeBotAttackValue($ctx,$card)/20;
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
    if($id==='me03-062')return PokeBotNeedsMeowth($ctx)&&!isset($ctx['hand'][$id])?260:-20;
    if($id==='me02.5-192'&&PokeBotOpeningNeedsDraw($ctx))return 310;
    // Recovery can offer both Energy and Pokémon. Find the first attacker
    // before spare Energy or a second copy, then attach to that attacker.
    if($id==='me05-039'&&PokeBotFieldCount($ctx,['me05-039'])===0&&!isset($ctx['hand'][$id]))return 300;
    // The first Ultra Ball can search the HNS card needed to pay the next
    // Ultra Ball. Complete a reachable four-card combo before searching spare
    // attackers, while always reserving the first Dhelmise above this branch.
    $handHns=($ctx['hand']['me05-005']??0)+($ctx['hand']['me05-006']??0)+($ctx['hand']['me05-034']??0);
    if(in_array($id,['me05-005','me05-006','me05-034'],true)&&$ctx['hide']>=2&&$ctx['hide']<4
        &&str_contains($ctx['view']['decision']['prompt']??'', 'Ultra Ball: search')
        &&isset($ctx['hand']['me02.5-213'])&&min(2,$handHns+1)>=4-$ctx['hide'])return 240;
    // With the current attacker powered, find its replacement before piling
    // Energy into hand. Once the replacement exists, search its attachment.
    if($id==='me05-039'&&PokeBotNeedsDhelmise($ctx)&&!PokeBotNeedsEnergyAccess($ctx))return 200;
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
        if(str_contains($prompt,'Boss')){$target=PokeBotCard($ctx,$c['value']);$active=$ctx['own']['Active'][0];$baseline=PokeBotPrizeTarget($ctx,$active,$ctx['enemy']['Active'][0]);return PokeBotBossTargetPlan($ctx,$active,$target,$baseline)['value']??0;}
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
    if (($view['players'][$view['viewer']]['deckKey']??'')==='relicanth-v3-meta-tune') return PokeRelicanthMetaTuneChoose($view);
    if (in_array($view['players'][$view['viewer']]['deckKey']??'', ['relicanth-v4-colress','relicanth-v5-bastiodon','relicanth-v6-explorers-guidance','relicanth-v7-lanas-aid'], true)) return PokeRelicanthColressChoose($view);
    if (($view['players'][$view['viewer']]['deckKey']??'')==='relicanth-v2-draw') return PokeRelicanthDrawChoose($view);
    if (($view['players'][$view['viewer']]['deckKey']??'')==='relicanth-fossils') return PokeRelicanthChoose($view);
    if (($view['players'][$view['viewer']]['deckKey']??'')==='brisbane-lopunny') return PokeLopunnyChoose($view);
    $ctx=PokeBotContext($view);$d=$view['decision'];
    if($d)return ['type'=>'decision','player'=>$ctx['seat'],'value'=>PokeBotDecision($ctx,$d)];
    $actions=$view['actions'];if(!$actions)return null;
    usort($actions,fn($a,$b)=>PokeBotActionScore($ctx,$b)<=>PokeBotActionScore($ctx,$a));
    $best=$actions[0];
    // Burn a legal Pad before a planned shuffle-and-draw, even if no new
    // Pokemon is needed. Search choices still use the normal exposed candidates.
    // Applying this only when Lillie would be next preserves Blender, Petrel,
    // useful Ultra Ball discards, attachments and winning attack priorities.
    if($best['type']==='trainer'&&PokeBotCard($ctx,$best['source'])['id']==='me02.5-192'){
        foreach($actions as $action)if($action['type']==='trainer'&&PokeBotCard($ctx,$action['source'])['id']==='me03-081')return $action;
    }
    return $best;
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
