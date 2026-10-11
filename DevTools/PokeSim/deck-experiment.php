<?php
/** Fixed-policy, reproducible card-count experiments. Does not alter registered decks. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';

function PokeExperimentVariants(string $base='sinistcha',string $suite='default'): array {
    if($suite==='call-bell'){
        if($base!=='dhelmise-v2')throw new InvalidArgumentException('Call Bell suite requires dhelmise-v2');
        return [
            'baseline'=>[],
            'bell-to-ultra'=>['sv08-165'=>-1,'me02.5-213'=>1],
            'two-bell-to-ultra'=>['sv08-165'=>-2,'me02.5-213'=>2],
            'bell-to-meowth'=>['sv08-165'=>-1,'me03-062'=>1],
            'two-bell-meowth-ultra'=>['sv08-165'=>-2,'me03-062'=>1,'me02.5-213'=>1],
            'three-bell-meowth-two-ultra'=>['sv08-165'=>-3,'me03-062'=>1,'me02.5-213'=>2],
            'four-bell-meowth-two-ultra-energy'=>['sv08-165'=>-4,'me03-062'=>1,'me02.5-213'=>2,'mee-005'=>1],
            'bell-to-retrieval'=>['sv08-165'=>-1,'sv10.5w-082'=>1],
            'two-bell-ultra-retrieval'=>['sv08-165'=>-2,'me02.5-213'=>1,'sv10.5w-082'=>1],
            'four-bell-two-ultra-two-energy'=>['sv08-165'=>-4,'me02.5-213'=>2,'mee-005'=>2],
        ];
    }
    if($suite!=='default')throw new InvalidArgumentException('Unknown experiment suite');
    if($base==='dhelmise-v2')return [
        'baseline'=>[],
        'belt-to-meowth'=>['sv09-144'=>-1,'me03-062'=>1],
        'retrieval-to-meowth'=>['sv10.5w-082'=>-1,'me03-062'=>1],
        'belt-meowth-ultra-pad'=>['sv09-144'=>-1,'me03-062'=>1,'me02.5-213'=>-1,'me03-081'=>1],
        'belt-to-gear'=>['sv09-144'=>-1,'sv10.5b-084'=>1],
        'belt-to-pad'=>['sv09-144'=>-1,'me03-081'=>1],
        'belt-to-energy'=>['sv09-144'=>-1,'mee-005'=>1],
        'belt-to-retrieval'=>['sv09-144'=>-1,'sv10.5w-082'=>1],
        'ultra-to-pad'=>['me02.5-213'=>-1,'me03-081'=>1],
        'two-ultra-to-pad'=>['me02.5-213'=>-2,'me03-081'=>2],
        'belt-gear-two-ultra-pad'=>['sv09-144'=>-1,'sv10.5b-084'=>1,'me02.5-213'=>-2,'me03-081'=>2],
        'belt-gear-ultra-pad'=>['sv09-144'=>-1,'sv10.5b-084'=>1,'me02.5-213'=>-1,'me03-081'=>1],
        'belt-pad-ultra-pad'=>['sv09-144'=>-1,'me03-081'=>2,'me02.5-213'=>-1],
        'belt-energy-ultra-pad'=>['sv09-144'=>-1,'mee-005'=>1,'me02.5-213'=>-1,'me03-081'=>1],
        'belt-retrieval-ultra-pad'=>['sv09-144'=>-1,'sv10.5w-082'=>1,'me02.5-213'=>-1,'me03-081'=>1],
    ];
    $variants=['baseline'=>[]];
    $cuts=['retrieval'=>'sv10.5w-082','energy-search'=>'me03-072','stretcher'=>'me02.5-196',
        'transceiver'=>'me02.5-209','call-bell'=>'sv08-165','petrel'=>'me02.5-207'];
    $adds=['gear'=>'sv10.5b-084','pad'=>'me03-081','lillie'=>'me02.5-192'];
    foreach($cuts as $cut=>$cutID)foreach($adds as $add=>$addID)
        $variants[$cut.'-to-'.$add]=[$cutID=>-1,$addID=>1];
    return $variants+[
        'retrieval-to-energy'=>['sv10.5w-082'=>-1,'mee-005'=>1],
        'energy-search-to-energy'=>['me03-072'=>-1,'mee-005'=>1],
        'energy-search-to-retrieval'=>['me03-072'=>-1,'sv10.5w-082'=>1],
        'retrieval-to-banette'=>['sv10.5w-082'=>-1,'me05-034'=>1],
        'sinistcha-to-banette'=>['me05-006'=>-1,'me05-034'=>1],
        'poltchageist-to-banette'=>['me05-005'=>-1,'me05-034'=>1],
        'two-retrieval-to-gear-pad'=>['sv10.5w-082'=>-2,'sv10.5b-084'=>1,'me03-081'=>1],
        'retrieval-search-to-gear-pad'=>['sv10.5w-082'=>-1,'me03-072'=>-1,'sv10.5b-084'=>1,'me03-081'=>1],
        'retrieval-stretcher-to-gear-pad'=>['sv10.5w-082'=>-1,'me02.5-196'=>-1,'sv10.5b-084'=>1,'me03-081'=>1],
        'retrieval-search-to-lillie-pad'=>['sv10.5w-082'=>-1,'me03-072'=>-1,'me02.5-192'=>1,'me03-081'=>1],
        'retrieval-transceiver-to-lillie-gear'=>['sv10.5w-082'=>-1,'me02.5-209'=>-1,'me02.5-192'=>1,'sv10.5b-084'=>1],
    ];
}
function PokeExperimentDeck(array $changes,string $base='sinistcha',string $suite='default'): array {
    if(!in_array($suite,['default','call-bell'],true)||$suite==='call-bell'&&$base!=='dhelmise-v2')throw new InvalidArgumentException('Invalid experiment suite/base');
    foreach($base==='dhelmise-v2'?['me04-082']:['me04-082','sv09-144'] as $protected)
        if(($changes[$protected]??0)!==0)throw new InvalidArgumentException(CardName($protected).' is fixed at one copy in this experiment suite');
    // Freeze the historical v2 experiment baseline when the selector list changes.
    $deck=$base==='dhelmise-v2'
        ?PokeParseDeckText(file_get_contents(__DIR__.'/fixtures/'.($suite==='call-bell'?'dhelmise-call-bell-baseline.txt':'dhelmise-v2-experiment-baseline.txt')))
        :PokeNamedDeck($base);
    // Preserve baseline entry order. The same seeds are a paired comparison,
    // but changing counts can change opening deals and mulligans.
    foreach($changes as $id=>$delta){
        $found=false;
        foreach($deck as &$entry)if($entry['id']===$id){$entry['count']+=$delta;$found=true;break;}
        unset($entry);
        if(!$found){if($delta<0)throw new InvalidArgumentException('Cannot remove absent card');$deck[]=['id'=>$id,'count'=>$delta];}
    }
    foreach($deck as $entry)if($entry['count']<0)throw new InvalidArgumentException('Negative card count');
    $deck=array_values(array_filter($deck,fn($e)=>$e['count']>0));
    $errors=PokeValidateDeck($deck);if($errors)throw new InvalidArgumentException(implode('; ',$errors));
    return $deck;
}
/** Accepted Trainer actions, measured before compacting private opening traces. */
function PokeExperimentBlenderMilestones(array $trace): array {
    $milestones=['blenderTurn1Played'=>false,'blenderByOpportunityPlayed'=>false];
    foreach($trace as $event)if(($event['source']??null)==='sv08-164'&&($event['type']??null)==='trainer'){
        $milestones['blenderByOpportunityPlayed']=true;
        if(($event['turn']??null)===1)$milestones['blenderTurn1Played']=true;
    }
    return $milestones;
}
/** Diagnostic usage only; variant comparisons estimate the benefit of the slots. */
function PokeExperimentAccessMilestones(array $trace): array {
    $out=['callBellTurn1Plays'=>0,'openingSearches'=>[]];$source=null;
    $searchCards=['sv08-165','sv10.5b-084','me02.5-207','me02.5-209','me03-062'];
    foreach($trace as $event){
        if(in_array($event['type']??'', ['trainer','bench'],true)){
            $source=in_array($event['source']??'', $searchCards,true)?$event['source']:null;
            if(($event['type']??'')==='trainer'&&$source==='sv08-165'&&($event['turn']??0)===1)++$out['callBellTurn1Plays'];
        }
        if(($event['type']??'')==='decision'&&$source!==null){
            $out['openingSearches'][$source][]=['turn'=>$event['turn'],'choices'=>$event['choices']??[]];
            $source=null;
        }
    }
    return $out;
}
if(realpath($_SERVER['SCRIPT_FILENAME'] ?? '')!==__FILE__)return;
try{
    $options=getopt('',['base:','suite:','variant:','pairs:','seed:','opponent:','output:']);
    $base=$options['base']??'sinistcha';if(!in_array($base,['sinistcha','dhelmise-v2'],true))throw new InvalidArgumentException('Unknown base');
    $suite=$options['suite']??'default';
    $name=$options['variant']??'baseline';$variants=PokeExperimentVariants($base,$suite);
    if(!isset($variants[$name]))throw new InvalidArgumentException('Unknown variant: '.implode(', ',array_keys($variants)));
    $pairs=filter_var($options['pairs']??100,FILTER_VALIDATE_INT);$seed=filter_var($options['seed']??1001,FILTER_VALIDATE_INT);
    if($pairs===false||$pairs<1||$pairs>10000||$seed===false||$seed<1||$seed>2147483647-$pairs+1)throw new InvalidArgumentException('Invalid pairs/seed range');
    $path=$options['output']??null;
    if(!$path||file_exists($path))throw new InvalidArgumentException('Provide --output with a new JSON filename');
    $deck=PokeExperimentDeck($variants[$name],$base,$suite);$opponent=$options['opponent']??'brisbane-lopunny';
    $policy=PokeSimulationPolicy();$rows=[];$started=microtime(true);
    for($i=0;$i<$pairs;++$i){
        foreach([1,2] as $first){
            $row=PokeSimulateGame($seed+$i,$first,1500,$base,$opponent,$deck);
            unset($row['damageTurns']);
            foreach($row['openingStats'] as &$opening){
                $opening+=PokeExperimentBlenderMilestones($opening['trace']??[]);
                $opening+=PokeExperimentAccessMilestones($opening['trace']??[]);
                unset($opening['trace'],$opening['snapshot'],$opening['startSnapshot'],$opening['milestones']);
            }
            unset($opening);$rows[]=$row;
        }
        if(($i+1)%25===0)fwrite(STDERR,$name.' '.($i+1).'/'.$pairs." pairs\n");
    }
    $swaps=[];foreach($variants[$name] as $id=>$delta)$swaps[]=['card'=>CardName($id),'id'=>$id,'delta'=>$delta];
    $out=['base'=>$base,'suite'=>$suite,'variant'=>$name,'changes'=>$swaps,'policy'=>$policy,'opponent'=>$opponent,'seed'=>$seed,'pairs'=>$pairs,
        'deck'=>$deck,'elapsedSeconds'=>round(microtime(true)-$started,2),'summary'=>PokeSimulationSummary($rows),'results'=>$rows];
    $file=fopen($path,'x');if(!$file)throw new RuntimeException('Cannot create output file');
    fwrite($file,json_encode($out,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");fclose($file);
    echo json_encode(['variant'=>$name,'seconds'=>$out['elapsedSeconds'],'summary'=>$out['summary']],JSON_THROW_ON_ERROR)."\n";
    exit($out['summary']['incomplete']?2:0);
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
