<?php
/** CLI-only paired slot ablations and package experiments. No registered list is changed. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
function relicanthExperimentBlank(int $slot): string {
    $id='experiment-blank-'.$slot;
    // Process-local dictionaries/registries only: this ID cannot be imported in normal play.
    $GLOBALS['nameData'][$id]='Experiment blank '.$slot;
    $GLOBALS['typeData'][$id]='Trainer';$GLOBALS['trainerTypeData'][$id]='Item';
    $GLOBALS['trainerPlayedAbilities'][$id.':0']=static function($player){};
    $GLOBALS['trainerPlayedPrereqs'][$id.':0']=static fn($player,$ref)=>false;
    $GLOBALS['CardTrainerPlayedCountData'][$id]=1;
    return $id;
}
function relicanthExperimentDeck(array $base,array $changes): array {
    $slots=[];foreach($base as $e)for($i=0;$i<$e['count'];++$i)$slots[]=$e['id'];
    $adds=[];foreach($changes as $id=>$delta)if($delta>0)for($i=0;$i<$delta;++$i)$adds[]=$id;
    $holes=[];foreach($changes as $id=>$delta)if($delta<0){
        $found=0;foreach($slots as $i=>$card)if($card===$id&&$found<-$delta){$holes[]=$i;++$found;}
        if($found!==-$delta)throw new InvalidArgumentException('Cut exceeds count: '.$id);
    }
    if(count($adds)>count($holes))throw new InvalidArgumentException('Additions exceed cuts');
    foreach($holes as $i=>$slot)$slots[$slot]=$adds[$i]??relicanthExperimentBlank($slot);
    $deck=array_map(fn($id)=>['id'=>$id,'count'=>1],$slots);
    $errors=PokeValidateDeck($deck);if($errors)throw new InvalidArgumentException(implode('; ',$errors));
    return $deck;
}
/** Tested opponents deal a single hit to the Active. Count Helmet's draw from
 * that damage event, rather than including the next turn's automatic draw. */
