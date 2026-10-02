<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function expect($condition,string $message): void {global $checks;++$checks;if(!$condition)throw new RuntimeException($message);}
function field(int $seat,string $zone,string $id) {$obj=PokeAdd($seat,$zone,$id);$obj->Controller=$seat;$obj->EnteredTurn=1;return $obj;}
function board(string $own='me02-084',string $enemy='me02-084'): void {
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetTurnNumber(4);SetTurnPlayer(1);SetFirstPlayer(2);SetCurrentPhase('MAIN');SetRandomState(42);
    foreach([1,2] as $p){AddPlayerTurns($p,2);AddSetupReady($p,true);for($i=0;$i<6;++$i)PokeAdd($p,'Prizes','mee-005');for($i=0;$i<20;++$i)PokeAdd($p,'Deck','mee-005');}
    field(1,'Active',$own)->Energy=['sv05-161'];field(2,'Active',$enemy)->Energy=['sv05-161'];
    PokeSetVar('deckKey:1','brisbane-lopunny');PokeSetVar('deckKey:2','brisbane-lopunny');
}
function act(string $type,string $source,array $extra=[]): void {PokeApplyAction(['type'=>$type,'source'=>$source,'player'=>1]+$extra);}
function answerCard(string $id): void {
    $d=PokeDecisionOptions(1);foreach($d['choices'] as $c)if($c['card']===$id){PokeAnswerDecision(1,$c['value']);return;}
    throw new RuntimeException('Missing chooser target '.$id);
}
$deck=PokeNamedDeck('brisbane-lopunny');expect(array_sum(array_column($deck,'count'))===60&&!PokeValidateDeck($deck),'Entire exported deck is implemented');
expect(count($deck)===28,'All 28 deck printings resolved');
foreach($deck as $e)expect(is_file(dirname(__DIR__,2).'/PokeSim/WebpImages/'.$e['id'].'.webp'),'Art for '.$e['id']);
expect(PokePrizeValue('me02-084')===3,'Mega ex grants three Prizes');
board();act('attack','p1Active-0',['index'=>0]);expect(GetZoneObject('p2Active-0')->Damage===60,'Gale Thrust base damage');
board('30th-066');$lop=field(1,'Bench','me02-084');$lop->Energy=['sv05-161'];PokeSwitch(1,'p1Bench-0');PokeCompact();act('attack','p1Active-0',['index'=>0]);expect(GetZoneObject('p2Active-0')->Damage===230,'Switching enables Gale Thrust');
board('sv09-121');field(2,'Bench','me03-062');act('attack','p1Active-0',['index'=>0]);expect(GetZoneObject('p2Active-0')->Damage===120,'Tenacious Tail counts opposing ex including Mega');
board('sv09-056');field(1,'Bench','sv09-120');field(2,'Bench','me02-083');GetZoneObject('p1Active-0')->Energy=['mee-005','sv05-161'];act('attack','p1Active-0',['index'=>0]);expect(GetZoneObject('p2Active-0')->Damage===60,'Full Moon Rondo counts both Benches');
board('30th-066');field(1,'Bench','me02-084');$a=array_values(array_filter(PokeLegalActions(1),fn($a)=>isset($a['copySource'])&&$a['index']===0));expect(count($a)===1,'Memory Helix exposes payable Bench attacks');PokeApplyAction($a[0]);expect(GetZoneObject('p2Active-0')->Damage===60,'Memory Helix dispatches authored copied attack');
board('me05-005');PokePlaceDamageCounters('p1Active-0','p2Active-0',4);expect(GetZoneObject('p2Active-0')->Damage===0,'Mist protects against effects');GetZoneObject('p2Active-0')->Counters['noAbilities']=true;PokeSetCondition('p1Active-0','p2Active-0','Poisoned');expect(!GetZoneObject('p2Active-0')->Conditions,'Mist protection survives ability suppression');PokeDealAttackDamage(1,'p1Active-0',20);expect(GetZoneObject('p2Active-0')->Damage===20,'Mist permits attack damage');
board();PokeAdd(1,'Hand','me02-085');act('trainer','p1Hand-0');expect(PokeStadium()->CardID==='me02-085'&&PokeCount(1,'Discard')===0,'Stadium moves into global play');
$bench=field(2,'Bench','sv09-120');PokePlaceDamageCounters('p1Active-0','p2Bench-0',4);expect($bench->Damage===0,'Battle Cage stops attack counters on Bench');PokePlaceDamageCounters('p1Active-0','p2Bench-0',4,'Ability');expect($bench->Damage===0,'Battle Cage stops ability counters on Bench');PokePlaceDamageCounters('p1Active-0','p1Active-0',1);expect(GetZoneObject('p1Active-0')->Damage===10,'Battle Cage does not stop self or Active counters');
PokeAdd(1,'Hand','me02-085');expect(!PokeCanPlayTrainer(1,'p1Hand-0'),'Cannot replay same Stadium or a second Stadium in one turn');
$snapshot=PokeStateExport();PokeStateImport(json_decode(json_encode($snapshot),true));expect(PokeStadium()->CardID==='me02-085','Global Stadium survives serialization');
board();field(2,'Bench','sv09-120');field(2,'Bench','sv10-010');PokeDealAttackDamage(1,'p1Active-0',50,false,'p2Bench-0');expect(GetZoneObject('p2Bench-0')->Damage===0,'Flower Curtain prevents Bench damage to non rule box');PokeDealAttackDamage(1,'p1Active-0',50,true,'p2Bench-0');expect(GetZoneObject('p2Bench-0')->Damage===50,'Ignoring effects bypasses Flower Curtain');
board();PokeAdd(1,'Hand','me02.5-181');act('trainer','p1Hand-0');$snapshot=PokeStateExport();PokeStateImport($snapshot);answerCard('me02-084');expect(GetZoneObject('p1Active-0')->Tool==='me02.5-181'&&PokeRetreatCost(GetZoneObject('p1Active-0'))===0,'Air Balloon attachment and generated cost modifier');
board();GetZoneObject('p1Active-0')->Damage=170;PokeAdd(1,'Hand','me01-132');act('trainer','p1Hand-0');answerCard('me02-084');expect(GetZoneObject('p1Active-0')->Damage===0&&GetZoneObject('p1Active-0')->Energy===[]&&PokeCount(1,'Hand')===1,'Wally heals and returns Energy');
board();PokeAdd(1,'Hand','sv08-191');act('attach','p1Hand-0',['target'=>'p1Active-0']);expect(PokeCount(1,'Hand')===4,'Enriching Energy draws four');
board();$d=field(1,'Bench','sv05-129');$d->Evolutions=['sv09-120'];$d->Energy=['mee-005'];$d->Tool='me02.5-181';act('ability','p1Bench-0',['index'=>0]);expect(PokeCount(1,'Hand')===3&&PokeCount(1,'Bench')===0&&PokeCount(1,'Deck')===21,'Run Away Draw recycles entire stack after draw');
board();PokeAdd(1,'Hand','me03-062');PokeAdd(1,'Deck','sv10.5w-084');act('bench','p1Hand-0');expect(str_contains(PokeDecisionOptions(1)['prompt'],'Last-Ditch'),'Meowth triggers from hand');answerCard('sv10.5w-084');expect(PokeCount(1,'Hand')===1,'Last-Ditch Catch resolves');PokeAdd(1,'Hand','me03-062');act('bench','p1Hand-1');expect(PokeDecisionOptions(1)===null,'Last-Ditch Catch once per turn across copies');
board();PokeAdd(1,'Hand','me02-085');act('trainer','p1Hand-0');PokeAdd(1,'Hand','sv08-056');act('bench','p1Hand-0');PokeAnswerDecision(1,'YES');expect(PokeStadium()===null,'Snow Sink can remove a Stadium');
board();PokeAdd(1,'Deck','me02-084');PokeAdd(1,'Hand','sv10.5w-084');act('trainer','p1Hand-0');answerCard('me02-084');$snapshot=PokeStateExport();PokeStateImport($snapshot);answerCard('mee-005');expect(PokeCount(1,'Hand')===2,'Hilda searches Evolution and Energy across await snapshot');
board('sv07-118');AddPlayerTurns(1,1);PokeAdd(1,'Deck','me02-083');PokeAdd(1,'Deck','sv09-120');act('ability','p1Active-0',['index'=>0]);$chosen=implode('&',array_column(PokeDecisionOptions(1)['choices'],'value'));PokeAnswerDecision(1,$chosen);expect(PokeCount(1,'Hand')===2,'Fan Call finds small Colorless Pokémon');expect(!array_filter(PokeLegalActions(1),fn($a)=>$a['type']==='ability'),'Fan Call cannot repeat');
board();PokeAdd(1,'Deck','me02-083');PokeAdd(1,'Deck','sv09-120');PokeAdd(1,'Hand','sv05-144');act('trainer','p1Hand-0');PokeAnswerDecision(1,implode('&',array_column(PokeDecisionOptions(1)['choices'],'value')));expect(PokeCount(1,'Bench')===2,'Poffin benches up to two small Basics');
board('sv08-056');GetZoneObject('p1Active-0')->Energy=['swsh12.5-154','swsh12.5-154','sv05-161'];act('attack','p1Active-0',['index'=>0]);PokeAnswerDecision(1,'2');expect(count(GetZoneObject('p1Active-0')->Energy)===2&&PokeCount(1,'Hand')===1,'Icicle Loop returns one chosen Energy');
board('me03-062');GetZoneObject('p1Active-0')->Energy=['sv05-161','sv05-161','sv05-161'];field(1,'Bench','me02-083');act('attack','p1Active-0',['index'=>0]);expect(PokeCount(1,'Active')===0&&PokeCount(1,'Hand')===4&&PokeDecisionOptions(1),'Tuck Tail returns full stack and requires promotion');answerCard('me02-083');expect(GetTurnPlayer()===2,'Promotion precedes ending the attack turn');
// Policy priorities are asserted on actual legal actions rather than synthetic scores.
board('sv07-118');AddPlayerTurns(1,1);PokeAdd(1,'Hand','me02-083');expect(PokeBotChoose(PokeObservation(1))['type']==='ability','Bot prioritizes first-turn Fan Call');
board();GetZoneObject('p1Active-0')->Damage=170;PokeAdd(1,'Hand','me01-132');PokeAdd(1,'Hand','sv05-161');expect(PokeBotChoose(PokeObservation(1))['source']==='p1Hand-0','Bot heals before spending Energy attachment');
board();GetZoneObject('p1Active-0')->Tool='me02.5-181';$b=field(1,'Bench','me02-084');$b->Energy=['sv05-161'];expect(PokeBotChoose(PokeObservation(1))['type']==='retreat','Bot pivots into Gale Thrust bonus');
board();field(1,'Bench','sv09-120');PokeAdd(1,'Hand','sv05-129');PokeAdd(1,'Hand','sv09-121');expect(PokeBotChoose(PokeObservation(1))['source']==='p1Hand-0','Bot establishes draw evolution against low ex counts');
foreach([['brisbane-lopunny','sinistcha'],['sinistcha','brisbane-lopunny'],['brisbane-lopunny','brisbane-lopunny']] as [$a,$b]) foreach([1,2] as $first){$r=PokeSimulateGame(42,$first,1500,$a,$b);expect($r['status']==='complete',"$a vs $b first $first: ".$r['reason']);}
echo "PASS $checks Lopunny checks\n";

