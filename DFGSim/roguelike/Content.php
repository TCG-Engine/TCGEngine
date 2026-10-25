<?php
declare(strict_types=1);
require_once __DIR__ . '/../Custom/CardCatalog.php';

const RG_VERSION = 1;
function RGAnimations(): array {
    $path=__DIR__.'/assets/animations/enemy-animations.json';
    return is_file($path)?json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR):[];
}
function RGEnemies(): array {
    return [
        'fox'=>['name'=>'Huo, the Ember Fox','title'=>'Keeper of the Cinder Gate','color'=>'Red','life'=>18,'style'=>'Fast strikes and echoing follow-ups.','slot'=>0,'armor'=>[]],
        'tortoise'=>['name'=>'Shan, the Jade Tortoise','title'=>'The Mountain That Breathes','color'=>'Green','life'=>20,'style'=>'Patient guard and a break checkpoint at 10 life.','slot'=>1,'armor'=>['jade-shell']],
        'tengu'=>['name'=>'Sora, the Crane Tengu','title'=>'Disciple of the High Wind','color'=>'Yellow','life'=>19,'style'=>'Precise counters and shifting routes.','slot'=>2,'armor'=>[]],
        'dragon'=>['name'=>'Ao, the Azure Dragon','title'=>'Master of the Ninth Current','color'=>'Purple','life'=>30,'style'=>'Heavy commitments. A burst segment from 22 to 18 life must be crossed in one exchange.','slot'=>3,'armor'=>['dragon-scale']],
    ];
}
function RGItems(): array {
    return [
        'jade-vest'=>['name'=>'Jade-Seam Vest','slot'=>'Chest','kind'=>'break','at'=>20,'text'=>'Once each fight, incoming damage stops at 20 life. Reaching the checkpoint consumes it.','price'=>28],
        'low-vest'=>['name'=>'Last-Light Vest','slot'=>'Chest','kind'=>'break','at'=>12,'text'=>'Once each fight, incoming damage stops at 12 life. A late safety checkpoint.','price'=>25],
        'high-vest'=>['name'=>'Dawn-Seam Vest','slot'=>'Chest','kind'=>'break','at'=>28,'text'=>'Once each fight, incoming damage stops at 28 life. Only protects while current life is at or above 28.','price'=>25],
        'azure-mail'=>['name'=>'Azure Lamellar','slot'=>'Chest','kind'=>'burst','at'=>28,'width'=>4,'text'=>'The 28→24 life segment cannot be chipped: cross its full 4 life in one exchange. Broken segments stay broken for that fight.','price'=>32],
        'narrow-mail'=>['name'=>'River Lamellar','slot'=>'Chest','kind'=>'burst','at'=>20,'width'=>3,'text'=>'The 20→17 segment requires 3 damage in one exchange to cross. Damage above the segment still applies.','price'=>26],
        'moth-cloak'=>['name'=>'Moth-Silk Mantle','slot'=>'Chest','kind'=>'shield','amount'=>3,'text'=>'Start each fight with a separate 3-point barrier. It absorbs damage before life and does not regenerate.','price'=>24],
        'echo-wraps'=>['name'=>'Echo Bindings','slot'=>'Arms','kind'=>'confirm','amount'=>1,'text'=>'On a life-damaging hit, mark 1 damage. Hit again next round to confirm it. Marks do not accumulate.','price'=>26],
        'stone-wraps'=>['name'=>'Stone-Knuckle Wraps','slot'=>'Arms','kind'=>'attack','amount'=>1,'text'=>'Your main gains +1 attack when you commit no bonus. Reactions remain legal.','price'=>28],
        'rally-wraps'=>['name'=>'Returning Tide Wraps','slot'=>'Arms','kind'=>'rally','amount'=>2,'text'=>'After taking life damage, up to 2 becomes recoverable next round. Hit next round to recover it after damage, if you survive.','price'=>24],
        'reed-crown'=>['name'=>'Reedleaf Crown','slot'=>'Head','kind'=>'guard_heal','amount'=>1,'text'=>'After an exchange in which you lose no life, recover 1 life if alive, up to your maximum.','price'=>22],
        'iron-crown'=>['name'=>'Iron Brow','slot'=>'Head','kind'=>'block','amount'=>1,'text'=>'Your main has +1 block. Printed values remain unchanged for reads.','price'=>28],
        'pilgrim-boots'=>['name'=>'Pilgrim Greaves','slot'=>'Legs','kind'=>'victory_heal','amount'=>3,'text'=>'After winning a regular fight, recover 3 life. Cannot revive you after a simultaneous knockout.','price'=>20],
        'reed-boots'=>['name'=>'Reed-Step Greaves','slot'=>'Legs','kind'=>'shield','amount'=>2,'text'=>'Start every fight with a 2-point barrier. Stacks with a barrier from the chest slot.','price'=>22],
        // Creature equipment uses the same geometry and damage resolver as player equipment.
        'jade-shell'=>['name'=>'Jade Shell','slot'=>'Chest','kind'=>'break','at'=>10,'text'=>'Once this fight, damage stops at 10 life.','price'=>0],
        'dragon-scale'=>['name'=>'Dragon Scale','slot'=>'Chest','kind'=>'burst','at'=>22,'width'=>4,'text'=>'The 22→18 life segment must be crossed with 4 damage in one exchange.','price'=>0],
    ];
}
function RGCompileCard(array $card): array {
    $main=DFGCompileAdvantage($card['ability']);
    $card['advantage']=$main['mechanic'];$card['special']=$main['special'];
    foreach(['ability','bonus','reaction'] as $mode)$card['effects'][$mode]=DFGCompileEffect($mode==='ability'?$main['text']:$card[$mode]);
    return $card;
}
function DFGCustomCatalog(): array {
    static $cards;if($cards!==null)return $cards;
    $cards=[];
    foreach(json_decode(file_get_contents(__DIR__.'/../data/cards.json'),true,64,JSON_THROW_ON_ERROR)['cards'] as $c)$cards[$c['id']]=RGCompileCard($c);
    foreach(DFG_COLORS as $i=>$color)for($j=0;$j<3;$j++) {
        $id=100+$i*3+$j;
        $c=['id'=>$id,'name'=>$color.' '.['Straight','Guard','Palm'][$j],'color'=>$color,'attack'=>[3,1,2][$j],'block'=>[1,3,2][$j],
            'ability'=>$j===0?"For your damage calculation this round, treat your opponent's block as 1 lower, to a minimum of 0.\nObjective: Deal damage: gain ".$color.' advantage.':('Spend '.$color.' advantage: Gain +1 '.($j===1?'block':'attack').' this round.'),
            'bonus'=>'Gain +1 '.($j===1?'block':'attack').' this round.','reaction'=>'Gain +1 '.($j===0?'attack':'block').' this round.',
            'switches'=>$j===0?[DFG_COLORS[($i+1)%6],DFG_COLORS[($i+2)%6]]:($j===1?[DFG_COLORS[($i+3)%6],DFG_COLORS[($i+4)%6]]:[$color,DFG_COLORS[($i+5)%6]]),
            'artId'=>1+$i*6+$j,'flavor'=>'A foundation technique. Learn stronger moves along the ascent.'];
        $cards[$id]=RGCompileCard($c);
    }
    foreach([['Echo Palm','Orange',3,2,2],['Returning Fang','Red',4,1,2],['Patient Seal','Blue',2,3,3],['Thunder Echo','Purple',5,0,3],['Whispering Step','Yellow',3,2,1],['Resonant Guard','Green',2,4,1]] as $i=>$v) {
        [$name,$color,$a,$b,$echo]=$v;$ci=array_search($color,DFG_COLORS,true);$id=200+$i;
        $cards[$id]=RGCompileCard(['id'=>$id,'name'=>$name,'color'=>$color,'attack'=>$a,'block'=>$b,'ability'=>'Objective: Deal damage: gain '.$color.' advantage.',
            'bonus'=>'Gain +2 attack this round.','reaction'=>'Gain +1 block this round.','switches'=>[DFG_COLORS[($ci+1)%6],DFG_COLORS[($ci+3)%6]],
            'artId'=>1+$ci*6,'confirm'=>$echo,'extraText'=>'On hit, mark '.$echo.' damage. Hit again next round to confirm it; otherwise it disappears.']);
    }
    foreach(array_values(array_keys(RGEnemies())) as $e=>$key)foreach(DFG_COLORS as $i=>$color)for($j=0;$j<3;$j++) {
        $base=$cards[100+$i*3+$j];$id=300+$e*100+$i*3+$j;
        $prefix=['fox'=>['Cinder Fang','Ash Veil','Foxfire Palm'],'tortoise'=>['Stone Palm','Shell Guard','Mountain Turn'],
            'tengu'=>['Feather Cut','Wing Guard','Cloud Step'],'dragon'=>['Dragon Claw','Scale Guard','River Coil']][$key];
        $base['id']=$id;$base['name']=$prefix[$j].' · '.$color;$base['enemy']=$key;unset($base['artId']);$base['artPath']='roguelike/assets/'.$key.'-v2.png';
        if($key==='fox'&&$j===0){$base['block']=0;$base['confirm']=1;$base['extraText']='On hit, mark 1 damage. Hit next round to confirm.';}
        if($key==='tortoise'&&$j===1){$base['attack']=0;$base['block']=3;}
        if($key==='tengu'&&$j===2){$base['attack']=3;$base['block']=1;}
        if($key==='dragon'){$base['attack']+=$j===1?1:2;$base['block']+=1;}
        $cards[$id]=RGCompileCard($base);
    }
    if(function_exists('ADExtraCards'))$cards+=ADExtraCards($cards);
    return $cards;
}
function RGStarterDeck(): array { return range(100,117); }
function RGEnemyDeck(string $key): array { $offset=array_search($key,array_keys(RGEnemies()),true);return range(300+$offset*100,317+$offset*100); }
function RGLootItems(): array { return array_values(array_filter(array_keys(RGItems()),fn($id)=>!in_array($id,['jade-shell','dragon-scale'],true))); }
