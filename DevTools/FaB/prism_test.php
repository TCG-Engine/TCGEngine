<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('prism');
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Prism deck not legal.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/prism_source.json'),true))===$deck,'Prism deck differs from source.');
$resetPrism=function(int $seats)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}$p=$seats;$h=&GetHero($p);$h=[];AddHero($p,CardID:'prism',Owner:$p,Controller:$p);SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(1);};
foreach([2,4] as $seats){
    $resetPrism($seats);$p=$seats;AddWeapons($p,CardID:'iris_of_reality',Owner:$p,Controller:$p);
    $shield=FaBWTRCreateArena($p,'spectral_shield');$shieldUID=intval($shield->UniqueID);AddHand($p,CardID:'wartune_herald_blue');
    $check(FaBWTRActivate($p,FaBFindUID($shieldUID)['mzID']),'Iris aura activation failed.');if(GetDecisionQueue($p))$answer($p,'p1Hero-0');
    $check(FaBGetState()['window']==='PITCH'&&intval(FaBGetState()['pendingPayment']['cost'])===3,'Iris did not charge three.');
    DoPitchCard($p,'p'.$p.'Hand-0');$top=FaBStackTop();DoResolveCard($p,FaBFindUID(intval($top->UniqueID))['mzID']);
    $s=FaBGetState();$attack=FaBFindUID(intval($s['attackUID']));
    $check(FaBAttackPower($s)===4&&FaBAttackHasGoAgain($s,$attack['object'])&&FaBFindUID($shieldUID)['zone']==='Arena','Iris attack lost power, go again or aura source.');

    $resetPrism($seats);AddHand(1,CardID:'zap_red');AddEquipment($p,CardID:'spell_fray_leggings',Owner:$p,Controller:$p);SetTurnPlayer(1);SetPriorityPlayer(1);
    DoPlayCard(1,'p1Hand-0');$answer(1,'p'.$p.'Hero-0');$top=FaBStackTop();DoResolveCard(1,FaBFindUID(intval($top->UniqueID))['mzID']);
    $check((GetDecisionQueue($p)[0]->Type??'')==='MZMAYCHOOSE','Leggings did not offer Spellvoid.');$answer($p,'p'.$p.'Equipment-0');
    $check(intval(GetHealth($p))===18&&!FaBChoiceRefs($p,'Equipment'),'Leggings did not prevent one arcane damage.');

    $resetPrism($seats);AddResources($p,3);AddHand($p,CardID:'herald_of_protection_red');DoPlayCard($p,'p'.$p.'Hand-0');if(GetDecisionQueue($p))$answer($p,'p1Hero-0');
    $top=FaBStackTop();DoResolveCard($p,FaBFindUID(intval($top->UniqueID))['mzID']);$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));FaBWTRTag($f['object'],'MON_SOUL_HIT');
    OnHit($p,$f['mzID'],1);
    $check(count(FaBChoiceRefs($p,'Soul'))===1&&count(FaBMONArena($p,'spectral_shield'))===1,'Seek Enlightenment prevented Herald hit effect.');

    $resetPrism($seats);$p=$seats;$o=AddArsenal($p,CardID:'the_librarian',FaceDown:1);$uid=intval($o->UniqueID);
    for($i=0;$i<4;++$i)AddDeck($p,CardID:'wartune_herald_blue');AddDeck($p,CardID:'herald_of_judgment_yellow');
    FaBPrismShieldCreated($p);$check(!FaBLessonCounters($o)&&FaBHandCount($p)===0,'Face-down mentor triggered.');
    FaBPrismStart($p);$answer($p,'1');$check(intval(FaBFindUID($uid)['object']->FaceDown)===0,'Mentor did not reveal.');
    FaBWTRCreateArena(1,'spectral_shield');$check(FaBHandCount($p)===0,'Other player Shield triggered mentor.');
    for($i=0;$i<3;++$i)FaBWTRCreateArena($p,'spectral_shield');
    $check(FaBHandCount($p)===1&&FaBLessonCounters(FaBFindUID($uid)['object'])===1,'Multiple Shields earned multiple lessons.');
    SetTurnNumber(2);SetTurnPlayer(1);FaBWTRCreateArena($p,'spectral_shield');
    $check(FaBHandCount($p)===2&&FaBLessonCounters(FaBFindUID($uid)['object'])===2,'Opponent turn did not refresh mentor.');
    SetTurnNumber(3);FaBWTRCreateArena($p,'spectral_shield');
    $check(FaBFindUID($uid)['zone']==='Banish','Third lesson did not banish mentor.');
    $d=GetDecisionQueue($p)[0]??null;$check($d&&$d->Tooltip==='Find_a_specialization_for_arsenal','Missing specialization search.');
    if($d){$answer($p,explode('&',$d->Param)[0]);$a=FaBIdentityFromMZ(FaBChoiceRefs($p,'Arsenal')[0])['object'];$check($a->CardID==='herald_of_judgment_yellow'&&intval($a->FaceDown)===0&&!FaBChoiceRefs($p,'Temp'),'Search failed to put specialization face up.');}
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "Prism mentor checks passed in duels and UPF.\n";
foreach([['prism','boltyn'],['prism','prism','prism','prism'],['fai','professor','ira','prism']] as $profiles){
    $seats=count($profiles);$resetPrism($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$bots=[];
    foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
        foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
    }
    $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$safeTurns=[];$mentorSeen=false;$auraAttacks=0;
    for($step=0;$step<8000&&!intval(GetWinner());++$step){
        $p=BotControllerPendingPlayerForClient();
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
        if(FaBIsPrismBot($p)){
            $top=FaBStackTop();if($top&&intval(FaBObjectCounters($top)['MON_IRIS']??0))++$auraAttacks;
            foreach(GetArsenal($p) as $o)if(empty($o->removed)&&$o->CardID==='the_librarian'&&intval($o->FaceDown)===0)$mentorSeen=true;
            foreach(GetArena($p) as $o)if(empty($o->removed)&&$o->CardID==='spectral_shield')$safeTurns[$p.':'.GetTurnNumber()]=true;
        }
    }
    $check(intval(GetWinner())>0,'Prism game exceeded limit.');$check(count($safeTurns)>0,'Prism never created a Shield.');if($profiles===['prism','prism','prism','prism'])$check($auraAttacks>0,'Prism never attacked with Iris.');
    echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($safeTurns)." turns with Shields, $auraAttacks Iris observations; mentor ".($mentorSeen?'revealed':'not drawn').".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
