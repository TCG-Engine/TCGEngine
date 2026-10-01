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
botFixture();$before=PokeBotChoose(PokeObservation(2));PokeAdd(1,'Hand','sv08-164');$opposingDeck=&GetDeck(1);$opposingDeck=array_reverse($opposingDeck);$prizes=&GetPrizes(1);$prizes[0]->CardID='me05-006';
botCheck(PokeBotChoose(PokeObservation(2))===$before,'Hidden opposing identities do not affect bot choice');
botFixture();$b=PokeAdd(1,'Bench','me05-005');$b->Controller=1;for($i=0;$i<4;++$i)PokeAdd(2,'Discard','me05-006');
PokeRunBot(2);botCheck(GetTurnPlayer()===2&&PokeDecisionOptions(1)!==null,'Bot stops for human promotion during its own turn');
$d=PokeDecisionOptions(1);PokeAnswerDecision(1,$d['choices'][0]['value']);PokeRunBot(2);botCheck(GetTurnPlayer()===1,'Bot resumes after human promotion and ends turn');
$deck=PokeParseDeckText(file_get_contents(dirname(__DIR__,2).'/PokeSim/Decks/sinistcha.txt'));
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
