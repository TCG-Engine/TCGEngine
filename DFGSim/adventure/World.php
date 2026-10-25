<?php
declare(strict_types=1);
require_once __DIR__.'/../roguelike/Run.php';
const AD_VERSION=1;
const AD_STARTER_REVISION=2;
function ADStarterDeck(): array { return range(1,36); }
function ADBaselineCards(): array { return [1,6,8,12,13,17,20,24,25,26,33,35]; }
function ADSignatureSpecs(): array {
    // PvP reference, printed attack/block, and the strategic cost of the variation.
    return [
        'iddin'=>[[2,5,2,'One less attack for one more block.'],[11,2,3,'A safer low-attack read with a smaller strike.'],[14,3,3,'Conservation rewards a clean exchange; gives up Sidestep’s high-attack defense and stronger block burn.'],[22,3,4,'More pressure, less raw protection.'],[29,2,4,'A defensive mirror read with less base damage.'],[34,2,4,'A firmer counterstroke with less guard.']],
        'imdugud'=>[[4,3,2,'A sharper guard read with less protection.'],[10,3,3,'Braces against commitment at the cost of damage.'],[15,2,3,'Reads a bonus commitment and turns a successful guard into tempo; gives up Bait and Slip’s relocation and stronger attack.'],[21,1,3,'Less damage in exchange for a safer response to burns.'],[27,3,4,'More base pressure, less guard.'],[36,3,3,'A safer advantage read with less punishment.']],
        'huwawa'=>[[3,1,3,'A little more attack makes the retaliation harder to protect.'],[7,3,2,'A steadier grip with less piercing pressure.'],[16,0,5,'Gives up all base attack for guard; still relies on reading a strong printed defense.'],[19,1,5,'A little base pressure and on-hit recovery; less block and weaker burns than Rooted Guard.'],[30,3,3,'A patient counter with one less attack.'],[34,0,6,'A deeper guard whose zero base attack makes momentum and advantage harder to earn.']],
        'bull-of-heaven'=>[[2,7,0,'More attack leaves no printed defense.'],[9,7,0,'A larger committed tackle with no printed defense.'],[18,4,1,'Gives up attack for a little protection before the read.'],[23,6,1,'A stronger recovering strike with less block.'],[28,5,1,'A bigger guard-breaking read with more exposure.'],[31,6,0,'One less attack than Haymaker buys a Purple setup payoff; missing still exposes the next round.']]
    ];
}

