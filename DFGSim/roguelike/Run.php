<?php
declare(strict_types=1);
require_once __DIR__.'/Content.php';
require_once __DIR__.'/../Bot/HeuristicBot.php';

function RGEquipment(array $player): array { $items=RGItems();return array_map(fn($id)=>$items[$id],array_values($player['equipment']??[])); }
function DFGPlanContext(array $player): array {
    return ['rogue'=>['life'=>$player['life'],'maxLife'=>DFGMaxLife($player),'equipment'=>$player['equipment']??[],
        'shield'=>$player['shield']??0,'usedBreak'=>$player['usedBreak']??[],'usedBurst'=>$player['usedBurst']??[],
        'incomingConfirm'=>$player['incomingConfirm']??0,'recoverable'=>$player['recoverable']??0]];
}
function DFGModifyCombatStats(array $plans,array $stats): array {
    foreach([0,1] as $seat)foreach(RGEquipment($plans[$seat]['rogue']??[]) as $item){
        if($item['kind']==='block')$stats[$seat]['block']+=$item['amount'];
        if($item['kind']==='attack'&&!$plans[$seat]['bonuses'])$stats[$seat]['attack']+=$item['amount'];
    }
    return $stats;
}
// Pure, public-state calculation used identically by forecasts and authoritative resolution.
function RGIncoming(array $player,int $incoming): array {
    $incoming=max(0,$incoming);$raw=$incoming;$shield=$player['shield']??0;
    $absorbed=min($shield,$incoming);$shield-=$absorbed;$incoming-=$absorbed;
    $life=$player['life'];$break=$player['usedBreak']??[];$burst=$player['usedBurst']??[];
    $items=RGEquipment($player);
    usort($items,fn($a,$b)=>($b['at']??0)<=>($a['at']??0));
    foreach($items as $item) {
        if($incoming<=0)break;
        if($item['kind']==='break') {
            $at=$item['at'];
            if(!in_array($at,$break,true)&&$life>=$at&&$incoming>=$life-$at){$incoming=$life-$at;$break[]=$at;}
        } elseif($item['kind']==='burst') {
            $at=$item['at'];$width=$item['width'];
            if(!in_array($at,$burst,true)&&$life>=$at&&$incoming>=$life-$at){
                $above=$life-$at;$remaining=$incoming-$above;
                if($remaining<$width)$incoming=$above;else $burst[]=$at;
            }
        }
    }
    return ['damage'=>min($life,$incoming),'shield'=>$shield,'usedBreak'=>array_values(array_unique($break)),
        'usedBurst'=>array_values(array_unique($burst)),'prevented'=>$raw-min($life,$incoming)];
}
function DFGModifyRoundDamage(array $plans,array $stats): array {
    foreach([0,1] as $seat){
        $target=$plans[1-$seat]['rogue']??null;if(!$target)continue;
        $raw=$stats[$seat]['damage'];$confirmed=$raw>0?($target['incomingConfirm']??0):0;
        $impact=RGIncoming($target,$raw+$confirmed);
        $stats[$seat]['preArmorDamage']=$raw;$stats[$seat]['confirmed']=$confirmed;
        $stats[$seat]['damage']=$impact['damage'];$stats[$seat]['impact']=$impact;
    }
    return $stats;
}
function DFGAfterDamage(array &$state,array $stats): void {
    foreach([0,1] as $seat){
        $p=&$state['players'][$seat];$other=1-$seat;$impact=$stats[$other]['impact'];
        foreach(['shield','usedBreak','usedBurst'] as $key)$p[$key]=$impact[$key];
        $old=$p['recoverable']??0;$p['recoverable']=0;
        if($impact['prevented']>0)DFGLog($state,($seat===0?'Your':'Enemy').' armor prevents '.$impact['prevented'].' damage.');
        if($stats[$other]['confirmed'])DFGLog($state,($seat===0?'Enemy':'Your').' follow-up confirms '.$stats[$other]['confirmed'].' marked damage before armor.');
        $heal=0;
        foreach(RGEquipment($p) as $item){
            if($item['kind']==='guard_heal'&&$stats[$other]['damage']===0)$heal+=$item['amount'];
            if($item['kind']==='rally'){
                if($stats[$seat]['damage']>0)$heal+=min($old,$item['amount']);
                $p['recoverable']=min($stats[$other]['damage'],$item['amount']);
            }
        }
        if($p['life']>0&&$heal){$actual=min($heal,DFGMaxLife($p)-$p['life']);$p['life']+=$actual;if($actual)DFGLog($state,($seat===0?'Your':'Enemy').' equipment recovers '.$actual.' life.');}
        $next=DFGCatalog()[$state['commitments'][$other]['main']]['confirm']??0;
        foreach(RGEquipment($state['players'][$other]) as $item)if($item['kind']==='confirm')$next+=$item['amount'];
        $p['incomingConfirm']=$stats[$other]['damage']>0?min(4,$next):0;
        if($p['incomingConfirm'])DFGLog($state,($seat===0?'You are':'Enemy is').' marked for '.$p['incomingConfirm'].' next-round confirm damage.');
        unset($p);
    }
}
function RGRandom(int &$seed,int $count): int { $seed=(int)(($seed*48271)%2147483647);return $seed%$count; }
function RGSample(array $pool,int $count,int &$seed): array {
    $out=[];while($pool&&count($out)<$count){$i=RGRandom($seed,count($pool));$out[]=$pool[$i];array_splice($pool,$i,1);}return $out;
}
function RGMap(int &$seed): array {
    $rows=[];$enemies=['fox','tortoise','tengu'];
    for($depth=0;$depth<7;$depth++){
        $nodes=[];$width=$depth===6?1:3;
        for($lane=0;$lane<$width;$lane++){
            $type=$depth===6?'boss':(in_array($depth,[0,2,4],true)?'fight':(['camp','shop','shrine'][($lane+$depth)%3]));
            $enemy=$type==='boss'?'dragon':$enemies[($lane+intdiv($depth,2))%3];
            $nodes[]=['id'=>$depth.'-'.$lane,'depth'=>$depth,'lane'=>$lane,'type'=>$type,'enemy'=>$enemy,
                'title'=>in_array($type,['fight','boss'],true)?RGEnemies()[$enemy]['name']:['camp'=>'Lantern Camp','shop'=>'The Wandering Smith','shrine'=>'Shrine of Unburdening'][$type]];
        }$rows[]=$nodes;
    }return $rows;
}
function RGNewRun(?int $seed=null): array {
    $seed??=random_int(1,2147483646);if($seed<1||$seed>=2147483647)throw new InvalidArgumentException('Invalid seed');
    $mapSeed=$seed;
    return ['version'=>RG_VERSION,'id'=>bin2hex(random_bytes(12)),'revision'=>0,'seed'=>$mapSeed,'initialSeed'=>$seed,
        'phase'=>'starter','life'=>40,'maxLife'=>40,'gold'=>24,'deck'=>RGStarterDeck(),'inventory'=>[],
        'equipment'=>['Head'=>null,'Chest'=>null,'Arms'=>null,'Legs'=>null],'depth'=>-1,'lane'=>1,'map'=>RGMap($mapSeed),
        'visited'=>[],'battle'=>null,'enemy'=>null,'node'=>null,'wins'=>0,'reward'=>null,'shop'=>null,
        'starterArmor'=>['jade-vest','azure-mail','moth-cloak'],'journal'=>['You arrive at the foot of the Cloudbreak Stair.']];
}
function RGAccessible(array $run): array {
    $next=$run['depth']+1;if($next>=count($run['map']))return [];
    return array_values(array_filter($run['map'][$next],fn($n)=>$next===0||$next===6||abs($n['lane']-$run['lane'])<=1));
}
function RGPlayer(array $deck,int $life,int $max,string $color,array $equipment): array {
    $p=['deck'=>$deck,'life'=>$life,'maxLife'=>$max,'color'=>$color,'discarded'=>[],'advantages'=>[],
        'equipment'=>array_filter($equipment),'shield'=>0,'usedBreak'=>[],'usedBurst'=>[],'incomingConfirm'=>0,'recoverable'=>0];
    foreach(RGEquipment($p) as $item)if($item['kind']==='shield')$p['shield']+=$item['amount'];
    return $p;
}
function RGStartFight(array &$run,string $enemy,string $color): void {
    if(!in_array($color,DFG_COLORS,true))throw new InvalidArgumentException('Choose an opening hand');
    $npc=RGEnemies()[$enemy];$state=DFGNewGame($color,$npc['color'],$run['seed'],($run['wins']%2), 'tactical');
    $state['players']=[RGPlayer($run['deck'],$run['life'],40,$color,$run['equipment']),RGPlayer(RGEnemyDeck($enemy),$npc['life'],$npc['life'],$npc['color'],$npc['armor'])];
    $state['matchId']=bin2hex(random_bytes(12));$run['battle']=DFGRunBot($state);$run['enemy']=$enemy;$run['phase']='fight';
    $run['journal'][]='You challenge '.$npc['name'].'.';
}
function RGFinishFight(array &$run): void {
    $b=$run['battle'];if($b['phase']!=='finished')return;
    $run['life']=$b['players'][0]['life'];
    if($b['winner']!==0||$run['life']<=0){$run['phase']='dead';$run['journal'][]='The ascent ends. A new pilgrimage starts with a clean slate.';return;}
    $run['wins']++;$run['gold']+=18;
    if($run['enemy']==='dragon'){$run['phase']='victory';$run['journal'][]='Ao bows. You have crossed the Cloudbreak Stair.';return;}
    foreach(array_filter($run['equipment']) as $id){$item=RGItems()[$id];if($item['kind']==='victory_heal')$run['life']=min(40,$run['life']+$item['amount']);}
    $pool=array_values(array_diff(array_merge(range(1,36),range(200,205)),$run['deck']));
    $run['reward']=['cards'=>RGSample($pool,3,$run['seed']),'armor'=>RGSample(array_values(array_diff(RGLootItems(),$run['inventory'])),3,$run['seed'])];
    $run['phase']='cardReward';$run['journal'][]='Victory. Match burns return; gain 18 jade and choose your rewards.';
}
function RGRemoveOptions(array $run): array {
    $counts=array_fill_keys(DFG_COLORS,0);foreach($run['deck'] as $id)$counts[DFGCatalog()[$id]['color']]++;
    return array_values(array_filter($run['deck'],fn($id)=>$counts[DFGCatalog()[$id]['color']]>1));
}
function RGEquip(array &$run,string $id): void {
    if(!in_array($id,$run['inventory'],true))throw new InvalidArgumentException('You do not own that armor');
    $run['equipment'][RGItems()[$id]['slot']]=$id;
}
function RGApply(array $run,array $action): array {
    $type=$action['type']??'';
    if($type==='starter'){
        if($run['phase']!=='starter'||!in_array($action['item']??'', $run['starterArmor'],true))throw new InvalidArgumentException('Choose a starting chest piece');
        $run['inventory'][]=$action['item'];RGEquip($run,$action['item']);$run['phase']='map';
    }elseif($type==='travel'){
        if($run['phase']!=='map')throw new LogicException('Finish this room first');
        $node=null;foreach(RGAccessible($run) as $n)if($n['id']===($action['node']??null))$node=$n;
        if(!$node)throw new InvalidArgumentException('Choose a connected room on the next row');
        $opening=$action['color']??'Red';if(!in_array($opening,DFG_COLORS,true))throw new InvalidArgumentException('Invalid opening color');
        $run['depth']=$node['depth'];$run['lane']=$node['lane'];$run['visited'][]=$node['id'];$run['node']=$node;
        if(in_array($node['type'],['fight','boss'],true))RGStartFight($run,$node['enemy'],$opening);
        elseif($node['type']==='shop'){$run['shop']=['cards'=>RGSample(array_values(array_diff(array_merge(range(1,36),range(200,205)),$run['deck'])),3,$run['seed']),
            'armor'=>RGSample(array_values(array_diff(RGLootItems(),$run['inventory'])),3,$run['seed']),'bought'=>[]];$run['phase']='shop';}
        else $run['phase']=$node['type'];
    }elseif($type==='battle'){
        if($run['phase']!=='fight'||!is_array($action['move']??null))throw new LogicException('No active fight');
        $run['battle']=DFGRunBot(DFGApply($run['battle'],0,$action['move']));RGFinishFight($run);
    }elseif($type==='cardReward'){
        if($run['phase']!=='cardReward')throw new LogicException('No card reward');
        $id=$action['card']??null;if($id!==null&&!in_array($id,$run['reward']['cards'],true))throw new InvalidArgumentException('Choose an offered technique');
        if($id!==null)$run['deck'][]=$id;$run['phase']='armorReward';
    }elseif($type==='armorReward'){
        if($run['phase']!=='armorReward')throw new LogicException('No armor reward');
        $id=$action['item']??null;if($id!==null&&!in_array($id,$run['reward']['armor'],true))throw new InvalidArgumentException('Choose offered armor');
        if($id!==null){if(!in_array($id,$run['inventory'],true))$run['inventory'][]=$id;RGEquip($run,$id);}
        $run['phase']='map';$run['battle']=null;$run['reward']=null;
    }elseif($type==='equip'){
        if(!in_array($run['phase'],['map','shop','camp','shrine'],true))throw new LogicException('Change armor between fights');
        RGEquip($run,(string)($action['item']??''));
    }elseif($type==='rest'){
        if(!in_array($run['phase'],['camp','shrine'],true))throw new LogicException('No rest here');
        $run['life']=min(40,$run['life']+($run['phase']==='camp'?12:6));$run['phase']='map';
    }elseif($type==='remove'){
        if(!in_array($run['phase'],['camp','shrine','shop'],true))throw new LogicException('No training opportunity');
        if(!in_array($action['card']??null,RGRemoveOptions($run),true))throw new InvalidArgumentException('Keep at least one move in each color');
        if($run['phase']==='shop'){if($run['gold']<16)throw new LogicException('Training costs 16 jade');$run['gold']-=16;}
        $run['deck']=array_values(array_diff($run['deck'],[$action['card']]));if($run['phase']!=='shop')$run['phase']='map';
        $run['journal'][]='You permanently unlearn '.DFGCatalog()[$action['card']]['name'].'.';
    }elseif($type==='buy'){
        if($run['phase']!=='shop')throw new LogicException('No shop here');
        $kind=$action['kind']??'';$id=$action['id']??null;$key=$kind.':'.$id;
        if(!in_array($kind,['cards','armor'],true)||!in_array($id,$run['shop'][$kind],true)||in_array($key,$run['shop']['bought'],true))throw new InvalidArgumentException('Choose an unpurchased offer');
        $price=$kind==='cards'?20:RGItems()[$id]['price'];if($run['gold']<$price)throw new LogicException('Not enough jade');$run['gold']-=$price;
        if($kind==='cards')$run['deck'][]=$id;else{if(!in_array($id,$run['inventory'],true))$run['inventory'][]=$id;RGEquip($run,$id);}
        $run['shop']['bought'][]=$key;
    }elseif($type==='leave'){
        if(!in_array($run['phase'],['camp','shrine','shop'],true))throw new LogicException('Cannot leave now');$run['phase']='map';
    }else throw new InvalidArgumentException('Unknown run action');
    $run['revision']++;return $run;
}
function RGObserve(array $run): array {
    $view=$run;unset($view['seed'],$view['initialSeed']);
    // Future loot is not rerolled by GET; the actual opponent commitment is never exposed.
    $view['battle']=$run['battle']?DFGObserve($run['battle'],0):null;
    if($view['battle'])unset($view['battle']['initialSeed']);
    $view['accessible']=$run['phase']==='map'?RGAccessible($run):[];$view['removeOptions']=RGRemoveOptions($run);
    return $view;
}
