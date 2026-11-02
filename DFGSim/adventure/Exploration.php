<?php
declare(strict_types=1);

// Optional Sumerian side activities; combat continues to use the shared engine.
function ADUpgradeExploration(array $j): array {
    if(($j['explorationRevision']??0)>=1)return $j;
    $j['explorationRevision']=1;$j['copper']=12;$j['jobs']=[];
    ++$j['revision'];ADLog($j,'Aya tucked 12 copper into your travel pouch. A new riverside path opens beside the canal bridge.');return $j;
}
function ADRiversideMap(): array {
    return ['width'=>24,'height'=>16,'tile'=>64,'name'=>'The Riverside Quarter','region'=>'Sumer · Useful hands, patient feet',
        'art'=>'adventure/assets/riverside-quarter-v1.webp',
        'blocked'=>[[0,0,24,1],[1,1,7,3],[5,3,2,1],[7,4,1,1],[9,1,5,3],[16,1,5,3],
            [14,2,1,5],[14,6,3,1],[19,6,2,1],[16,4,1,1],[20,4,1,1],
            [21,0,3,16],[20,11,1,2],[15,13,6,1],[10,14,14,2],
            [14,9,1,2],[14,10,3,1],[19,10,2,1],[0,8,3,1],[5,9,1,4],[0,13,4,1],[7,10,3,3]]];
}
function ADRiversideEntities(): array {
    $a='adventure/assets/';
    return [
        'river-exit'=>['name'=>'Path to the First Courtyard','x'=>1,'y'=>7,'kind'=>'door','icon'=>'↖'],
        'outfitter'=>['name'=>'Belit, Riverside Outfitter','x'=>10,'y'=>4,'kind'=>'npc','portrait'=>$a.'belit-overworld-v1.webp'],
        'potter'=>['name'=>'Taram, Potter of Patient Hands','x'=>5,'y'=>5,'kind'=>'npc','portrait'=>$a.'taram-overworld-v1.webp'],
        'dockmaster'=>['name'=>'Lugal, Dock Steward','x'=>17,'y'=>8,'kind'=>'npc','portrait'=>$a.'lugal-overworld-v1.webp'],
        'river-teacher'=>['name'=>'Sila, Teacher of the Current','x'=>18,'y'=>5,'kind'=>'npc','portrait'=>$a.'sila-overworld-v1.webp'],
        'clay-bank'=>['name'=>'Soft river clay','x'=>3,'y'=>11,'kind'=>'object','icon'=>'◆'],
        'kiln'=>['name'=>'Taram’s kiln','x'=>7,'y'=>4,'kind'=>'object','icon'=>'♨'],
        'cargo-west'=>['name'=>'Western cargo lashings','x'=>16,'y'=>7,'kind'=>'object','icon'=>'≋'],
        'cargo-east'=>['name'=>'Quayside cargo lashings','x'=>19,'y'=>9,'kind'=>'object','icon'=>'≋'],
        'loading-post'=>['name'=>'The loading post','x'=>20,'y'=>8,'kind'=>'object','icon'=>'⚓'],
        'herb-west'=>['name'=>'Western herb bed','x'=>16,'y'=>4,'kind'=>'object','icon'=>'❧'],
        'herb-east'=>['name'=>'Eastern herb bed','x'=>20,'y'=>4,'kind'=>'object','icon'=>'❧'],
        'water-jar'=>['name'=>'Garden water jar','x'=>13,'y'=>9,'kind'=>'object','icon'=>'≈'],
        'reflection-one'=>['name'=>'Stone of the still hand','x'=>8,'y'=>7,'kind'=>'object','icon'=>'Ⅰ'],
        'reflection-two'=>['name'=>'Stone of the yielding foot','x'=>11,'y'=>12,'kind'=>'object','icon'=>'Ⅱ'],
        'reflection-three'=>['name'=>'Stone of the patient eye','x'=>20,'y'=>5,'kind'=>'object','icon'=>'Ⅲ'],
        'river-cache'=>['name'=>'A forgotten travel pouch','x'=>8,'y'=>10,'kind'=>'object','icon'=>'◇']
    ];
}
function ADJobs(): array {
    return [
        'potter'=>['title'=>'A bowl before a boast','pay'=>10,'steps'=>['clay-bank','kiln'],
            'instructions'=>'Gather soft clay in the southwest yard, bring it to the kiln, then return to Taram.','card'=>1100],
        'dockmaster'=>['title'=>'Steady feet, safe cargo','pay'=>12,'steps'=>['cargo-west','cargo-east','loading-post'],
            'instructions'=>'Check the western lashings, check the quayside lashings, report at the loading post, then return to Lugal.','card'=>1101],
        'river-teacher'=>['title'=>'A garden, not a battlefield','pay'=>8,'steps'=>['herb-west','herb-east','water-jar'],
            'instructions'=>'Weed the western herb bed, weed the eastern bed, fetch water at the jar outside the garden, then return to Sila.','card'=>1102]
    ];
}
function ADLessonCards(array $base): array {
    $out=[];
    foreach([[1100,3,'Kiln-Hardened Palm',1,3,'Taram, Potter of Patient Hands'],
        [1101,17,'Dockside Footwork',4,3,'Lugal, Dock Steward'],
        [1102,29,'Patient Current',2,4,'Sila, Teacher of the Current']] as [$id,$source,$name,$attack,$block,$teacher]){
        $c=$base[$source];$c['id']=$id;$c['name']=$name;$c['attack']=$attack;$c['block']=$block;$c['artId']=$source;
        $c['teacher']=$teacher;$c['comparisonId']=$source;$c['tradeoff']=$attack>$base[$source]['attack']?'One more attack for one less block than '.$base[$source]['name'].'.':'One less attack for one more block than '.$base[$source]['name'].'.';
        $c['flavor']='A permanent lesson earned through patient work in the Riverside Quarter.';$out[$id]=RGCompileCard($c);
    }return $out;
}
function ADShopStock(): array {return ['high-vest'=>20,'low-vest'=>20,'narrow-mail'=>24,'echo-wraps'=>24,'rally-wraps'=>22,'iron-crown'=>26,'reed-boots'=>16];}
function ADCanLearn(array $j,string $owner): bool {
    if(!isset(ADJobs()[$owner])||($j['jobs'][$owner]['completed']??0)<1)return false;
    return $owner!=='river-teacher'||(!empty($j['flags']['reflection-one'])&&!empty($j['flags']['reflection-two'])&&!empty($j['flags']['reflection-three']));
}
function ADExplorationAction(array &$j,array $a): bool {
    $type=$a['type']??'';
    if(!in_array($type,['buy','job','learn'],true))return false;
    if(empty($j['flags']['invited']))throw new LogicException('Read your invitation before taking on work.');
    $owner=$type==='buy'?'outfitter':($a['id']??'');
    if(!is_string($owner)||$j['zone']!=='riverside'||!ADNear($j,$owner))throw new LogicException('Walk beside the person offering this activity.');
    if($type==='buy'){
        $id=$a['item']??null;$stock=ADShopStock();
        if(!is_string($id)||!isset($stock[$id]))throw new InvalidArgumentException('That item is not for sale here.');
        if(in_array($id,$j['inventory'],true))throw new LogicException('You already own that equipment.');
        if($j['copper']<$stock[$id])throw new LogicException('You need more copper. Taram, Lugal and Sila offer paid work.');
        $j['copper']-=$stock[$id];$j['inventory'][]=$id;
        ADLog($j,'Bought '.ADItems()[$id]['name'].' for '.$stock[$id].' copper. Equip it outside fights.');
        ADInteract($j,'outfitter');return true;
    }
    $job=ADJobs()[$owner]??null;if(!$job)throw new InvalidArgumentException('There is no activity here.');
    if($type==='job'){
        if(!empty($j['jobs'][$owner]['active']))throw new LogicException('Finish the current errand before taking another.');
        $j['jobs'][$owner]=['active'=>true,'step'=>0,'completed'=>$j['jobs'][$owner]['completed']??0];
        ADLog($j,'Accepted “'.$job['title'].'” for '.$job['pay'].' copper.');ADInteract($j,$owner);
    }else{
        if(!ADCanLearn($j,$owner))throw new LogicException('Finish this teacher’s lesson requirements first.');
        if(in_array($job['card'],$j['collection'],true))throw new LogicException('You already learned this technique.');
        $j['collection'][]=$job['card'];$card=DFGCatalog()[$job['card']];ADLog($j,'Learned '.$card['name'].' from '.ADEntities('riverside')[$owner]['name'].'.');
        $j['dialog']=ADDialog($j,$owner,'“Now take that patience into the arena.” You permanently learned '.$card['name'].'. Open Techniques and replace an equipped '.$card['color'].' card to use it. Your deck stays as you arranged it.');
    }return true;
}
function ADExplorationInteract(array &$j,string $id): bool {
    if($id==='quay-path'){
        $j['dialog']=ADDialog($j,$id,'Beyond the bridge, a busier path follows the river. A potter needs a hand, an outfitter sells equipment, and someone in the herb garden is offering lessons.',[['label'=>'Visit the Riverside Quarter','action'=>'door']]);return true;
    }
    if(($j['zone']??'courtyard')!=='riverside')return false;
    if($id==='river-exit'){$j['dialog']=ADDialog($j,$id,'The courtyard is a short walk upriver. Your work, copper and lessons will be here when you return.',[['label'=>'Return to the First Courtyard','action'=>'door']]);return true;}
    if(empty($j['flags']['invited'])){$j['dialog']=ADDialog($j,$id,'“You look like a challenger. Read the invitation in your courtyard mailbox first; then come back and make yourself useful.”');return true;}
    if($id==='outfitter'){$j['dialog']=ADDialog($j,$id,'“Belit’s Practical Miracles. No miracles guaranteed.” Belit smiles. “Buy a piece once, change equipment freely between fights. If your pouch is light, the potter, dock steward and garden teacher all pay for honest work.”', [['label'=>'Browse equipment · '.$j['copper'].' copper','action'=>'shop']]);return true;}
    if(isset(ADJobs()[$id])){
        $job=ADJobs()[$id];$state=$j['jobs'][$id]??['active'=>false,'completed'=>0,'step'=>0];$options=[];
        $intro=match($id){'potter'=>'“A good palm is rather like a good pot. Firm where it must be. Empty where it can be.”',
            'dockmaster'=>'“The river doesn’t care how impressive your stance looks. It cares whether you fall in.”',
            default=>'“The garden is an excellent opponent. It never rushes, and it has yet to lose an argument.”'};
        if($state['active']&&$state['step']>=count($job['steps'])){
            $j['jobs'][$id]['active']=false;$j['jobs'][$id]['completed']++;$j['copper']+=$job['pay'];$state=$j['jobs'][$id];
            $intro.=' Thank you. You earned '.$job['pay'].' copper.';ADLog($j,'Completed “'.$job['title'].'”: +'.$job['pay'].' copper.');
        }
        if($state['active']){
            $target=$job['steps'][$state['step']];$intro.=' Next: '.ADEntities('riverside')[$target]['name'].'. Then return here for your '.$job['pay'].' copper.';
        }else $options[]=['label'=>($state['completed']?'Another errand':'Help out').' · earn '.$job['pay'].' copper','action'=>'job'];
        $intro.="\n\n".$job['instructions'];
        if(!in_array($job['card'],$j['collection'],true)){
            $intro.="\n\n".($id==='river-teacher'?'For Patient Current, finish one garden errand and read all three reflection stones around this quarter.':'Finish one errand and I will teach you '.DFGCatalog()[$job['card']]['name'].'.');
            if(ADCanLearn($j,$id))$options[]=['label'=>'Learn '.DFGCatalog()[$job['card']]['name'],'action'=>'learn'];
        }else $intro.="\n\n“Your lesson is yours to keep. There is always more work if you need copper.”";
        $j['dialog']=ADDialog($j,$id,$intro,$options);return true;
    }
    $reflections=['reflection-one'=>'The still hand: watch what is offered before deciding what to answer.',
        'reflection-two'=>'The yielding foot: giving a little ground can leave you standing after the blow.',
        'reflection-three'=>'The patient eye: a familiar shape does not always promise a familiar outcome.'];
    if(isset($reflections[$id])){
        if(empty($j['flags'][$id])){$j['flags'][$id]=true;ADLog($j,'Read '.$id.': '.$reflections[$id]);}
        $j['dialog']=ADDialog($j,$id,$reflections[$id]."\n\nSila in the herb garden teaches Patient Current to those who have helped the garden and considered all three stones.");return true;
    }
    if($id==='river-cache'){
        if(empty($j['flags']['riverCache'])){$j['flags']['riverCache']=true;$j['copper']+=6;ADLog($j,'Found 6 copper in a weathered travel pouch by the reeds.');}
        $j['dialog']=ADDialog($j,$id,'Inside the weathered pouch: 6 copper and a receipt for “one very ordinary miracle.” The pouch is empty now.');return true;
    }
    $responses=['clay-bank'=>'You lift a smooth lump of river clay. Taram will be delighted that it contains no frogs.',
        'kiln'=>'You carry the clay to the kiln and help turn the prepared bowls. Taram is waiting in the yard with your wages.',
        'cargo-west'=>'You tighten the western lashings. Nothing rolls, falls, or makes a suspicious cracking noise.',
        'cargo-east'=>'The quayside ropes are secure. Your balance is improving without a single dramatic speech.',
        'loading-post'=>'The cargo is ready. Return to Lugal for your pay.',
        'herb-west'=>'You clear the western bed, carefully leaving the herbs in place. Sila approves of this distinction.',
        'herb-east'=>'The eastern bed is tidy. The water jar is outside the garden, near the dock path.',
        'water-jar'=>'You carry fresh water back for the herbs. Return to Sila for your pay.'];
    if(isset($responses[$id])){
        $text='There is useful work here. Ask '.match($id){'clay-bank','kiln'=>'Taram in the pottery yard','cargo-west','cargo-east','loading-post'=>'Lugal at the docks',default=>'Sila in the herb garden'}.' for an errand first.';
        foreach(ADJobs() as $owner=>$job){$state=$j['jobs'][$owner]??[];
            if(!empty($state['active'])&&in_array($id,$job['steps'],true)){
                if(($job['steps'][$state['step']]??null)===$id){++$j['jobs'][$owner]['step'];$text=$responses[$id];}
                else $text='This part is already handled, or another step comes first. Next: '.(($job['steps'][$state['step']]??null)?ADEntities('riverside')[$job['steps'][$state['step']]]['name']:'return to '.ADEntities('riverside')[$owner]['name']).'.';
                break;
            }
        }$j['dialog']=ADDialog($j,$id,$text);return true;
    }return false;
}
function ADExplorationQuests(array $j): array {
    $out=[['title'=>'Down by the river','detail'=>'Take the riverside path beside the courtyard’s canal bridge. Equipment, paid work and lessons await.','done'=>!empty($j['flags']['riversideVisited']),'target'=>'quay-path','zone'=>'courtyard','optional'=>true]];
    foreach(ADJobs() as $id=>$job){
        $state=$j['jobs'][$id]??[];$active=!empty($state['active']);$known=in_array($job['card'],$j['collection'],true);
        $target=$active?($job['steps'][$state['step']]??$id):$id;
        $detail=$active?'Next: '.ADEntities('riverside')[$target]['name'].'. Return to '.ADEntities('riverside')[$id]['name'].' for '.$job['pay'].' copper.':$job['instructions'].' Earn '.$job['pay'].' copper; errands can be repeated.';
        $out[]=['title'=>$job['title'],'detail'=>$detail,'done'=>!$active&&($state['completed']??0)>0,'target'=>$target,'zone'=>'riverside','optional'=>true];
        if($id==='river-teacher'&&!$active&&($state['completed']??0)>0&&!$known)foreach(['reflection-one','reflection-two','reflection-three'] as $stone)if(empty($j['flags'][$stone])){$target=$stone;break;}
        $out[]=['title'=>'Lesson: '.DFGCatalog()[$job['card']]['name'],'detail'=>$id==='river-teacher'?'Help the herb garden, read the three reflection stones, then ask Sila to teach you.':'Complete one errand, then ask '.ADEntities('riverside')[$id]['name'].' for a permanent technique.','done'=>$known,'target'=>$known?$id:($id==='river-teacher'?$target:$id),'zone'=>'riverside','optional'=>true];
    }return $out;
}
