<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('levia');
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Levia deck not UPF legal.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/levia_source.json'),true))===$deck,'Levia deck differs from pinned source.');
$adult=$deck;$adult['hero']='levia_shadowborn_abomination';$check(!FaBFinalizeResolvedDeck($adult)['success'],'Mentor accepted with adult hero.');
$resetLevia=function(int $seats)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}$p=$seats;$h=&GetHero($p);$h=[];AddHero($p,CardID:'levia',Owner:$p,Controller:$p);SetTurnPlayer($p);SetPriorityPlayer($p);};
$playLevia=function(int $p,string $ref)use($answer){if(!DoPlayCard($p,$ref))throw new RuntimeException('Illegal play '.$ref);if(GetDecisionQueue($p)&&str_contains(GetDecisionQueue($p)[0]->Tooltip,'attack_target'))$answer($p,'p1Hero-0');};
$resolveLevia=function(){ $o=FaBStackTop();if(!$o)throw new RuntimeException('No layer to resolve');DoResolveCard(intval($o->Controller),FaBFindUID(intval($o->UniqueID))['mzID']);};
foreach([2,4] as $seats){$p=$seats;
    $resetLevia($seats);$mentor=AddArsenal($p,CardID:'lady_barthimont');$uid=intval($mentor->UniqueID);
    FaBLeviaStart(1);$check(!FaBHasPendingDecision(),'Another turn revealed mentor.');FaBLeviaStart($p);
    $answer($p,'0');$check(intval(FaBFindUID($uid)['object']->FaceDown)===1,'Declining reveal exposed mentor.');FaBLeviaStart($p);$answer($p,'1');
    $check(intval(FaBFindUID($uid)['object']->FaceDown)===0,'Mentor did not reveal.');
    AddDeck($p,CardID:'rally_the_rearguard_red');AddDeck($p,CardID:'rally_the_rearguard_red');AddDeck($p,CardID:'soul_harvest_blue');AddDeck($p,CardID:'blood_tribute_blue');
    $other=AddStack(CardID:'leg_tap_red',Controller:1,Kind:'ATTACK');OnCardPlayed(1,FaBFindUID(intval($other->UniqueID))['mzID'],'leg_tap_red','Hand');$other->removed=true;
    $check(count(FaBChoiceRefs($p,'Deck'))===4&&FaBLessonCounters(FaBFindUID($uid)['object'])===0,'Opponent attack triggered mentor.');
    AddResources($p,10);AddHand($p,CardID:'rally_the_rearguard_red');$playLevia($p,'p'.$p.'Hand-0');
    $check(FaBLessonCounters(FaBFindUID($uid)['object'])===1&&FaBMONCount($p,'BANISHED_SIX')===1,'First mentor lesson did not banish six power.');$resolveLevia();
    $check(FaBCurrentAttackHasKeyword(FaBGetState(),'Dominate'),'Mentor did not grant dominate.');FaBCloseCombatChain();AddActionPoints($p,1);SetPriorityPlayer($p);
    AddHand($p,CardID:'rally_the_rearguard_red');$playLevia($p,FaBChoiceRefs($p,'Hand')[0]);
    $check(GetDecisionQueue($p)[0]->Tooltip==='Find_a_specialization_for_arsenal','Second lesson did not search.');
    $options=explode('&',GetDecisionQueue($p)[0]->Param);$check(count($options)===1&&FaBIdentityFromMZ($options[0])['object']->CardID==='soul_harvest_blue','Search includes non-specialization.');$answer($p,$options[0]);
    $arsenal=FaBChoiceRefs($p,'Arsenal');$o=FaBIdentityFromMZ($arsenal[0])['object'];
    $check(FaBFindUID($uid)['zone']==='Banish'&&$o->CardID==='soul_harvest_blue'&&intval($o->FaceDown)===0&&!FaBChoiceRefs($p,'Temp'),'Mentor search did not replace arsenal face up.');
    $check(count(FaBChoiceRefs($p,'Deck'))===1,'Search lost remaining deck cards.');

    $resetLevia($seats);AddArsenal($p,CardID:'lady_barthimont',FaceDown:0);AddDeck($p,CardID:'blood_tribute_blue');AddResources($p,2);AddHand($p,CardID:'rally_the_rearguard_red');$playLevia($p,'p'.$p.'Hand-0');
    $check(FaBLessonCounters(GetArsenal($p)[0])===0&&FaBMONCount($p,'BANISHED_SIX')===0,'Non-six banish granted a lesson.');

    $resetLevia($seats);SetTurnPlayer(1);SetPriorityPlayer(1);AddHand(1,CardID:'zap_red');AddEquipment($p,CardID:'spell_fray_cloak',Owner:$p,Controller:$p);
    DoPlayCard(1,'p1Hand-0');$answer(1,'p'.$p.'Hero-0');$resolveLevia();
    $check(GetDecisionQueue($p)[0]->Type==='MZMAYCHOOSE','Spell Fray Cloak did not offer Spellvoid.');$answer($p,'p'.$p.'Equipment-0');
    $check(intval(GetHealth($p))===18&&!FaBChoiceRefs($p,'Equipment'),'Spell Fray Cloak failed to destroy/prevent one arcane.');

    $resetLevia($seats);AddGraveyard($p,CardID:'rally_the_rearguard_red');AddGraveyard($p,CardID:'blood_tribute_blue');AddGraveyard($p,CardID:'unworldly_bellow_blue');
    $check(FaBLeviaSixChance($p)===1.0,'Six-power probability wrong for guaranteed sample.');
    AddGraveyard($p,CardID:'blood_tribute_blue');$check(abs(FaBLeviaSixChance($p)-0.75)<0.0001,'Six-power probability wrong for partial sample.');
    $decision=(object)['Type'=>'MZREARRANGE','Param'=>'Top=blood_tribute_blue;Bottom=','Tooltip'=>''];
    $check(FaBLeviaChoice($p,$decision)==='Top=;Bottom=blood_tribute_blue','Blood Tribute did not bottom a revealed non-six.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "Levia deck, mentor and Spellvoid checks passed in duels and UPF.\n";

foreach([['levia','boltyn'],['levia','levia','levia','levia'],['fai','professor','ira','levia']] as $profiles){
    $seats=count($profiles);$resetLevia($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$bots=[];
    foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
        foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
    }
    $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$safeTurns=[];$mentorSeen=false;
    for($step=0;$step<8000&&!intval(GetWinner());++$step){
        $p=BotControllerPendingPlayerForClient();
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
        if(FaBIsLeviaBot($p)){
            if(FaBMONCount($p,'BANISHED_SIX'))$safeTurns[$p.':'.GetTurnNumber()]=true;
            foreach(GetArsenal($p) as $o)if(empty($o->removed)&&$o->CardID==='lady_barthimont'&&intval($o->FaceDown)===0)$mentorSeen=true;
        }
    }
    $check(intval(GetWinner())>0,'Levia game exceeded limit.');$check(count($safeTurns)>0,'Levia never suppressed blood debt.');
    echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($safeTurns).' turns banishing six power; mentor '.($mentorSeen?'revealed':'not drawn').".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
