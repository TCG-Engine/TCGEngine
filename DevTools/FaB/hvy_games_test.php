<?php
require_once __DIR__.'/hvy_rules_test.php';
$failures=[];
foreach([['kayo','victor_goldmane'],['betsy','kassai','olympia','victor_goldmane']] as $heroes){
 $n=count($heroes);$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260917);$bots=[];
 foreach($heroes as $i=>$id){$p=$i+1;foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
  AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
  $weapon=match($id){'kayo'=>'ball_breaker','victor_goldmane'=>'millers_grindstone','betsy'=>'high_riser',default=>'hot_streak'};
  AddWeapons($p,CardID:$weapon,Owner:$p,Controller:$p);
  foreach(['glory_seeker','raw_meat','gauntlet_of_might','flat_trackers'] as $eq)AddEquipment($p,CardID:$eq,Owner:$p,Controller:$p);
  $base=match($id){'kayo'=>['pound_town_red','pound_town_blue','clash_of_might_red','clash_of_might_blue','mighty_windup_blue','wage_might_red','bonebreaker_bellow_red'],
   'betsy','victor_goldmane'=>['wage_vigor_red','wage_vigor_blue','clash_of_vigor_red','clash_of_vigor_blue','thunk_red','thunk_blue','bigger_than_big_red'],
   default=>['wage_agility_red','wage_agility_blue','clash_of_agility_red','clash_of_agility_blue','agile_windup_blue','draw_swords_red','edge_ahead_red']};
  $pool=array_merge($base,['wage_gold_red','wage_gold_blue','test_of_strength_red','money_where_ya_mouth_is_red','starting_stake_yellow','clash_of_might_yellow']);
  for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
 }
 $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$wagers=0;$beats=0;$clashes=0;
 for($step=0;$step<10000&&!intval(GetWinner());++$step){
  $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No HVY pending bot.');$GLOBALS['playerID']=$p;$d=GetDecisionQueue($p)[0]??null;
  if($d&&str_contains((string)$d->Tooltip,'Wager_with_defending'))++$wagers;
  if($d&&str_contains((string)$d->Tooltip,'beat_chest'))++$beats;
  foreach(FaBLiveSeats() as $seat)$clashes=max($clashes,FaBHVYCount($seat,'CLASH_WINS'));
  if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('HVY bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
 }
 $check(intval(GetWinner())>0,'HVY game exceeded action limit.');$check($wagers>0&&$clashes>0,'HVY fixture missed wagers or clashes.');if($n===2)$check($beats>0,'Kayo fixture missed Beat Chest.');echo implode('/',$heroes).": $step actions; $wagers wager choices; $beats Beat Chest choices; max $clashes clash wins in a turn.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
