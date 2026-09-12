<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBProfessorBotDeck();
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Pinned Professor deck is not legal UPF.');
$import=FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/professor_source.json'),true));
$check($import===$deck,'Fabrary export does not reproduce the pinned deck (Evo routing).');
$setup=function(int $p)use($deck){
    $h=&GetHero($p);$h=[];AddHero($p,CardID:$deck['hero'],Owner:$p,Controller:$p);
    foreach($deck['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
    foreach($deck['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
};
foreach([2,4] as $seats){
    $arcReset($seats);$p=$seats;$setup($p);SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,9);
    $evo=AddBanish($p,CardID:'evo_energy_matrix_blue');
    $check(CanPlayCard($p,'p'.$p.'Banish-0'),'Professor cannot play a banished Evo.');
    $check(FaBCardCost($evo,$p)===($seats===4?0:2),'Professor cost does not count live opposing heroes.');
    DoPlayCard($p,'p'.$p.'Banish-0');DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
    $check(FaBEvoCount($p)===1&&FaBTekloBlasterCost($p)===($seats===4?0:2),'Matrix upgrade/cost failed.');
    $equipped=array_values(array_filter(FaBProfessorEquipped($p),fn($o)=>FaBHasType($o,'Evo')))[0];
    $check(FaBObjectCounters($equipped)['SUBCARDS']===['proto_base_chest'],'Base did not become an Evo subcard.');
    $duplicate=AddGraveyard($p,CardID:'evo_energy_matrix_blue');FaBEvoEquip($p,FaBFindUID(intval($duplicate->UniqueID))['mzID']);
    $check(FaBEvoCount($p)===1,'An Evo without Base incorrectly replaced equipment.');
    FaBMoveUID(intval($equipped->UniqueID),'Graveyard',$p);
    $check(count(FaBChoiceRefs($p,'Graveyard',['base'=>'proto_base_chest']))===1,'Destroyed Evo did not release its subcard.');
}
$arcReset();$setup(1);
foreach(['evo_rapid_fire_blue','evo_scatter_shot_blue','evo_tekloscope_blue'] as $id){$o=AddGraveyard(1,CardID:$id);FaBEvoEquip(1,FaBFindUID(intval($o->UniqueID))['mzID']);}
$weapon=GetWeapons(1)[0];
$check(FaBProfessorPower(1,$weapon)===3&&FaBAttackHasGoAgain(array_replace(FaBGetState(),['attacker'=>1]),$weapon),'Blaster upgrades missing.');
$targets=FaBProfessorAttackTargets(1,intval($weapon->UniqueID));
$check(array_column($targets,'player')===[2,3,4],'Tekloscope cannot target the opposite hero.');
$mayhem=AddHand(1,CardID:'liquid_cooled_mayhem_red');$strength=AddHand(1,CardID:'mechanical_strength_red');
$check(FaBCardCost($mayhem,1)===1&&FaBProfessorPower(1,$strength)===3,'Evo Upgrade attack costs/power incorrect.');
$h=&GetHand(1);$h=[];
AddResources(1,3);AddHand(1,CardID:'apocalypse_automaton_red');
$check(DoPlayCard(1,'p1Hand-0')&&GetDecisionQueue(1)[0]->Type==='MZMULTICHOOSE','Automaton did not offer multiple heroes.');
$answer(1,'p4Hero-0&p2Hero-0&p3Hero-0');
DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$s=FaBGetState();$check(array_column($s['attackTargets'],'player')===[2,3,4],'Defenders not sorted clockwise.');
$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);SetPriorityPlayer(2);
AddDeck(2,CardID:'evo_rapid_fire_blue');AddHand(2,CardID:'firewall_blue');
FaBDeclareBlock(2,'p2Hand-0');FaBFinishDefendDeclaration(FaBGetState());
$check(GetPriorityPlayer()===3&&GetDeck(2)[0]->CardID==='evo_rapid_fire_blue','Firewall/Evo reveal or next defender failed.');
AddHand(3,CardID:'firewall_red');AddDeck(3,CardID:'throttle_red');AddDeck(3,CardID:'evo_tekloscope_blue');
FaBDeclareBlock(3,'p3Hand-0');FaBFinishDefendDeclaration(FaBGetState());
$check(GetPriorityPlayer()===4&&FaBIdentityFromMZ(FaBChoiceRefs(3,'Deck')[0])['object']->CardID==='evo_tekloscope_blue','Firewall did not bottom the non-Evo.');
FaBFinishDefendDeclaration(FaBGetState());$s=FaBGetState();$s['window']='REACTION';$s['combatStep']='REACTION';FaBSetState($s);
AddResources(2,3);AddHand(2,CardID:'unmovable_blue');SetPriorityPlayer(2);
$check(CanPlayCard(2,'p2Hand-1'),'An earlier defender cannot use a defense reaction.');
DoPlayCard(2,'p2Hand-1');DoResolveCard(2,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(FaBDefenseValue(FaBGetState(),2)===7&&FaBDefenseValue(FaBGetState(),3)===4,'Defense totals were pooled between heroes.');
FaBBeginDamageStep();
$check(intval(GetHealth(2))===20&&intval(GetHealth(3))===18&&intval(GetHealth(4))===14,'Automaton damage was not independent per target.');
$check(count(FaBGetState()['targetDamage'])===3,'Per-target damage summary missing.');
FaBBeginResolutionStep();$check(intval(GetActionPoints(1))===0,'Multi-target attack awarded extra action points.');

// Equipped effects apply on the chain; an Evo defended from hand is not equipped.
$arcReset();$setup(4);$o=AddHand(4,CardID:'evo_scatter_shot_blue');
$s=FaBGetState();$s['attacker']=1;$s['defender']=4;$s['attackTarget']=['type'=>'HERO','player'=>4];$s['window']='DEFEND_DECLARE';FaBSetState($s);
FaBDeclareBlock(4,'p4Hand-0');$check(FaBEvoCount(4)===0,'Hand-blocked Evo counted as equipped.');

// Zero is a legal target count; the card is paid for and leaves without an attack.
$arcReset();$setup(1);$o=AddGraveyard(1,CardID:'evo_energy_matrix_blue');FaBEvoEquip(1,FaBFindUID(intval($o->UniqueID))['mzID']);
AddResources(1,3);AddHand(1,CardID:'apocalypse_automaton_red');DoPlayCard(1,'p1Hand-0');$answer(1,'-');
DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$check(!FaBGetState()['combatOpen']&&FaBStackCount()===0&&intval(GetResources(1))===0,'Automaton zero-target resolution failed.');

// Losing an early target must not erase the remaining simultaneous damage packets.
$arcReset();$o=AddCombatChain(1,CardID:'apocalypse_automaton_red',Owner:1,Controller:1,Role:'ATTACK',ChainLink:1);
$s=FaBGetState();$s['attacker']=1;$s['defender']=2;$s['attackUID']=intval($o->UniqueID);$s['chainLink']=1;$s['combatOpen']=true;
$s['attackTargets']=[];foreach([2,3,4] as $p)$s['attackTargets'][]=FaBAttackTargetDescriptor(FaBIdentityFromMZ('p'.$p.'Hero-0'))+['anyHero'=>true];
$s['attackTarget']=$s['attackTargets'][0];FaBSetState($s);AddHealth(2,1);AddHealth(3,1);
FaBBeginDamageStep();GameAfterEngineAction([],[]);
$check(!FaBSeatIsLive(2)&&!FaBSeatIsLive(3)&&intval(GetHealth(4))===14&&FaBGetState()['combatOpen'],'Elimination interrupted the multi-target attack.');

// Under Loop uses the common boost continuation and preserves go again after hitting.
$arcReset();AddHand(1,CardID:'under_loop_red');AddDeck(1,CardID:'firewall_red');AddResources(1,1);
DoPlayCard(1,'p1Hand-0');$answer(1,'p2Hero-0');$answer(1,'0');
$check(in_array('GO_AGAIN',FaBStackTop()->TurnEffects,true),'Under Loop boost failed.');
DoResolveCard(1,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
$uid=intval(FaBGetState()['attackUID']);FaBRunSourceMacro('Hit',1,'under_loop_red',['mzID'=>FaBFindUID($uid)['mzID'],'amount'=>1]);
$check(FaBFindUID($uid)['zone']==='Deck'&&!empty(FaBGetState()['attackGoAgain']),'Under Loop hit did not bottom itself with go again preserved.');

if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "Professor cards, Evo import/transform, Blaster and multi-target combat checks passed.\n";

foreach([[1=>'professor',2=>'fai'],[1=>'professor',2=>'professor',3=>'professor',4=>'professor'],[1=>'fai',2=>'professor',3=>'fai',4=>'professor']] as $profiles){
    $arcReset(count($profiles));
    mt_srand(20260912);
    foreach($profiles as $p=>$profile){
        $d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($d['hero'])));
        foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);
        $ordered=&GetDeck($p);shuffle($ordered);
        if($profile==='fai')FaBFaiSetup($p,true);else DoDrawCard($p,4);
    }
    $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$upgrades=0;$multi=false;
    for($steps=0;$steps<5000&&!intval(GetWinner());++$steps){
        foreach(array_keys($profiles) as $p)$upgrades=max($upgrades,FaBEvoCount($p));
        $multi=$multi||count(FaBGetState()['attackTargets']??[])>1;
        $p=BotControllerPendingPlayerForClient();
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled: '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
    }
    $check(intval(GetWinner())>0,'Bot match exceeded 5000 steps.');
    if(!intval(GetWinner()))foreach(array_keys($profiles) as $p){echo 'Unfinished P'.$p.' life '.GetHealth($p).' hand '.implode(',',array_map(fn($r)=>FaBIdentityFromMZ($r)['object']->CardID,FaBChoiceRefs($p,'Hand'))).' equipment '.implode(',',array_map(fn($o)=>$o->CardID,FaBProfessorEquipped($p))).' deck '.count(FaBChoiceRefs($p,'Deck'))."\n";}
    $check($upgrades>0,'Professor never equipped an Evo.');
    echo count($profiles)."-player bot match: $steps steps; max $upgrades Evos; multi-target ".($multi?'yes':'no').".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
