<?php
require dirname(__DIR__,2).'/PokeSim/Runtime.php';
require dirname(__DIR__,2).'/PokeSim/Bot/HeuristicBot.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$checks=0;
function botCheck($condition,$message){global $checks;++$checks;if(!$condition)throw new RuntimeException($message);}
function botFixture():void {
    InitializeGamestate();$GLOBALS['playerID']=2;$GLOBALS['currentPlayer']=2;
    SetCurrentPhase('MAIN');SetTurnNumber(4);SetTurnPlayer(2);SetFirstPlayer(1);
    foreach([1,2] as $seat){AddSetupReady($seat,true);AddPlayerTurns($seat,2);$c=PokeAdd($seat,'Active','me05-039');$c->Controller=$seat;$c->Energy=['mee-005'];for($i=0;$i<6;++$i)PokeAdd($seat,'Prizes','mee-005');for($i=0;$i<15;++$i)PokeAdd($seat,'Deck','mee-005');}
}
botFixture();PokeAdd(2,'Hand','sv08-164');PokeAdd(2,'Deck','me05-034');for($i=0;$i<4;++$i)PokeAdd(2,'Deck','me05-006');PokeAdd(2,'Deck','me05-039');PokeAdd(2,'Deck','sv08-165');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='trainer','Bot sets up discard engine before attacking');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='decision'&&count(explode('&',$a['value']))===5,'Blender selects four HNS plus a recovery target');PokeApplyAction($a);botCheck(PokeCountHideSneak(2)===4,'Bot resolves Blender choices legally');
botCheck(in_array('me05-039',array_map(fn($c)=>$c->CardID,PokeObjects(2,'Discard')),true),'Blender seeds a needed backup Dhelmise');

// Exercise the actual search macros, not merely score comparisons.
botFixture();PokeAdd(2,'Hand','me02.5-207');PokeAdd(2,'Hand','me02.5-192');PokeAdd(2,'Deck','sv08-164');
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='me02.5-207','Petrel takes priority over Lillie when Blender is needed');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['value'])->CardID==='sv08-164','Petrel searches for Blender');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='sv08-164','Found Blender is played before draw or attacking');
foreach(['me02.5-209','sv08-165'] as $search){
    botFixture();AddPlayerTurns(2,1);PokeAdd(2,'Hand',$search);PokeAdd(2,'Hand','me02.5-192');PokeAdd(2,'Deck','me02.5-207');PokeAdd(2,'Deck','me02.5-192');PokeAdd(2,'Deck','sv08-164');
    $a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID===$search,"$search starts the Petrel search route before Lillie");PokeApplyAction($a);
    $a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['value'])->CardID==='me02.5-207',"$search searches Petrel rather than a draw Supporter");PokeApplyAction($a);
    $a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='me02.5-207',"$search follows through by playing Petrel");
}
function blenderFixture(bool $powered,bool $backup,array $hand=[]): array {
    botFixture();if(!$powered)GetZoneObject('p2Active-0')->Energy=[];
    if($backup){$c=PokeAdd(2,'Bench','me05-039');$c->Controller=2;$c->Energy=['mee-005'];}
    foreach($hand as $id)PokeAdd(2,'Hand',$id);
    PokeAdd(2,'Hand','sv08-164');PokeAdd(2,'Deck','me05-034');
    for($i=0;$i<4;++$i)PokeAdd(2,'Deck','me05-006');
    PokeAdd(2,'Deck','me05-039');PokeAdd(2,'Deck','sv08-165');PokeAdd(2,'Deck','me03-088');
    PokeApplyAction(PokeBotChoose(PokeObservation(2)));
    $a=PokeBotChoose(PokeObservation(2));
    $ids=array_map(fn($ref)=>GetZoneObject($ref)->CardID,explode('&',$a['value']));
    botCheck(count($ids)===5&&count(array_filter($ids,fn($id)=>in_array($id,['me05-005','me05-006','me05-034'],true)))===4,'Blender stops at four HNS and fills its fifth slot');
    return [$a,$ids];
}
[$a,$ids]=blenderFixture(false,false);botCheck(in_array('mee-005',$ids,true),'Unpowered attacker needs basic Energy as the fifth discard');PokeApplyAction($a);
PokeAdd(2,'Hand','me02.5-196');$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='me02.5-196','Bot uses Night Stretcher for the seeded Energy');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['value'])->CardID==='mee-005','Night Stretcher retrieves seeded Energy rather than HNS');PokeApplyAction($a);
botCheck(PokeCountHideSneak(2)===4,'Recovery preserves the four-HNS threshold');
[$a,$ids]=blenderFixture(true,false,['mee-005']);botCheck(in_array('me05-039',$ids,true),'Available Energy and missing backup prefer a Dhelmise discard');
[$a,$ids]=blenderFixture(true,true,['mee-005']);botCheck(in_array('sv08-165',$ids,true),'Established attackers and Energy use Call Bell to thin the deck');
botFixture();$peekDeck=&GetDeck(2);$peekDeck=[];
foreach(['me02.5-207','me02.5-192','sv08-164','mee-005','me05-039'] as $id)PokeAdd(2,'Deck',$id);
PokeAdd(2,'Hand','sv10.5b-084');PokeAdd(2,'Hand','me02.5-192');
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='sv10.5b-084','Pokégear is used before Lillie to look for Petrel');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['value'])->CardID==='me02.5-207','Pokégear selects revealed Petrel over Lillie');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['source'])->CardID==='me02.5-207','Pokégear route continues through Petrel');

