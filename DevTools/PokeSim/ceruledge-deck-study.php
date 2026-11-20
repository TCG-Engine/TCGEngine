<?php
/** Card-use attribution and fixed-policy deck swaps, isolated from user games.
 * Default: approved 22-Fire baseline, preserving four Rotom.
 * --suite=original reproduces the historical experiments against the original list.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
function cerStudyVariants(): array {
    return [
        'baseline'=>[],
        'blender'=>['sv08-191'=>-1,'sv08-164'=>1],
        'blender-firebreather'=>['sv08-191'=>-1,'sv08-164'=>1,'me02.5-183'=>-1,'me02-089'=>1],
        'blender-firebreather-21fire'=>['sv08-191'=>-1,'sv08-164'=>1,'me02.5-183'=>-1,'me02-089'=>1,'me02-020'=>-1,'mee-002'=>1],
        'blender-nonex-to-fire'=>['sv08-191'=>-1,'sv08-164'=>1,'me02-020'=>-1,'mee-002'=>1],
        'blender-firebreather-22fire'=>['sv08-191'=>-1,'sv08-164'=>1,'me02.5-183'=>-1,'me02-089'=>1,'me02-020'=>-1,'sv07-118'=>-1,'mee-002'=>2],
        'one-boss-to-firebreather'=>['me02.5-183'=>-1,'me02-089'=>1],
        'boss-to-firebreather'=>['me02.5-183'=>-2,'me02-089'=>2],
        'rotom-to-firebreather'=>['sv07-118'=>-2,'me02-089'=>2],
        'nonex-to-firebreather'=>['me02-020'=>-1,'me02-089'=>1],
        'boss-to-fire-energy'=>['me02.5-183'=>-2,'mee-002'=>2],
        'rotom-to-fire-energy'=>['sv07-118'=>-2,'mee-002'=>2],
        'prism-to-firebreather'=>['me04-080'=>-1,'me02-089'=>1],
        'boss-to-mixed-energy'=>['me02.5-183'=>-2,'me02-089'=>1,'mee-002'=>1],
        'boss-nonex-to-firebreather'=>['me02.5-183'=>-2,'me02-020'=>-1,'me02-089'=>3],
        'stretcher-to-firebreather'=>['me02.5-196'=>-1,'me02-089'=>1],
    ];
}
function cerEnergyVariants(): array {
    return [
        'baseline'=>[],
        'lillie-to-fire'=>['me02.5-192'=>-1,'mee-002'=>1],
        'boss-to-fire'=>['me02.5-183'=>-1,'mee-002'=>1],
        'stretcher-to-fire'=>['me02.5-196'=>-1,'mee-002'=>1],
        'prism-to-fire'=>['me04-080'=>-1,'mee-002'=>1],
        'explorer-to-fire'=>['sv08.5-107'=>-1,'mee-002'=>1],
        'ultra-to-fire'=>['me01-131'=>-1,'mee-002'=>1],
        'firebreather-to-fire'=>['me02-089'=>-1,'mee-002'=>1],
        'two-lillie-to-fire'=>['me02.5-192'=>-2,'mee-002'=>2],
        'lillie-boss-to-fire'=>['me02.5-192'=>-1,'me02.5-183'=>-1,'mee-002'=>2],
    ];
}
function cerRefineVariants(): array {
    return [
        'baseline'=>[],
        'lillie-to-fire'=>['me02.5-192'=>-1,'mee-002'=>1],
        'boss-to-fire'=>['me02.5-183'=>-1,'mee-002'=>1],
        'stretcher-to-fire'=>['me02.5-196'=>-1,'mee-002'=>1],
        'prism-to-fire'=>['me04-080'=>-1,'mee-002'=>1],
        'explorer-to-fire'=>['sv08.5-107'=>-1,'mee-002'=>1],
        'ultra-to-fire'=>['me01-131'=>-1,'mee-002'=>1],
        'firebreather-to-fire'=>['me02-089'=>-1,'mee-002'=>1],
        'lillie-to-firebreather'=>['me02.5-192'=>-1,'me02-089'=>1],
        'lillie-to-gear'=>['me02.5-192'=>-1,'sv10.5b-084'=>1],
        'firebreather-to-gear'=>['me02-089'=>-1,'sv10.5b-084'=>1],
        'fire-to-petrel'=>['mee-002'=>-1,'me02.5-207'=>1],
        'lillie-to-petrel'=>['me02.5-192'=>-1,'me02.5-207'=>1],
        'lillie-to-naveen'=>['me02.5-192'=>-1,'me03-079'=>1],
        'two-lillie-to-naveen'=>['me02.5-192'=>-2,'me03-079'=>2],
        'three-lillie-to-naveen'=>['me02.5-192'=>-3,'me03-079'=>3],
        'four-lillie-to-naveen'=>['me02.5-192'=>-4,'me03-079'=>4],
    ];
}
function cerSearchVariants(): array {
    return [
        'baseline'=>[],
        'boss-to-poffin'=>['me02.5-183'=>-1,'sv05-144'=>1],
        'stretcher-to-poffin'=>['me02.5-196'=>-1,'sv05-144'=>1],
        'prism-to-poffin'=>['me04-080'=>-1,'sv05-144'=>1],
        'explorer-to-poffin'=>['sv08.5-107'=>-1,'sv05-144'=>1],
        'firebreather-to-poffin'=>['me02-089'=>-1,'sv05-144'=>1],
        'boss-stretcher-to-poffin'=>['me02.5-183'=>-1,'me02.5-196'=>-1,'sv05-144'=>2],
        'boss-to-pad'=>['me02.5-183'=>-1,'me03-081'=>1],
        'boss-to-hilda'=>['me02.5-183'=>-1,'sv10.5w-084'=>1],
        'firebreather-to-hilda'=>['me02-089'=>-1,'sv10.5w-084'=>1],
        'explorer-to-hilda'=>['sv08.5-107'=>-1,'sv10.5w-084'=>1],
    ];
}
function cerAceVariants(): array {
    return ['baseline'=>[],'blender-to-legacy'=>['sv08-164'=>-1,'sv06-167'=>1]];
}
function cerBudewVariants(): array {
    $cuts=['sv07-118'=>-4,'me04-080'=>-4];
    return [
        'baseline'=>[],
        'budew-fez-shovels'=>$cuts+['sv08.5-004'=>2,'me02.5-142'=>1,'sv05-144'=>2,'me03-074'=>3],
        'budew3-fez-shovels'=>$cuts+['sv08.5-004'=>3,'me02.5-142'=>1,'sv05-144'=>2,'me03-074'=>2],
        'budew3-shovels'=>$cuts+['sv08.5-004'=>3,'sv05-144'=>2,'me03-074'=>3],
        'budew3-fire'=>$cuts+['sv08.5-004'=>3,'sv05-144'=>2,'mee-002'=>3],
        'budew-fez-tutors'=>$cuts+['sv08.5-004'=>2,'me02.5-142'=>1,'sv05-144'=>2,'me02.5-207'=>2,'me03-079'=>1],
    ];
}
function cerStudyBaselineFile(string $suite): string {
    return match($suite){'original'=>'ceruledge-original-baseline.txt','energy'=>'ceruledge-21fire-baseline.txt','refine'=>'ceruledge-22fire-baseline.txt','search','ace','budew'=>'ceruledge-3-1-search-baseline.txt',default=>throw new InvalidArgumentException('Unknown suite')};
}
function cerStudyDeck(array $changes,string $suite='original'): array {
    // Freeze experimental controls when the registered/selectable list changes.
    $file=cerStudyBaselineFile($suite);
    $deck=PokeParseDeckText(file_get_contents(__DIR__.'/fixtures/'.$file));
    foreach($changes as $id=>$delta){
        $found=false;foreach($deck as &$entry)if($entry['id']===$id){$entry['count']+=$delta;$found=true;break;}unset($entry);
        if(!$found)$deck[]=['id'=>$id,'count'=>$delta];
    }
    $deck=array_values(array_filter($deck,fn($entry)=>$entry['count']>0));
    $errors=PokeValidateDeck($deck);if($errors)throw new RuntimeException(implode('; ',$errors));return $deck;
}
function cerStudySource(array $ctx,array $action): ?string {
    if(isset($action['source']))return PokeBotCard($ctx,$action['source'])['id']??null;
    $prompt=$ctx['view']['decision']['prompt']??'';
    foreach(['Buddy-Buddy Poffin'=>'sv05-144','Hilda'=>'sv10.5w-084','Naveen'=>'me03-079','Brilliant Blender'=>'sv08-164','Prism Tower'=>'me04-080','Ultra Ball'=>'me01-131',"Explorer's Guidance"=>'sv08.5-107','Firebreather'=>'me02-089','Gather Strength'=>'me02-019','Infernal Slash'=>'me02-020','Fan Call'=>'sv07-118','Night Stretcher'=>'me02.5-196'] as $text=>$id)if(str_contains($prompt,$text))return $id;
    return null;
}
function cerStudyGame(array $deck,string $opponent,int $seed,int $first,?callable $observer=null): array {
    PokeCreateGame(PokeNamedDeck($opponent),$deck,$seed,$first);
    // Freeze the same policy across candidates even if future deck-detection
    // rules change. Hidden cards are never supplied to the policy.
    PokeSetVar('deckKey:2','ceruledge-ex');
    $result=['seed'=>$seed,'firstPlayer'=>$first,'won'=>false,'complete'=>false,'by2ExAttack'=>false,'by4Ready330'=>false,'by5Ready330'=>false,'blenderBy2'=>false,'blenderBy4'=>false,'petrelFoundBlender'=>false,'turns'=>[],'cards'=>[]];
    $result['legacyAttached']=false;$result['legacyTriggered']=false;
    $lastSource=null;
    for($step=0;$step<1500&&!GetWinner();++$step){
        $seat=PokePendingPlayer();$view=PokeObservation($seat);$action=PokeBotChoose($view);
        if(!$action)throw new RuntimeException('Missing bot action');
        if($seat===2&&$action['type']==='attach'&&(PokeBotCard(PokeCeruledgeContext($view),$action['source'])['id']??'')==='sv06-167')$result['legacyAttached']=true;
        $early=$seat===2&&$view['phase']==='MAIN'&&$view['turnPlayer']===2&&$view['players'][2]['turns']<=5;
        $source=null;$beforeDiscard=0;$beforeHandFire=0;
        if($seat===2&&($view['phase']==='SETUP'||$early))foreach($view['players'][2]['Hand'] as $card)$result['cards'][$card['id']]['seen']=true;
        if($early){
            $ctx=PokeCeruledgeContext($view);$turn=$ctx['own']['turns'];$source=cerStudySource($ctx,$action);
            if($action['type']!=='decision')$lastSource=$source;
            if($source===null&&$lastSource==='me02.5-207'&&str_contains($view['decision']['prompt']??'','Search your deck'))$source=$lastSource;
            if($source==='me02.5-207'&&$action['type']==='decision')foreach($view['decision']['choices'] as $choice)if($choice['value']===($action['value']??'')&&$choice['card']==='sv08-164')$result['petrelFoundBlender']=true;
            if($source==='sv08-164'&&$action['type']==='trainer'){
                if($turn<=2)$result['blenderBy2']=true;if($turn<=4)$result['blenderBy4']=true;
            }
            $beforeDiscard=$ctx['discardEnergy'];$beforeHandFire=$ctx['fire'];
            $canAttackEx=(bool)array_filter($view['actions'],fn($a)=>$a['type']==='attack'&&(PokeBotCard($ctx,$a['source'])['id']??'')==='sv08-036');
            if($canAttackEx&&$turn<=2)$result['by2ExAttack']=true;
            if($canAttackEx&&$ctx['discardEnergy']>=15){$result['by5Ready330']=true;if($turn<=4)$result['by4Ready330']=true;}
            $result['turns'][$turn]=['discard'=>$ctx['discardEnergy']];
            if($source&&in_array($action['type'],['trainer','ability','attach','attack'],true)){
                $result['cards'][$source]['uses']=($result['cards'][$source]['uses']??0)+1;
                $field=$action['type'].'Uses';$result['cards'][$source][$field]=($result['cards'][$source][$field]??0)+1;
            }
        }
        // Diagnostics may inspect the accepted policy decision before resolution.
        if($observer!==null)$observer($view,$action);
        PokeApplyAction($action);
        if($early&&$source){
            $after=count(array_filter(PokeObjects(2,'Discard'),fn($c)=>CardType($c->CardID)==='Energy'));
            $result['cards'][$source]['discardedEnergy']=($result['cards'][$source]['discardedEnergy']??0)+max(0,$after-$beforeDiscard);
            if(in_array($source,['me02-089','me02-019'],true)&&$action['type']==='decision'){
                $fire=count(array_filter(PokeObjects(2,'Hand'),fn($c)=>$c->CardID==='mee-002'));
                $result['cards'][$source]['searchedFire']=($result['cards'][$source]['searchedFire']??0)+max(0,$fire-$beforeHandFire);
            }
        }
    }
    $result['won']=GetWinner()===2;$result['complete']=GetWinner()>0;$result['legacyTriggered']=(bool)PokeVar('legacyEnergyUsed:2',false);
    foreach(PokeVar('damageTurns',[]) as $row)if($row['player']===2&&isset($result['turns'][$row['turn']]))$result['turns'][$row['turn']]['damage']=$row['damage'];
    return $result;
}
function cerStudySummary(array $games): array {
    $n=count($games);$out=['games'=>$n,'completed'=>0,'wins'=>0,'by2ExAttack'=>0,'by4Ready330'=>0,'by5Ready330'=>0,'blenderBy2'=>0,'blenderBy4'=>0,'petrelFoundBlender'=>0,'legacyAttached'=>0,'legacyTriggered'=>0,'turns'=>[],'cards'=>[]];
    foreach($games as $g){
        foreach(['legacyAttached','legacyTriggered'] as $key)$out[$key]+=(int)($g[$key]??false);
        foreach(['complete'=>'completed','won'=>'wins','by2ExAttack'=>'by2ExAttack','by4Ready330'=>'by4Ready330','by5Ready330'=>'by5Ready330','blenderBy2'=>'blenderBy2','blenderBy4'=>'blenderBy4','petrelFoundBlender'=>'petrelFoundBlender'] as $field=>$sum)$out[$sum]+=(int)$g[$field];
        foreach($g['cards'] as $id=>$metrics)foreach(['seen','uses','trainerUses','abilityUses','attachUses','attackUses','discardedEnergy','searchedFire'] as $field)$out['cards'][$id][$field]=($out['cards'][$id][$field]??0)+(int)($metrics[$field]??0);
    }
    foreach(range(1,5) as $turn){$rows=[];foreach($games as $g)if(isset($g['turns'][$turn]))$rows[]=$g['turns'][$turn];if($rows)$out['turns'][$turn]=['reached'=>count($rows),'avgDiscard'=>round(array_sum(array_column($rows,'discard'))/count($rows),2),'avgDamage'=>round(array_sum(array_column($rows,'damage'))/count($rows),1)];}
    return $out;
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__)return;
$options=getopt('', ['suite:','pairs:','seed:','variants:','opponents:','out:']);
$pairs=(int)($options['pairs']??20);$seed=(int)($options['seed']??30001);
if($pairs<1||$pairs>1000||$seed<1)throw new InvalidArgumentException('Invalid seed/pair count');
$suite=$options['suite']??'refine';
$all=match($suite){'original'=>cerStudyVariants(),'energy'=>cerEnergyVariants(),'refine'=>cerRefineVariants(),'search'=>cerSearchVariants(),'ace'=>cerAceVariants(),'budew'=>cerBudewVariants(),default=>throw new InvalidArgumentException('Unknown suite')};$names=isset($options['variants'])?explode(',',$options['variants']):array_keys($all);
$opponents=explode(',',$options['opponents']??'brisbane-lopunny');
$out=['policy'=>PokeSimulationPolicy(),'suite'=>$suite,'baselineSHA256'=>hash_file('sha256',__DIR__.'/fixtures/'.cerStudyBaselineFile($suite)),'seed'=>$seed,'pairs'=>$pairs,'note'=>'Fixed policy; same seed ranges and starting orders. Slot swaps change deals/mulligans. Usage attribution is descriptive, not causal. byX rates count every game, including losses before turn X.','variants'=>[]];
foreach($names as $name){
    if(!isset($all[$name]))throw new InvalidArgumentException('Unknown variant');$deck=cerStudyDeck($all[$name],$suite);$record=['changes'=>$all[$name],'deck'=>$deck,'opponents'=>[]];
    foreach($opponents as $opponent){
        $games=[];foreach(range($seed,$seed+$pairs-1) as $s)foreach([1,2] as $first)$games[]=cerStudyGame($deck,$opponent,$s,$first);
        $record['opponents'][$opponent]=['summary'=>cerStudySummary($games),'games'=>$games];
        $summary=$record['opponents'][$opponent]['summary'];unset($summary['cards']);echo json_encode(['variant'=>$name,'opponent'=>$opponent]+$summary)."\n";
    }
    $out['variants'][$name]=$record;
    if(isset($options['out']))file_put_contents($options['out'],json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
}