function relicanthExperimentHelmetDrawCount(int $beforeDeck,int $attackTurn,string $activeID,array $events): int {
    foreach(array_reverse($events) as $event)
        if(($event['event']??'')==='attack-damage'&&($event['player']??0)===2
            &&($event['turn']??0)===$attackTurn&&($event['target']??'')===$activeID&&($event['amount']??0)>0)
            return min(2,$beforeDeck);
    return 0;
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__)return;
try {
$opt=getopt('',['plan:','variant:','opponent:','pairs:','seed:','output:']);
foreach(['plan','variant','opponent','pairs','seed','output'] as $key)
    if(!isset($opt[$key])||!is_string($opt[$key])||$opt[$key]==='')throw new InvalidArgumentException('Required option: --'.$key);
$pairs=filter_var($opt['pairs'],FILTER_VALIDATE_INT);$seed=filter_var($opt['seed'],FILTER_VALIDATE_INT);
if($pairs===false||$pairs<1||$pairs>10000||$seed===false||$seed<1||$seed>2147483647-$pairs+1)throw new InvalidArgumentException('Invalid pairs/seed range');
if(!isset(PokeDeckRegistry()[$opt['opponent']]))throw new InvalidArgumentException('Unknown opponent');
if(!is_file($opt['plan'])||file_exists($opt['output'])||!is_dir(dirname($opt['output'])))throw new InvalidArgumentException('Provide an existing plan and a new output file in an existing directory');
$plan=json_decode(file_get_contents($opt['plan']),true,512,JSON_THROW_ON_ERROR);
if(!isset($plan['baseline'],$plan['variants'][$opt['variant']]))throw new InvalidArgumentException('Invalid plan/variant');
$name=$opt['variant'];$changes=$plan['variants'][$name];$base=PokeParseDeckText($plan['baseline']);
$deck=relicanthExperimentDeck($base,$changes);$rows=[];$start=microtime(true);$policy=PokeSimulationPolicy();$subjectKey=PokeDetectDeck($base);
for($i=0;$i<(int)$opt['pairs'];++$i)foreach([1,2] as $first){
    $bulwarkTurn=null;$maxBastiodon=0;$zeroReasons=[];$seenTurns=[];$pendingRecovery=null;
    $consistency=['laceyPlays'=>0,'laceyLatePlays'=>0,'laceyCardsDrawn'=>0,'irisPlays'=>0,'irisCardsDrawn'=>0,
        'helmetPlays'=>0,'helmetCardsDrawn'=>0,'relicanthAttacks'=>0,'fiveFossilAttacks'=>0];
    $previousDeckCount=null;$previousHelmet=false;$pendingIris=null;$previousTurn=0;$previousActiveID='';
    $usage=['retrievalPlays'=>0,'retrievalLatePlays'=>0,'retrievalEnergyReturned'=>0,'retrievalLateEnergyReturned'=>0,
        'retrievalOneEnergy'=>0,'retrievalTwoEnergy'=>0,
        'stretcherPlays'=>0,'stretcherEnergyReturned'=>0,'energySearchPlays'=>0];
    $observe=static function($action)use(&$bulwarkTurn,&$maxBastiodon,&$zeroReasons,&$seenTurns,&$pendingRecovery,&$usage,&$consistency,&$previousDeckCount,&$previousHelmet,&$pendingIris,&$previousTurn,&$previousActiveID,$first){
        $deckCount=PokeCount(1,'Deck');
        if(($action['player']??0)===2&&$action['type']==='attack'&&$previousHelmet&&$previousDeckCount!==null)
            $consistency['helmetCardsDrawn']+=relicanthExperimentHelmetDrawCount($previousDeckCount,$previousTurn,$previousActiveID,PokeVar('log',[]));
        if(($action['player']??0)===1){
            if($action['type']==='trainer'){
                // Trainer sources have left Hand by this post-action callback.
                $events=PokeVar('log',[]);$event=$events?end($events):[];
                if(($event['event']??'')!=='play-trainer'||($event['player']??0)!==1)throw new RuntimeException('Missing accepted Trainer diagnostic');
                $id=$event['card'];$ownTurn=GetPlayerTurns(1);
                if($id==='sv07-139'){
                    ++$consistency['laceyPlays'];if(PokeCount(2,'Prizes')<=3)++$consistency['laceyLatePlays'];
                    $consistency['laceyCardsDrawn']+=PokeCount(1,'Hand');
                }
                if($id==='me02.5-190'){++$consistency['irisPlays'];$pendingIris=$deckCount;}
                if($id==='sv06-158')++$consistency['helmetPlays'];
                if($id==='sv10.5w-082'){++$usage['retrievalPlays'];if($ownTurn>=4)++$usage['retrievalLatePlays'];}
                if($id==='me02.5-196')++$usage['stretcherPlays'];
                if($id==='me03-072')++$usage['energySearchPlays'];
                if(in_array($id,['sv10.5w-082','me02.5-196'],true))$pendingRecovery=['id'=>$id,'turn'=>$ownTurn,
                    'energyCount'=>count(array_filter(PokeObjects(1,'Hand'),fn($c)=>CardType($c->CardID)==='Energy'&&CardEnergyType($c->CardID)==='Normal'))];
            }elseif($action['type']==='decision'&&$pendingRecovery!==null){
                // Recovery changes only Hand cardinality; zone indices compact after the chooser.
                $returned=count(array_filter(PokeObjects(1,'Hand'),fn($c)=>CardType($c->CardID)==='Energy'&&CardEnergyType($c->CardID)==='Normal'))-$pendingRecovery['energyCount'];
                if($pendingRecovery['id']==='sv10.5w-082'){
                    $usage['retrievalEnergyReturned']+=$returned;if($returned===1)++$usage['retrievalOneEnergy'];if($returned===2)++$usage['retrievalTwoEnergy'];
                    if($pendingRecovery['turn']>=4)$usage['retrievalLateEnergyReturned']+=$returned;
                }else $usage['stretcherEnergyReturned']+=$returned;
                $pendingRecovery=null;
            }
            if($action['type']==='decision'&&$pendingIris!==null){
                $consistency['irisCardsDrawn']+=max(0,$pendingIris-$deckCount);$pendingIris=null;
            }
            if($action['type']==='attack'){
                $active=PokeFirstRef(1,'Active');
                if($active!==''&&GetZoneObject($active)->CardID==='me05-017'){
                    ++$consistency['relicanthAttacks'];if(PokeFossilBenchCount(1)===5)++$consistency['fiveFossilAttacks'];
                }
            }
        }
        $previousDeckCount=$deckCount;$active=PokeFirstRef(1,'Active');
        $previousHelmet=$active!==''&&GetZoneObject($active)->Tool==='sv06-158';
        $previousActiveID=$active!==''?GetZoneObject($active)->CardID:'';$previousTurn=GetTurnNumber();
        $n=count(array_filter(PokeObjects(1,'Bench'),fn($c)=>$c->CardID==='me05-062'));
        if($n&&$bulwarkTurn===null)$bulwarkTurn=GetPlayerTurns(1);
        $maxBastiodon=max($maxBastiodon,$n);
        $turns=PokeVar('damageTurns',[]);$turn=$turns?end($turns):null;
        // End-turn actions may already have opened the opponent's new row.
        if($turn&&$turn['player']!==1&&count($turns)>1)$turn=$turns[count($turns)-2];
        if(!$turn||$turn['player']!==1||!$turn['complete']||isset($seenTurns[$turn['turn']]))return;
        $seenTurns[$turn['turn']]=true;
        if($turn['damage']>0||$turn['turn']>7||($turn['turn']===1&&$first===1))return;
        $ref=PokeFirstRef(1,'Active');$active=$ref===''?null:GetZoneObject($ref);
        $reason='other';
        if(!$active||$active->CardID!=='me05-017')$reason='noActiveRelicanth';
        elseif(!PokeHasAttackEnergy($active,PokeAttackCost($active,CardAttacks($active->CardID)[0]['cost'])))$reason='missingAttachedEnergy';
        $zeroReasons[$turn['turn']]=$reason;
    };
    $r=PokeSimulateGame((int)$opt['seed']+$i,$first,1500,$subjectKey,$opt['opponent'],$deck,null,$observe);
    $eligible=array_values(array_filter($r['damageTurns'],fn($t)=>$t['player']===1&&$t['turn']<=7&&($t['turn']>1||$first===2)));
    $r['eligibleTurns']=count($eligible);$r['zeroTurns']=count(array_filter($eligible,fn($t)=>$t['damage']===0));
    $r['earlyDamage']=array_sum(array_column($eligible,'damage'));
    $r['damageByTurn']=array_map(fn($t)=>['turn'=>$t['turn'],'damage'=>$t['damage']],$eligible);
    $r['zeroReasons']=$zeroReasons;
    $r['resourceUsage']=$usage;
    $r['consistencyUsage']=$consistency;
    $r['bastiodonEvolved']=$bulwarkTurn!==null;$r['bulwarkTurn']=$bulwarkTurn;$r['maxBenchedBastiodon']=$maxBastiodon;
    unset($r['openingStats'],$r['damageTurns']);$rows[]=$r;
}
if(PokeSimulationPolicy()!==$policy)throw new RuntimeException('Runtime policy changed during experiment');
$out=['variant'=>$name,'changes'=>$changes,'opponent'=>$opt['opponent'],'pairs'=>(int)$opt['pairs'],'seed'=>(int)$opt['seed'],
    'policy'=>$policy,'harnessHash'=>hash_file('sha256',__FILE__),'consistencyMetricsVersion'=>2,'baselineHash'=>hash('sha256',$plan['baseline']),'seconds'=>microtime(true)-$start,'games'=>$rows];
if(file_exists($opt['output']))throw new RuntimeException('Output already exists');
file_put_contents($opt['output'],json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo $name.' / '.$opt['opponent'].': '.count($rows).' games in '.round($out['seconds'],1)."s\n";
} catch(Throwable $error) {fwrite(STDERR,$error->getMessage()."\n");exit(1);}
