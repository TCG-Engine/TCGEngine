<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function prizeCheck($ok,$message){global $checks;++$checks;if(!$ok)throw new RuntimeException($message);}
function prizeField(int $seat,string $zone,string $id,int $damage=0,array $energy=[]){$c=PokeAdd($seat,$zone,$id);$c->Controller=$seat;$c->Damage=$damage;$c->Energy=$energy;return $c;}
function prizeBoard(string $active='me05-039',int $remaining=6):void{
    InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;
    SetCurrentPhase('MAIN');SetTurnPlayer(1);SetFirstPlayer(2);SetTurnNumber(5);
    foreach([1,2] as $seat){AddPlayerTurns($seat,3);AddSetupReady($seat,true);for($i=0;$i<($seat===1?$remaining:6);$i++)PokeAdd($seat,'Prizes','mee-005');for($i=0;$i<15;$i++)PokeAdd($seat,'Deck','mee-005');}
    prizeField(1,'Active','me05-039',0,['mee-005']);prizeField(2,'Active',$active);
    prizeField(1,'Bench','me05-039',0,['mee-005']);
    for($i=0;$i<4;$i++)PokeAdd(1,'Discard','me05-006');
    PokeAdd(1,'Hand','me02.5-183');PokeAdd(1,'Hand','me02.5-192');
}
prizeBoard();prizeField(2,'Bench','me03-062');
$a=PokeBotChoose(PokeObservation(1));prizeCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-183','Gust two-prize KO even when Active is already a one-prize KO');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(1));prizeCheck(GetZoneObject($a['value'])->CardID==='me03-062','Chooser follows the same prize plan');PokeApplyAction($a);
prizeCheck(GetZoneObject('p2Active-0')->CardID==='me03-062','Actual Boss macro makes chosen target Active');
prizeBoard('me05-039',3);prizeField(2,'Bench','me03-062');prizeField(2,'Bench','me02-084',200);
PokeApplyAction(PokeBotChoose(PokeObservation(1)));$a=PokeBotChoose(PokeObservation(1));prizeCheck(GetZoneObject($a['value'])->CardID==='me02-084','Three-prize Mega KO is the game-winning route, ahead of two-prize ex');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(1));prizeCheck($a['type']==='attack','Follow through on winning gust instead of shuffling hand');PokeApplyAction($a);
prizeCheck(GetWinner()===1,'Winning prize map resolves victory through engine rules');
prizeBoard('me03-062',2);prizeField(2,'Bench','me02-084',200);
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'Keep Boss when current Active already supplies the final two prizes');
prizeCheck(PokeBotChoose(PokeObservation(1))['type']==='attack','Take current winning attack ahead of Lillie or reserve setup');
prizeBoard();prizeField(2,'Bench','me05-005');
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'Do not spend Boss on an equal one-prize KO for no benefit');
prizeBoard();prizeField(2,'Bench','sv05-129',0,['mee-005']);
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))>0,'Equal-prize KO can remove an established draw engine');
prizeBoard();GetZoneObject('p1Active-0')->Conditions['Asleep']=true;prizeField(2,'Bench','me03-062');
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'No speculative Boss while attacker is asleep');
prizeBoard();GetZoneObject('p1Active-0')->Energy=[];prizeField(2,'Bench','me03-062');
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'No Boss without payable attack');
prizeBoard('me02-084');prizeField(2,'Bench','sv09-056');
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'Preserve Boss on a speculative two-hit route even with a powered replacement');
prizeBoard('me02-084');prizeField(1,'Bench','me05-039',0,['mee-005']);prizeField(2,'Bench','sv09-056',0,['mee-005','mee-005']);
GetZoneObject('p1Active-0')->Damage=100;$prizes=&GetPrizes(2);$prizes=array_slice($prizes,0,1);
prizeCheck(PokeBotBossScore(PokeBotContext(PokeObservation(1)))<0,'Avoid two-hit gust that visibly gives opponent their final prize on the return attack');
prizeBoard('me02-084',2);prizeField(2,'Bench','me03-062');$before=PokeBotChoose(PokeObservation(1));
PokeAdd(2,'Hand','me01-132');$deck=&GetDeck(2);$deck=array_reverse($deck);GetZoneObject('p2Prizes-0')->CardID='me02-084';
prizeCheck(PokeBotChoose(PokeObservation(1))===$before,'Hidden opposing hand, deck and prize identities do not affect the map');
prizeBoard('me05-039',2);prizeField(2,'Bench','me03-062');$hand=&GetHand(1);$hand=[];
PokeAdd(1,'Hand','sv08-165');AddPlayerTurns(1,1);PokeAdd(1,'Deck','me02.5-183');PokeAdd(1,'Deck','me02.5-192');
PokeApplyAction(PokeBotChoose(PokeObservation(1)));$a=PokeBotChoose(PokeObservation(1));
prizeCheck(GetZoneObject($a['value'])->CardID==='me02.5-183','Call Bell searches Boss for the winning prize route rather than draw');
// Seed 1's regression: an ordinary gust spent the Supporter before recovery
// and Lillie could supply Energy for the replacement attacker.
prizeBoard('me02-084');$bench=&GetBench(1);$bench=[];
prizeField(2,'Bench','sv07-118');PokeAdd(1,'Discard','me05-039');PokeAdd(1,'Hand','me02.5-196');
$a=PokeBotChoose(PokeObservation(1));
prizeCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-196','Recover backup Dhelmise before an ordinary Boss KO');
PokeApplyAction($a);PokeApplyAction(PokeBotChoose(PokeObservation(1)));PokeApplyAction(PokeBotChoose(PokeObservation(1)));
$a=PokeBotChoose(PokeObservation(1));
prizeCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-192','Draw to power the replacement before spending Supporter on Boss');
PokeApplyAction($a);$a=PokeBotChoose(PokeObservation(1));
prizeCheck($a['type']==='attach'&&$a['target']==='p1Bench-0','Follow setup draw with Energy on the replacement Dhelmise');
echo "PASS $checks prize-plan checks\n";