// Candidate-limited plans respect existing progress and recovery access.
botFixture();PokeAdd(2,'Discard','me05-006');PokeAdd(2,'Discard','me05-006');
PokeAdd(2,'Hand','mee-005');PokeAdd(2,'Deck','me05-039');
$ctx=PokeBotContext(PokeObservation(2));
$choices=[];foreach(['me05-034','me05-006','me05-006','me05-005','me05-039','sv08-165','sv08-165'] as $i=>$id)$choices[]=['value'=>'candidate-'.$i,'card'=>$id];
$d=['choices'=>$choices,'min'=>0,'max'=>5,'type'=>'MZMULTICHOOSE','prompt'=>'Brilliant Blender: discard up to five cards'];
$value=PokeBotDecision($ctx,$d);$ids=array_map(fn($ref)=>$choices[(int)substr($ref,10)]['card'],explode('&',$value));
botCheck(count($ids)===5&&count(array_filter($ids,fn($id)=>in_array($id,['me05-034','me05-006','me05-005'],true)))===2,'Existing HNS discards reduce the Blender deficit; spare slots thin Call Bells');
GetZoneObject('p2Active-0')->Energy=[];PokeAdd(2,'Discard','mee-005');
$choices=[['value'=>'hns','card'=>'me05-006'],['value'=>'energy','card'=>'mee-005'],['value'=>'dhelmise','card'=>'me05-039'],['value'=>'bell','card'=>'sv08-165']];
$d['choices']=$choices;$value=PokeBotDecision(PokeBotContext(PokeObservation(2)),$d);
botCheck(!in_array('energy',explode('&',$value),true)&&in_array('dhelmise',explode('&',$value),true),'Existing basic Energy recovery target makes the fifth-slot plan prefer needed Dhelmise');
botFixture();GetZoneObject('p2Active-0')->Energy=[];
$d['choices']=[['value'=>'special','card'=>'me03-088'],['value'=>'bell','card'=>'sv08-165']];
botCheck(PokeBotDecision(PokeBotContext(PokeObservation(2)),$d)==='bell','Blender does not seed unrecoverable Special Energy when basic Energy is unavailable');
botFixture();for($i=0;$i<6;++$i)PokeAdd(2,'Discard','me05-006');PokeAdd(2,'Hand','me02.5-196');botCheck(PokeBotChoose(PokeObservation(2))['type']==='attack','Night Stretcher does not undo online discard engine for no benefit');
botFixture();GetZoneObject('p2Active-0')->Energy=[];PokeAdd(2,'Hand','mee-005');$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attach'&&$a['target']==='p2Active-0','Bot powers active attacker');
// Poltchageist is a free-retreat pivot, not the first attachment target.
botFixture();$pivot=GetZoneObject('p2Active-0');$pivot->CardID='me05-005';$pivot->Energy=[];
PokeAdd(2,'Hand','me05-039');PokeAdd(2,'Hand','mee-005');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='bench'&&GetZoneObject($a['source'])->CardID==='me05-039','Bench Dhelmise before committing opening Energy to Poltchageist');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attach'&&GetZoneObject($a['target'])->CardID==='me05-039','Opening Energy powers Dhelmise instead of the pivot');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='retreat'&&$a['payment']===[]&&GetZoneObject($a['target'])->CardID==='me05-039','Free retreat to Dhelmise even without the four-HNS boost');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attack'&&GetZoneObject($a['source'])->CardID==='me05-039','Dhelmise follows through with the attack');
// A previously powered pivot must also yield to the main attacker, even
// though 30 vs 10 damage falls below the old generic retreat threshold.
botFixture();GetZoneObject('p2Active-0')->CardID='me05-005';
$c=PokeAdd(2,'Bench','me05-039');$c->Controller=2;$c->Energy=['mee-005'];
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='retreat','Powered Poltchageist does not attack instead of a ready Dhelmise');
// Recovery must establish the first attacker before choosing surplus Energy.
botFixture();GetZoneObject('p2Active-0')->CardID='me05-005';GetZoneObject('p2Active-0')->Energy=[];
PokeAdd(2,'Hand','me02.5-196');PokeAdd(2,'Discard','me05-039');PokeAdd(2,'Discard','mee-005');
PokeApplyAction(PokeBotChoose(PokeObservation(2)));$a=PokeBotChoose(PokeObservation(2));
botCheck(GetZoneObject($a['value'])->CardID==='me05-039','Night Stretcher finds first Dhelmise before Energy');
// Do not consume the attachment on the pivot while a legal draw route remains.
botFixture();GetZoneObject('p2Active-0')->CardID='me05-005';GetZoneObject('p2Active-0')->Energy=[];
PokeAdd(2,'Hand','mee-005');PokeAdd(2,'Hand','me02.5-192');
for($i=0;$i<7;$i++)PokeAdd(2,'Hand','me02.5-183');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-192','Try draw for Dhelmise before fallback Energy attachment even with a large hand');
// A genuine dead opening still gets its backup attack instead of passing.
botFixture();GetZoneObject('p2Active-0')->CardID='me05-005';GetZoneObject('p2Active-0')->Energy=[];PokeAdd(2,'Hand','mee-005');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attach'&&$a['target']==='p2Active-0','Poltchageist gets Energy only when Dhelmise setup routes are exhausted');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attack','Backup Poltchageist attack remains available');
botFixture();$before=PokeBotChoose(PokeObservation(2));PokeAdd(1,'Hand','sv08-164');$opposingDeck=&GetDeck(1);$opposingDeck=array_reverse($opposingDeck);$prizes=&GetPrizes(1);$prizes[0]->CardID='me05-006';
botCheck(PokeBotChoose(PokeObservation(2))===$before,'Hidden opposing identities do not affect bot choice');
botFixture();$b=PokeAdd(1,'Bench','me05-005');$b->Controller=1;for($i=0;$i<4;++$i)PokeAdd(2,'Discard','me05-006');
PokeRunBot(2);botCheck(GetTurnPlayer()===2&&PokeDecisionOptions(1)!==null,'Bot stops for human promotion during its own turn');
$d=PokeDecisionOptions(1);PokeAnswerDecision(1,$d['choices'][0]['value']);PokeRunBot(2);botCheck(GetTurnPlayer()===1,'Bot resumes after human promotion and ends turn');
$deck=PokeParseDeckText(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt'));
PokeCreateGame($deck,PokeNamedDeck('brisbane-lopunny'),2,2);$openingAttack=null;
for($steps=0;$steps<300&&!GetWinner();++$steps){
    $seat=PokePendingPlayer();$action=PokeBotChoose(PokeObservation($seat));
    if($seat===1&&$action['type']==='attack'){$openingAttack=GetZoneObject($action['source'])->CardID;break;}
    if(GetPlayerTurns(1)>1)break;
    PokeApplyAction($action);
}
botCheck($openingAttack==='me05-039'&&GetPlayerTurns(1)===1&&PokeCountHideSneak(1)>=4,'Reported seed 2 opens with a boosted Dhelmise attack instead of Poltchageist');
// Once the combo is online, commit the next Dhelmise and its attachment before
// attacking or shuffling those resources back with a draw Supporter.
botFixture();for($i=0;$i<4;$i++)PokeAdd(2,'Discard','me05-006');
PokeAdd(2,'Hand','me05-039');PokeAdd(2,'Hand','mee-005');PokeAdd(2,'Hand','me02.5-192');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='bench'&&GetZoneObject($a['source'])->CardID==='me05-039','Online combo benches replacement before draw or attack');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attach'&&$a['target']==='p2Bench-0','Energy goes to unpowered replacement instead of stacking on Active');PokeApplyAction($a);
botCheck(count(GetZoneObject('p2Active-0')->Energy)===1&&count(GetZoneObject('p2Bench-0')->Energy)===1,'Both current and backup Dhelmise are powered');
// Do not spend the recovery card on spare Energy when the current attacker
// already has Energy and a replacement Pokémon is the missing resource.
botFixture();for($i=0;$i<4;$i++)PokeAdd(2,'Discard','me05-006');
PokeAdd(2,'Discard','me05-039');PokeAdd(2,'Discard','mee-005');PokeAdd(2,'Hand','me02.5-196');
PokeApplyAction(PokeBotChoose(PokeObservation(2)));$a=PokeBotChoose(PokeObservation(2));
botCheck(GetZoneObject($a['value'])->CardID==='me05-039','Recover backup Dhelmise ahead of surplus Energy for powered Active');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='bench','Recovered replacement is immediately benched');PokeApplyAction($a);
PokeAdd(2,'Hand','me02.5-196');PokeApplyAction(PokeBotChoose(PokeObservation(2)));$a=PokeBotChoose(PokeObservation(2));
botCheck(GetZoneObject($a['value'])->CardID==='mee-005','Next recovery powers that backup without disturbing HNS');PokeApplyAction($a);PokeApplyAction(PokeBotChoose(PokeObservation(2)));
botCheck(PokeCountHideSneak(2)===4&&count(GetZoneObject('p2Bench-0')->Energy)===1,'Replacement setup preserves discard combo');
// Four is sufficient for Dhelmise. An unnecessary Ultra Ball should not burn
// resources searching for extra HNS once current and backup attackers are ready.
botFixture();for($i=0;$i<4;$i++)PokeAdd(2,'Discard','me05-006');
$c=PokeAdd(2,'Bench','me05-039');$c->Controller=2;$c->Energy=['mee-005'];
PokeAdd(2,'Hand','me02.5-213');PokeAdd(2,'Hand','mee-005');PokeAdd(2,'Hand','mee-005');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attack','Online attackers do not waste Ultra Ball chasing six HNS');
// Special Energy can supply a replacement Pokémon as well as an attachment.
botFixture();for($i=0;$i<4;$i++)PokeAdd(2,'Discard','me05-006');GetZoneObject('p2Active-0')->Energy=[];
PokeAdd(2,'Deck','me05-039');PokeAdd(2,'Hand','mee-005');PokeAdd(2,'Hand','me03-088');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='attach'&&GetZoneObject($a['source'])->CardID==='me03-088','Telepathic Energy is preferred when it can establish a backup');PokeApplyAction($a);PokeApplyAction(PokeBotChoose(PokeObservation(2)));
botCheck(PokeCount(2,'Bench')===1&&GetZoneObject('p2Bench-0')->CardID==='me05-039','Telepathic search follows through on replacement plan');
// Seed 7 used to discard Lillie into Ultra Ball and settle for 30 damage.
PokeCreateGame($deck,PokeNamedDeck('brisbane-lopunny'),7,2);$openingAttack=null;$usedOpeningDraw=false;
for($steps=0;$steps<300&&!GetWinner();++$steps){
    $seat=PokePendingPlayer();$action=PokeBotChoose(PokeObservation($seat));
    if($seat===1&&$action['type']==='trainer'&&GetZoneObject($action['source'])->CardID==='me02.5-192')$usedOpeningDraw=true;
    if($seat===1&&$action['type']==='attack'){$openingAttack=GetZoneObject($action['source'])->CardID;break;}
    if(GetPlayerTurns(1)>1)break;
    PokeApplyAction($action);
}
botCheck($usedOpeningDraw&&$openingAttack==='me05-039'&&GetPlayerTurns(1)===1,'Seed 7 tries Lillie for the missing combo instead of discarding it into Ultra Ball');
// Petrel -> Blender has only one spare resource slot at zero HNS. With both
// attacker and Energy missing, one Stretcher cannot complete the opening.
botFixture();AddPlayerTurns(2,1);SetTurnNumber(2);GetZoneObject('p2Active-0')->CardID='me05-005';GetZoneObject('p2Active-0')->Energy=[];
foreach(['me02.5-207','sv08-165','me02.5-196'] as $id)PokeAdd(2,'Hand',$id);
PokeAdd(2,'Deck','me02.5-192');PokeAdd(2,'Deck','sv08-164');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='sv08-165','Use Call Bell for draw before committing Petrel to an impossible two-resource opening');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck(GetZoneObject($a['value'])->CardID==='me02.5-192','Call Bell selects Lillie when Blender cannot supply both missing resources');PokeApplyAction($a);
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-192','Follow through on the opening draw plan');
// A spare Poltchageist must remain available as HNS fodder until four are
// discarded; the opening does not need a second attacking line on the Bench.
botFixture();PokeAdd(2,'Hand','me05-005');SetCurrentPhase('SETUP');AddSetupReady(2,false);
botCheck(PokeBotChoose(PokeObservation(2))['type']==='ready','Setup preserves optional Poltchageist in hand for the discard combo');
SetCurrentPhase('MAIN');botCheck(PokeBotChoose(PokeObservation(2))['type']==='attack','Do not bench optional HNS fodder ahead of the opening combo');
// Two Ultra Balls plus three HNS in hand can complete four discards by
// searching the fourth HNS between costs, without sacrificing Lillie.
botFixture();foreach(['me02.5-213','me02.5-213','me05-006','me05-006','me05-006','me02.5-192'] as $id)PokeAdd(2,'Hand',$id);
PokeAdd(2,'Deck','me05-006');PokeAdd(2,'Deck','me05-039');
$a=PokeBotChoose(PokeObservation(2));botCheck($a['type']==='trainer'&&GetZoneObject($a['source'])->CardID==='me02.5-213','Reachable two-Ultra-Ball combo takes priority over shuffling away its pieces');
for($i=0;$i<6;$i++)PokeApplyAction(PokeBotChoose(PokeObservation(2)));
botCheck(PokeCountHideSneak(2)===4&&in_array('me02.5-192',array_column(PokeObjects(2,'Hand'),'CardID'),true),'Ultra Ball chain reaches four HNS and preserves Lillie');
foreach([1,42,99] as $seed){
    PokeCreateGame($deck,$deck,$seed,1);PokeRunBot(2);
    botCheck(GetSetupReady(2),'Bot commits setup without requiring human hotseat control');
    for($steps=0;$steps<1500&&!GetWinner();++$steps){
        PokeStateImport(json_decode(json_encode(PokeStateExport()),true));
        $seat=PokePendingPlayer();$action=PokeBotChoose(PokeObservation($seat));
        if($action===null)throw new RuntimeException('Bot stalled without a human choice');
        PokeApplyAction($action);
    }
    botCheck(GetWinner()>0,"Bot mirror seed $seed finishes");
    echo "Bot mirror seed $seed: player ".GetWinner()." won in ".GetTurnNumber()." turns ($steps actions)\n";
}
echo "PASS $checks bot checks\n";