function ADChampions(): array {
    return [
        'iddin'=>['name'=>'Iddin, Reed-Court Disciple','title'=>'Lesser champion · The First Lesson','life'=>20,'color'=>'Yellow','art'=>'assets/fighter-red-transparent.webp','rewardOffset'=>0,'armor'=>[],'difficulty'=>'casual'],
        'imdugud'=>['name'=>'Imdugud, Terrace Champion','title'=>'Lesser champion · A Bird’s-Eye Assessment','life'=>26,'color'=>'Yellow','art'=>'roguelike/assets/sumerian/imdugud-v1.png','rewardOffset'=>1,'armor'=>[],'difficulty'=>'tactical'],
        'huwawa'=>['name'=>'Huwawa, Cedar Guardian','title'=>'Lesser champion · Respect the Cedar','life'=>28,'color'=>'Green','art'=>'roguelike/assets/sumerian/huwawa-v1.png','rewardOffset'=>2,'armor'=>['jade-shell'],'difficulty'=>'tactical'],
        'bull-of-heaven'=>['name'=>'The Bull of Heaven','title'=>'Greater champion · The Proper Challenge','life'=>40,'color'=>'Purple','art'=>'roguelike/assets/sumerian/bull-of-heaven-v1.png','rewardOffset'=>3,'armor'=>['dragon-scale'],'difficulty'=>'tactical'],
    ];
}
function ADTechniqueArtPaths(): array {
    $slugs=[
        1000=>'canal-jab',1001=>'ferrymans-feint',1002=>'reed-step',1003=>'bank-guard',1004=>'still-water-palm',1005=>'sluice-reversal',
        1010=>'lions-cry',1011=>'storm-talon',1012=>'sky-step',1013=>'wing-shelter',1014=>'rainward-guard',1015=>'thunder-descent',
        1020=>'cedar-hand',1021=>'rooted-grip',1022=>'forest-turn',1023=>'sevenfold-guard',1024=>'breath-of-cedar',1025=>'guardians-rebuke',
        1030=>'heavens-horn',1031=>'gatebreaker',1032=>'starbound-step',1033=>'sacred-mantle',1034=>'skyward-guard',1035=>'horizon-charge'
    ];
    $paths=[];foreach($slugs as $id=>$slug)$paths[$id]='adventure/assets/techniques/'.$id.'-'.$slug.'-v1.webp';return $paths;
}
function ADAnimations(): array {
    $all=RGAnimations();$human=json_decode(file_get_contents(__DIR__.'/../assets/fighter-reactions.json'),true,32,JSON_THROW_ON_ERROR);
    $all['iddin']=$human['red']+['idle'=>['src'=>'assets/fighter-red-idle.webp','durationMs'=>3361],'still'=>'assets/fighter-red-transparent.webp','wideCanvas'=>false];return $all;
}
function ADExtraCards(array $base): array {
    $names=[
        ['Canal Jab','Ferryman’s Feint','Reed Step','Bank Guard','Still-Water Palm','Sluice Reversal'],
        ['Lion’s Cry','Storm Talon','Sky Step','Wing Shelter','Rainward Guard','Thunder Descent'],
        ['Cedar Hand','Rooted Grip','Forest Turn','Sevenfold Guard','Breath of Cedar','Guardian’s Rebuke'],
        ['Heaven’s Horn','Gatebreaker','Starbound Step','Sacred Mantle','Skyward Guard','Horizon Charge']
    ];$out=[];$specs=ADSignatureSpecs();
    foreach(ADChampions() as $key=>$npc){
        $e=$npc['rewardOffset'];
        foreach(DFG_COLORS as $i=>$color){
            [$reference,$attack,$block,$tradeoff]=$specs[$key][$i];
            $c=$base[$reference];$c['id']=1000+$e*10+$i;$c['name']=$names[$e][$i];$c['attack']=$attack;$c['block']=$block;$c['champion']=$key;$c['artPath']=ADTechniqueArtPaths()[$c['id']];unset($c['artId']);
            $c['comparisonId']=$reference;$c['tradeoff']=$tradeoff;$c['headline']=($key==='iddin'||$key==='imdugud')?$i===2:($key==='huwawa'?$i===3:$i===5);
            if($c['headline']){
                if($key==='iddin'){
                    $c['comparisonId']=13;$c['ability']="If you committed no bonus burns this round, gain +1 attack this round.\nObjective: Deal damage without a bonus or reaction burn: gain Orange advantage.";
                    $c['bonus']='Gain +2 block this round.';$c['reaction']='Gain +1 block this round.';$c['switches']=['Orange','Blue','Green'];
                }elseif($key==='imdugud'){
                    $c['ability']="If your opponent committed at least one bonus burn this round, gain +3 block this round.\nIf you take no damage, gain +1 attack next round.";
                    $c['bonus']='Gain +2 block this round.';$c['reaction']='Gain +1 block this round.';$c['switches']=['Orange','Blue','Purple'];
                }elseif($key==='huwawa'){
                    $c['ability']="If your opponent's main card has a printed attack value of 5 or more, gain +2 attack this round.\nIf you deal damage, recover 1 life after damage. This cannot prevent a knockout.\nObjective: Take no damage against printed attack 5+: gain Green advantage.";
                    $c['bonus']='Gain +2 block this round.';$c['reaction']='Gain +1 block this round.';$c['switches']=['Purple','Blue'];
                }else{
                    $c['ability']="If you deal no combat damage, you have -2 block next round, to a minimum of 0.\nObjective: Deal 4+ damage without a bonus burn: gain Purple advantage.\nSpend Purple advantage: If you deal damage, gain +2 attack next round.";
                    $c['bonus']='Gain +3 attack this round. Your opponent gains +1 attack this round.';$c['reaction']='';$c['switches']=['Orange','Purple'];
                }
            }
            $c['flavor']='A signature technique learned from '.$npc['name'].'.';$out[$c['id']]=RGCompileCard($c);
            // Keep the original 18-card NPC IDs available to finish already-saved fights.
            for($j=0;$j<3;$j++){
                $move=$j===0?$base[1+$i*6+$e]:$base[100+$i*3+$j];$move['id']=2000+$e*100+$i*3+$j;$move['enemy']=$key;$move['legacy']=true;$move['artPath']=$npc['art'];unset($move['artId']);
                if($j)$move['name']=explode(',',$npc['name'])[0].' · '.$color.' '.($j===1?'Guard':'Palm');
                $out[$move['id']]=$move;
            }
        }
    }return $out;
}
function ADRewards(string $enemy): array { $e=ADChampions()[$enemy]['rewardOffset'];return range(1000+$e*10,1005+$e*10); }
function ADEnemyDeck(string $enemy): array {
    $deck=ADStarterDeck();$e=ADChampions()[$enemy]['rewardOffset'];
    foreach(ADSignatureSpecs()[$enemy] as $i=>$spec){$index=array_search($spec[0],$deck,true);$deck[$index]=1000+$e*10+$i;}
    return array_values($deck);
}
function ADPublicEnemies(): array {
    $enemies=ADChampions();foreach($enemies as $key=>&$npc){$npc['moves']=ADEnemyDeck($key);$npc['signature']=ADRewards($key)[$key==='iddin'||$key==='imdugud'?2:($key==='huwawa'?3:5)];}unset($npc);return $enemies;
}
function ADPublicCard(array $c): array {
    $c=DFGCardPublic($c);$c['baseline']=in_array($c['id'],ADBaselineCards(),true);
    $c['roleTitle']=$c['baseline']?'Baseline · shared foundation':($c['id']<=36?'PvP repertoire':(!empty($c['champion'])?ADChampions()[$c['champion']]['name'].(!empty($c['headline'])?' · signature':' · variation'):''));return $c;
}
function ADUpgradeStarter(array $j): array {
    if(($j['starterRevision']??0)>=AD_STARTER_REVISION||$j['phase']==='fight')return $j;
    $old=range(100,117);$pureStarter=!array_diff($j['deck'],$old);
    $j['collection']=array_values(array_unique([...ADStarterDeck(),...array_diff($j['collection'],$old)]));
    if($pureStarter)$j['deck']=ADStarterDeck();
    else{
        // Preserve earned signature choices; translate old basics and fill remaining color slots.
        $mapped=[1,3,6,7,8,12,13,14,17,19,20,24,25,26,27,31,33,35];
        $deck=array_values(array_unique(array_map(fn($id)=>$id>=100&&$id<=117?$mapped[$id-100]:$id,$j['deck'])));
        foreach(DFG_COLORS as $color){$count=count(array_filter($deck,fn($id)=>DFGCatalog()[$id]['color']===$color));foreach(ADStarterDeck() as $id)if($count<6&&DFGCatalog()[$id]['color']===$color&&!in_array($id,$deck,true)){$deck[]=$id;$count++;}}
        $j['deck']=$deck;
    }
    $j['starterRevision']=AD_STARTER_REVISION;++$j['revision'];ADLog($j,'Your starting repertoire is now the full 36-card PvP set. Earned techniques, equipment, and exploration are preserved.');return $j;
}
function ADItems(): array {
    $items=RGItems();
    foreach(['moth-cloak'=>['Traveler’s Linen','A simple sanctuary cloak. Start each fight with a 3-point barrier.'],
        'stone-wraps'=>['Reed-Court Handwraps','Your main gains +1 attack when you commit no bonus.'],
        'reed-boots'=>['Reed-Woven Sandals','Start each fight with a 2-point barrier.'],
        'jade-vest'=>['Cedar-Knot Vest','Once each fight, incoming damage stops at 20 life.'],
        'reed-crown'=>['Terrace Laurel','After an exchange in which you lose no life, recover 1 life if alive.'],
        'azure-mail'=>['Mantle of the Horizon','The 28→24 life segment requires its full 4 damage in one exchange.'],
        'jade-shell'=>['Cedar Aura','Once this fight, incoming damage stops at 10 life.'],
        'dragon-scale'=>['Mantle of Heaven','The 22→18 life segment must be crossed with its full 4 damage in one exchange.']] as $id=>$v){$items[$id]['name']=$v[0];$items[$id]['text']=$v[1];}
    return $items;
}
function ADMap(string $zone='courtyard'): array {
    if($zone==='sanctuary')return ['width'=>24,'height'=>16,'tile'=>64,'name'=>'Sanctuary of First Light','region'=>'A quiet place to begin again','art'=>'adventure/assets/sanctuary-interior-v1.png','blocked'=>[[0,0,24,2],[0,2,1,14],[23,2,1,14],[0,13,11,3],[13,13,11,3],[1,2,5,3],[18,2,5,4],[9,2,6,2],[1,6,2,3],[21,6,2,3]]];
    return ['width'=>24,'height'=>16,'tile'=>64,'name'=>'The First Courtyard','region'=>'Sumer · Where the rivers begin',
        'art'=>'adventure/assets/sumerian-overworld-v1.png',
        'blocked'=>[[0,0,9,3],[0,3,2,2],[7,3,2,2],[9,0,6,4],[16,0,8,7],[14,4,2,5],[14,10,4,1],[20,10,4,1],[0,6,7,3],[0,10,4,4],[7,11,3,3],[12,11,5,3],[16,14,1,2],[7,14,3,2],[12,14,3,2],[20,12,4,4]]];
}
function ADEntities(string $zone='courtyard'): array {
    if($zone==='sanctuary')return [
        'keeper'=>['name'=>'Aya, Sanctuary Keeper','x'=>11,'y'=>6,'kind'=>'npc','portrait'=>'adventure/assets/elder-overworld-v1.png'],
        'cushion'=>['name'=>'A very forgiving cushion','x'=>3,'y'=>5,'kind'=>'object','icon'=>'☀'],
        'exit'=>['name'=>'Door to the courtyard','x'=>11,'y'=>14,'kind'=>'door','icon'=>'↗']
    ];
    return [
        'mailbox'=>['name'=>'Your stone mailbox','x'=>3,'y'=>13,'kind'=>'object','icon'=>'✉'],
        'sanctuary'=>['name'=>'Sanctuary of First Light','x'=>2,'y'=>13,'kind'=>'sanctuary','icon'=>'☀'],
        'iddin'=>['name'=>'Iddin','x'=>5,'y'=>9,'kind'=>'champion','portrait'=>'assets/fighter-red-transparent.webp'],
        'scribe'=>['name'=>'Nidaba, Tablet Reader','x'=>7,'y'=>9,'kind'=>'npc','portrait'=>'adventure/assets/elder-overworld-v1.png'],
        'gardener'=>['name'=>'Amat, Canal Keeper','x'=>9,'y'=>10,'kind'=>'npc','portrait'=>'adventure/assets/elder-overworld-v1.png'],
        'stopper'=>['name'=>'Loose clay stopper','x'=>8,'y'=>9,'kind'=>'pickup','icon'=>'◆'],
        'reed'=>['name'=>'Reed binding','x'=>12,'y'=>10,'kind'=>'pickup','icon'=>'≋'],
        'sluice'=>['name'=>'Garden sluice','x'=>10,'y'=>10,'kind'=>'object','icon'=>'≈'],
        'huwawa'=>['name'=>'Huwawa','x'=>4,'y'=>4,'kind'=>'champion','portrait'=>'roguelike/assets/sumerian/huwawa-v1.png'],
        'imdugud'=>['name'=>'Imdugud','x'=>11,'y'=>5,'kind'=>'champion','portrait'=>'roguelike/assets/sumerian/imdugud-v1.png'],
        'attendant'=>['name'=>'Puzur, Temple Attendant','x'=>17,'y'=>8,'kind'=>'npc','portrait'=>'adventure/assets/elder-overworld-v1.png'],
        'bull-of-heaven'=>['name'=>'Bull of Heaven','x'=>20,'y'=>8,'kind'=>'champion','portrait'=>'roguelike/assets/sumerian/bull-of-heaven-v1.png'],
        'gate'=>['name'=>'Road to the wider world','x'=>19,'y'=>11,'kind'=>'gate','icon'=>'➜']
    ];
}
function ADVisible(array $j,string $id): bool {
    return !($id==='stopper'&&isset($j['flags']['stopperFound']))&&!($id==='reed'&&isset($j['flags']['reedFound']));
}
function ADWalkable(int $x,int $y,string $zone='courtyard'): bool {
    $m=ADMap($zone);if($x<0||$y<0||$x>=$m['width']||$y>=$m['height'])return false;
    foreach($m['blocked'] as [$bx,$by,$w,$h])if($x>=$bx&&$x<$bx+$w&&$y>=$by&&$y<$by+$h)return false;
    foreach(ADEntities($zone) as $e)if(in_array($e['kind'],['npc','champion'],true)&&$x===$e['x']&&$y===$e['y'])return false;
    return true;
}
function ADNear(array $j,string $id): bool { $e=ADEntities($j['zone']??'courtyard')[$id]??null;return $e&&abs($j['position']['x']-$e['x'])+abs($j['position']['y']-$e['y'])<=1; }
function ADNewJourney(): array {
    return ['version'=>AD_VERSION,'starterRevision'=>AD_STARTER_REVISION,'id'=>bin2hex(random_bytes(12)),'revision'=>0,'phase'=>'world','zone'=>'courtyard','position'=>['x'=>5,'y'=>14],
        'deck'=>ADStarterDeck(),'collection'=>ADStarterDeck(),'inventory'=>['moth-cloak'],'equipment'=>['Head'=>null,'Chest'=>'moth-cloak','Arms'=>null,'Legs'=>null],
        'flags'=>[],'defeated'=>[],'visited'=>[],'battle'=>null,'enemy'=>null,'lastResult'=>null,'dialog'=>null,
        'journal'=>['A rather heavy envelope has arrived in your stone mailbox.']];
}
function ADLog(array &$j,string $text): void { $j['journal'][]=$text;$j['journal']=array_slice($j['journal'],-80); }
function ADBullReady(array $j): bool { return !empty($j['flags']['gardenWitness'])&&in_array('huwawa',$j['defeated'],true)&&in_array('imdugud',$j['defeated'],true); }
function ADCanChallenge(array $j,string $id): bool {
    return !empty($j['flags']['invited'])&&isset(ADChampions()[$id])&&($id!=='huwawa'||!empty($j['flags']['canalRestored']))&&($id!=='bull-of-heaven'||(ADBullReady($j)&&!empty($j['flags']['witnessed'])));
}
function ADDialog(array $j,string $id,string $text,array $options=[]): array {
    $e=ADEntities($j['zone']??'courtyard')[$id];return ['id'=>$id,'title'=>$e['name'],'portrait'=>$e['portrait']??null,'text'=>$text,'options'=>$options];
}
function ADInteract(array &$j,string $id): void {
    if(!isset(ADEntities($j['zone']??'courtyard')[$id])||!ADNear($j,$id)||!ADVisible($j,$id))throw new LogicException('Walk beside that person or object first.');
    $j['visited']=array_values(array_unique([...$j['visited'],$id]));$f=&$j['flags'];$options=[];
    if($id==='mailbox'){
        if(empty($f['invited'])){$f['invited']=true;ADLog($j,'Read El’s invitation. Find Nidaba at the market.');}
        $text="EL SEEKS A HEAVENLY CHAMPION.\nCourage welcome. Experience negotiable. Please bring your own sandals.\n\nOn the reverse: ‘Begin at the First Courtyard. Ask questions. Help somebody. — Tournament Administration’\n\nSomeone has added, in a much softer hand: ‘And eat a good breakfast. — El’";
    }elseif($id==='sanctuary'){$text='A sun is carved above the sanctuary door. Inside, you can hear a kettle attempting to sing. There is always a cushion waiting for a tired challenger.';$options[]=['label'=>'Enter the sanctuary','action'=>'door'];}
    elseif($id==='exit'){$text='Warm courtyard air drifts through the doorway. Your journey is waiting outside.';$options[]=['label'=>'Step into the courtyard','action'=>'door'];}
    elseif($id==='keeper')$text='Aya sets a cup beside you. “No one becomes a champion without getting up again. Every challenge begins at full health. Your techniques, equipment, and good deeds are safe here.”\n\n“Someone slipped a divine invitation into your stone mailbox. I hope El remembered the postage this time.”';
    elseif($id==='cushion')$text='A very forgiving cushion. It has supported several generations of ambitious challengers. Nobody keeps score here.\n\nYour next bout always begins at full health; there is no need to spend supplies or wait to recover.';
    elseif(empty($f['invited']))$text='“There’s a tablet rattling in your mailbox, challenger. Best read it before you volunteer for anything divine.”';
    elseif($id==='scribe'){
        if(empty($f['scribe'])){ADLog($j,'Nidaba explained the three witnesses: a watered garden, the cedar’s respect, and the sky’s respect.');$f['scribe']=true;}
        $text='“El’s new champion? Excellent. I have already made you a very small file.”\n\n“The Bull accepts a properly witnessed challenge. Restore the canal garden. Earn Huwawa’s Cedar Seal and Imdugud’s Sky Seal. Then ask Puzur at the temple to witness your challenge. A champion must care for a place before claiming it.”\n\n“Defeat a lesser champion to learn their signature techniques. Arrange them freely between fights: at most six in each color.”';
    }elseif($id==='gardener'){
        $f['gardener']=true;
        if(!empty($f['canalRestored'])){
            if(empty($f['gardenWitness'])){$f['gardenWitness']=true;$j['inventory'][]='reed-boots';ADLog($j,'Amat gave you the Garden Witness and Reed-Woven Sandals.');}
            $text='“Listen. The garden is drinking again.” Amat gives you the Garden Witness and a pair of Reed-Woven Sandals.\n\n“Let the temple attendant know someone remembered the vegetables. I suspect the gods forget them rather often.”';
        }else $text='“Everyone wants to move mountains. I would settle for moving this water.”\n\n“The sluice needs a clay stopper and a reed binding. A spare stopper is beside the market; reeds have washed up on the canal’s north bank. Bring both to the sluice by the bridge, then come back to me.”';
    }elseif(in_array($id,['stopper','reed'],true)){
        if(empty($f['gardener']))$text='A useful-looking piece of canal equipment. Perhaps the canal keeper knows where it belongs.';
        else{$key=$id==='stopper'?'stopperFound':'reedFound';$f[$key]=true;$text=$id==='stopper'?'You lift the clay stopper. Someone has scratched “NOT A SOUP BOWL” on its underside.':'You gather a sturdy reed binding. A frog watches your technique with professional interest.';ADLog($j,$id==='stopper'?'Found the clay stopper beside the market.':'Found a reed binding on the canal’s north bank.');}
    }elseif($id==='sluice'){
        if(!empty($f['canalRestored']))$text='Water gurgles through the repaired sluice. The garden is green again. Tell Amat the good news.';
        elseif(!empty($f['gardener'])&&!empty($f['stopperFound'])&&!empty($f['reedFound'])){$f['canalRestored']=true;ADLog($j,'Repaired the garden sluice. Return to Amat, then visit the cedar guardian.');$text='The stopper fits. The reed binding holds. A cool stream curls toward the garden.\n\nFrom the cedar grove, a deep voice says, “At last. Someone fixed something.”\n\nReturn to Amat for the Garden Witness. Huwawa will now accept your challenge.';}
        else $text='The sluice is missing a clay stopper and a reed binding. Ask the canal keeper beside the bridge what happened.';
    }elseif($id==='attendant'){
        $f['attendant']=true;
        if(ADBullReady($j)){$f['witnessed']=true;$text='Puzur studies your three seals. “The garden drinks. The cedar speaks for you. The sky has heard your name.”\n\nHe stamps your tablet with tremendous ceremony. “The secret was never sharper horns. It was a proper challenge. The Bull will face you now.”';ADLog($j,'Puzur witnessed your challenge. The Bull of Heaven will now fight.');}
        else{$missing=[];if(empty($f['gardenWitness']))$missing[]='the Garden Witness from Amat';if(!in_array('huwawa',$j['defeated'],true))$missing[]='Huwawa’s Cedar Seal';if(!in_array('imdugud',$j['defeated'],true))$missing[]='Imdugud’s Sky Seal';$text='“The Bull will not accept an unwitnessed duel. We are short of replacement paving.”\n\nYou still need: '.implode('; ',$missing).'.\n\n“Bring all three. I shall make the paperwork heroic.”';}
    }elseif($id==='gate'){
        if(in_array('bull-of-heaven',$j['defeated'],true)){$text='Beyond the courtyard, roads fan out toward other mythic lands. Your first regional seal opens the way.\n\nThis first playable chapter is complete. Future regions will be freely selectable here. Complete every region to enter El’s divine tournament.';$options[]=['label'=>'View the wider world','action'=>'world'];}
        else $text='A bronze notice reads: “REGIONAL SEAL REQUIRED. Sandals inspected at traveller’s own risk.”\n\nDefeat the Bull of Heaven to open the road beyond the First Courtyard.';
    }else{
        $won=in_array($id,$j['defeated'],true);
        $text=match($id){
            'iddin'=>$won?'“Ah, my instructor returns. One more lesson? I have almost recovered my dignity.”':'“The gods have started recruiting amateurs. At last, my competition. A friendly bout? I promise to make my best mistakes.”',
            'imdugud'=>$won?'“Your footing has earned my attention. Another exchange? I shall endeavor to deserve yours.”':'“From above, every challenger looks small. Show me why I should land.”',
            'huwawa'=>$won?'“I yield. The trees need not hear about it. Will you test my guard again, teacher?”':(!empty($f['canalRestored'])?'“Everyone asks what they can take from a forest. You repaired a garden. Better beginning. Let us test your roots.”':'“You want the respect of a forest? Begin with the thirsty garden by the canal. I will not raise a hand while it withers.”'),
            default=>$won?'“Another lesson from my better? Gladly. This time I shall attempt to leave the paving intact.”':(ADCanChallenge($j,$id)?'“The garden drinks. The cedar stands. The sky has heard your name. Very well—now we may discover how firmly you stand.”':'“Little challenger, I could empty that canal with a drink. Until its garden is tended and its guardians speak for you, I will not turn this courtyard into your lesson. Ask my attendant for a properly witnessed challenge.”')
        };
        if(ADCanChallenge($j,$id))$options[]=['label'=>$won?'Spar for practice':'Challenge','action'=>'challenge'];
        $options[]=['label'=>'Study their moves','action'=>'moves'];
    }
    $j['dialog']=ADDialog($j,$id,str_replace('\\n',"\n",$text),$options);
}
function ADStartFight(array &$j,string $enemy,string $color,?int $seed=null): void {
    $j=ADUpgradeStarter($j);
    if(!ADNear($j,$enemy)||!ADCanChallenge($j,$enemy))throw new LogicException('This champion has not accepted your challenge.');
    if(!in_array($color,DFG_COLORS,true))throw new InvalidArgumentException('Choose an opening color.');
    $npc=ADChampions()[$enemy];$e=$npc['rewardOffset'];$state=DFGNewGame($color,$npc['color'],$seed??random_int(1,2147483646),0,$npc['difficulty']);
    $state['players']=[RGPlayer($j['deck'],40,40,$color,$j['equipment']),RGPlayer(ADEnemyDeck($enemy),$npc['life'],$npc['life'],$npc['color'],$npc['armor'])];
    $state['matchId']=bin2hex(random_bytes(12));$j['battle']=DFGRunBot($state);$j['enemy']=$enemy;$j['phase']='fight';$j['dialog']=null;$j['lastResult']=null;
}
function ADFinishFight(array &$j): void {
    $b=$j['battle'];if($b['phase']!=='finished'||$j['phase']!=='fight')return;
    $enemy=$j['enemy'];$win=$b['winner']===0&&$b['players'][0]['life']>0;$first=$win&&!in_array($enemy,$j['defeated'],true);$npc=ADChampions()[$enemy];
    $result=['title'=>$win?($first?'Champion bested':'A good practice bout'):'Back to the sanctuary','won'=>$win,'first'=>$first,'enemy'=>$enemy,'cards'=>[],'item'=>null];
    if($first){
        $j['defeated'][]=$enemy;$result['cards']=ADRewards($enemy);$j['collection']=array_values(array_unique([...$j['collection'],...$result['cards']]));
        $item=['iddin'=>'stone-wraps','imdugud'=>'reed-crown','huwawa'=>'jade-vest','bull-of-heaven'=>'azure-mail'][$enemy];$j['inventory']=array_values(array_unique([...$j['inventory'],$item]));$result['item']=$item;
        $result['text']=$npc['name'].' bows. Six signature techniques are permanently available in your library. Equip them outside fights; your current deck stays as you arranged it.';
        if($enemy==='bull-of-heaven'){$j['flags']['regionComplete']=true;$result['text'].=' The First Courtyard is complete. The road to the wider world is open.';}
        ADLog($j,'Defeated '.$npc['name'].' and unlocked their six signature moves.');
    }elseif($win)$result['text']='Your partner bows again. Practice grants no duplicate rewards. Both fighters begin their next bout at full health.';
    else{$j['zone']='sanctuary';$j['position']=['x'=>11,'y'=>12];$result['text']='You wake on a sanctuary cushion. Your techniques, equipment, seals, and tasks are all safe. Every new challenge begins at full health.';ADLog($j,'Returned to the sanctuary after a defeat. Progress preserved.');}
    $j['lastResult']=$result;$j['phase']='result';
}
function ADValidateDeck(array $j,array $deck): array {
    if(!$deck||count($deck)>36||count(array_unique($deck,SORT_REGULAR))!==count($deck))throw new InvalidArgumentException('Equip unique techniques, up to six per color.');
    $counts=array_fill_keys(DFG_COLORS,0);$cards=DFGCatalog();
    foreach($deck as $id){if(!is_int($id)||!in_array($id,$j['collection'],true))throw new InvalidArgumentException('That technique is not unlocked.');++$counts[$cards[$id]['color']];}
    foreach($counts as $n)if($n<1||$n>6)throw new InvalidArgumentException('Keep one to six techniques in every color.');return array_values($deck);
}
function ADApply(array $j,array $a): array {
    $j['zone']??='courtyard';
    $type=$a['type']??'';
    if($type==='battle'){
        if($j['phase']!=='fight'||!is_array($a['move']??null))throw new LogicException('No active challenge.');
        $j['battle']=DFGRunBot(DFGApply($j['battle'],0,$a['move']));ADFinishFight($j);
    }elseif($type==='continue'){
        if($j['phase']!=='result')throw new LogicException('No result to acknowledge.');$j['phase']='world';$j['battle']=null;$j['dialog']=null;
    }else{
        if($j['phase']!=='world')throw new LogicException('Finish the current bout before exploring or changing equipment.');
        if($type==='move'){
            $path=$a['path']??null;if(!is_array($path)||!$path||count($path)>64)throw new InvalidArgumentException('Invalid walking path.');
            foreach($path as $step){if(!is_array($step)||count($step)!==2||!is_int($step[0])||!is_int($step[1])||abs($step[0]-$j['position']['x'])+abs($step[1]-$j['position']['y'])!==1||!ADWalkable($step[0],$step[1],$j['zone']))throw new InvalidArgumentException('That path crosses an obstacle.');$j['position']=['x'=>$step[0],'y'=>$step[1]];}
            $j['dialog']=null;
        }elseif($type==='door'){
            $id=$a['id']??'';if(!is_string($id)||!ADNear($j,$id))throw new LogicException('Walk beside the doorway first.');
            if($id==='sanctuary'&&$j['zone']==='courtyard'){$j['zone']='sanctuary';$j['position']=['x'=>11,'y'=>12];}
            elseif($id==='exit'&&$j['zone']==='sanctuary'){$j['zone']='courtyard';$j['position']=['x'=>2,'y'=>14];}
            else throw new InvalidArgumentException('That is not a usable doorway.');$j['dialog']=null;
        }elseif($type==='interact')ADInteract($j,(string)($a['id']??''));
        elseif($type==='challenge')ADStartFight($j,(string)($a['id']??''),(string)($a['color']??'Red'));
        elseif($type==='deck')$j['deck']=ADValidateDeck($j,$a['cards']??[]);
        elseif($type==='equip'){
            $slot=$a['slot']??'';$id=$a['item']??null;
            if(!array_key_exists($slot,$j['equipment'])||($id!==null&&(!is_string($id)||!in_array($id,$j['inventory'],true)||ADItems()[$id]['slot']!==$slot)))throw new InvalidArgumentException('Choose owned equipment for that slot.');$j['equipment'][$slot]=$id;
        }elseif($type==='close')$j['dialog']=null;
        else throw new InvalidArgumentException('Unknown adventure action.');
    }++$j['revision'];return $j;
}
function ADQuests(array $j): array {
    $f=$j['flags'];$won=$j['defeated'];
    return [
        ['title'=>'A rather heavy invitation','detail'=>'Read the tablet in your stone mailbox.','done'=>!empty($f['invited']),'target'=>'mailbox'],
        ['title'=>'Ask the tablet-reader','detail'=>'Find Nidaba beside the market.','done'=>!empty($f['scribe']),'target'=>'scribe'],
        ['title'=>'Water before glory','detail'=>empty($f['gardener'])?'Speak to Amat beside the canal.':(empty($f['stopperFound'])?'Find the clay stopper beside the market.':(empty($f['reedFound'])?'Find the reed binding on the north bank.':(empty($f['canalRestored'])?'Repair the sluice at the bridge.':'Return to Amat for the Garden Witness.'))),'done'=>!empty($f['gardenWitness']),'target'=>empty($f['gardener'])?'gardener':(empty($f['stopperFound'])?'stopper':(empty($f['reedFound'])?'reed':(empty($f['canalRestored'])?'sluice':'gardener')))],
        ['title'=>'Respect the cedar','detail'=>'Restore the garden, then defeat Huwawa in the cedar grove.','done'=>in_array('huwawa',$won,true),'target'=>'huwawa'],
        ['title'=>'A bird’s-eye assessment','detail'=>'Challenge Imdugud below the training terrace.','done'=>in_array('imdugud',$won,true),'target'=>'imdugud'],
        ['title'=>'The proper challenge','detail'=>'Bring the Garden Witness and both guardian seals to Puzur.','done'=>!empty($f['witnessed']),'target'=>'attendant'],
        ['title'=>'Greater champion','detail'=>'Defeat the Bull of Heaven, then visit the road beyond the court.','done'=>in_array('bull-of-heaven',$won,true),'target'=>'bull-of-heaven']
    ];
}
function ADObserve(array $j): array {
    $view=$j;$view['zone']=$j['zone']??'courtyard';$view['battle']=$j['battle']?DFGObserve($j['battle'],0):null;if($view['battle'])unset($view['battle']['initialSeed']);
    $view['quests']=ADQuests($j);$view['canChallenge']=array_values(array_filter(array_keys(ADChampions()),fn($id)=>ADCanChallenge($j,$id)));
    $view['entities']=array_filter(ADEntities($view['zone']),fn($e,$id)=>ADVisible($j,$id),ARRAY_FILTER_USE_BOTH);return $view;
}
