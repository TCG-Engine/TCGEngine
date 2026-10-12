<?php
/** Reproducible engine openings and immediate follow-up, fixed bot for every deck. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
require dirname(__DIR__,2).'/PokeSim/Bot/Simulation.php';
function fanVariants(): array {
 $base=PokeParseDeckText(file_get_contents(__DIR__.'/fixtures/fan-rotom-baseline.txt'));
 $fixed=PokeParseDeckText(file_get_contents(__DIR__.'/fixtures/fan-rotom-requested.txt'));
 $changes=[
  'requested'=>[],
  'energy8'=>['mee-005'=>1,'me02.5-213'=>-1],
  'energy9'=>['mee-005'=>2,'me02.5-213'=>-2],
  'stadium7'=>['me02-085'=>1,'me02.5-213'=>-1],
  'stadium5'=>['sv06-153'=>-1,'mee-005'=>1],
  'no-jamming'=>['sv06-153'=>-2,'me02-085'=>1,'mee-005'=>1],
  'energy-search2'=>['sv06-153'=>-2,'me02-085'=>1,'mee-005'=>1,'sv05-144'=>-2,'me03-072'=>2],
  'gear2'=>['me02.5-213'=>-2,'sv10.5b-084'=>2],
  'gear4'=>['me02.5-213'=>-2,'sv05-144'=>-2,'sv10.5b-084'=>4],
  'gear2-energy8'=>['me02.5-213'=>-2,'sv05-144'=>-1,'sv10.5b-084'=>2,'mee-005'=>1],
  'dudunsparce3'=>['sv08.5-080'=>1,'me02.5-213'=>-1],
  'poffin4'=>['sv05-144'=>2,'me02.5-213'=>-2],
  'pad3-energy8'=>['me03-081'=>-1,'mee-005'=>1],
  'lopunny2-energy8'=>['me02-084'=>-1,'mee-005'=>1],
 ];
 $out=['original'=>$base];
 foreach($changes as $name=>$delta){$counts=array_column($fixed,'count','id');foreach($delta as $id=>$n)$counts[$id]=($counts[$id]??0)+$n;$out[$name]=[];foreach($counts as $id=>$n)if($n)$out[$name][]=['id'=>$id,'count'=>$n];}
 return $out;
}
function fanTrial(array $deck,int $seed,int $first,string $opponent): array {
 PokeCreateGame($deck,PokeNamedDeck($opponent),$seed,$first);
 $eligible=$first===1?2:1;$fan=false;$pivot=false;$buneary=false;$stadium=false;$steps=0;
 while(!GetWinner()&&$steps++<600){
  if(GetPlayerTurns(1)>$eligible+1)break;
  $seat=PokePendingPlayer();$view=PokeObservation($seat);$a=PokeBotChoose($view);
  if(!$a)throw new RuntimeException('No action');
  if($seat===1&&$a['type']==='attack'){
   $id=GetZoneObject($a['source'])->CardID;
   if(GetPlayerTurns(1)===$eligible){$stadium=PokeStadium()!==null;$fan=$id==='sv07-118'&&$stadium;$buneary=(bool)array_filter(PokeFieldRefs(1),fn($r)=>GetZoneObject($r)->CardID==='me02-083');}
   if(GetPlayerTurns(1)===$eligible+1)$pivot=$id==='me02-084'&&$a['index']===0&&PokeMovedToActiveThisTurn($a['source']);
  }
  PokeApplyAction($a);
 }
 if($steps>=600)throw new RuntimeException('Opening action cap');
 return ['fan'=>$fan,'fanWithBuneary'=>$fan&&$buneary,'pivot'=>$pivot,'fanThenPivot'=>$fan&&$pivot,'stadium'=>$stadium];
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==__FILE__)return;
$o=getopt('',['games:','seed:','variant:','output:','opponent:','screen']);$n=(int)($o['games']??100);$seed=(int)($o['seed']??1001);$out=[];
if($n<1||$n>10000||$seed<1)throw new InvalidArgumentException('Invalid games/seed');
foreach(fanVariants() as $name=>$deck){
 if(isset($o['screen'])&&$name==='energy-search2')continue;
 if(isset($o['variant'])&&$o['variant']!==$name)continue;
 if($errors=PokeValidateDeck($deck))throw new RuntimeException($name.': '.implode('; ',$errors));
 $row=['policy'=>PokeSimulationPolicy(),'deck'=>$deck,'gamesPerOrder'=>$n,'seed'=>$seed,'opponent'=>$o['opponent']??'brisbane-lopunny','orders'=>[]];
 foreach([1,2] as $first){$sum=array_fill_keys(['fan','fanWithBuneary','pivot','fanThenPivot','stadium'],0);for($i=0;$i<$n;$i++){foreach(fanTrial($deck,$seed+$i,$first,$row['opponent']) as $k=>$v)$sum[$k]+=(int)$v;}$row['orders'][$first===1?'first':'second']=$sum;}
 $out[$name]=$row;echo json_encode([$name,$row['orders']])."\n";
}
if(!$out)throw new InvalidArgumentException('Unknown variant');
if(isset($o['output']))file_put_contents($o['output'],json_encode($out,JSON_PRETTY_PRINT)."\n");
