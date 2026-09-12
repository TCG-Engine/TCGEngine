<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('ira');
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Ira deck invalid.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/ira_source.json'),true))===$deck,'Pinned deck differs from Fabrary.');
$attack=function(int $p,string $id,int $target,string $previous=''){
    $o=AddCombatChain($p,CardID:$id,Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:2);
    $s=FaBGetState();$s['attacker']=$p;$s['defender']=$target;$s['attackUID']=intval($o->UniqueID);$s['chainLink']=2;$s['combatOpen']=true;$s['previousAttackCardID']=$previous;
    $s['window']='REACTION';$s['combatStep']='REACTION';$s['attackTarget']=['type'=>'HERO','player'=>$target];FaBSetState($s);
    $ref=FaBFindUID(intval($o->UniqueID))['mzID'];OnAttackDeclared($p,$ref,$p,$target);
    return [$o,$ref];
};
foreach([2,4] as $seats){
    $p=$seats;$target=1;
    foreach(['flex_claws_red','flex_claws_yellow','bittering_thorns_red','bittering_thorns_yellow','growl_red','growl_yellow'] as $id){
        $arcReset($seats);[$o,$ref]=$attack($p,$id,$target);
        if(FaBWTRBase($id)==='growl')$check(FaBARCEffect($p,'IRA_CHAIN_TIGER')===1,'Growl attack trigger missing.');
        else{
            FaBRunSourceMacro('Hit',$p,$id,['mzID'=>$ref,'amount'=>1]);
            $check(FaBWTRBase($id)==='flex_claws'?count(FaBChoiceRefs($p,'Banish'))===1:FaBARCEffect($p,'NEXT_ATTACK')===1,'Hit effect missing '.$id);
        }
    }
    $arcReset($seats);$resolve($p,'predatory_streak_blue');
    $check(count(FaBChoiceRefs($p,'Banish'))===1,'Blue Streak created wrong count.');
    $arcReset($seats);[$o,$ref]=$attack(1,'leg_tap_red',$p);
    $s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);SetPriorityPlayer($p);
    AddHand($p,CardID:'tiger_eye_reflex_yellow');FaBDeclareBlock($p,'p'.$p.'Hand-0');FaBFinishDefendDeclaration(FaBGetState());
    $check(count(FaBChoiceRefs($p,'Banish'))===1&&FaBDefenseValue(FaBGetState(),$p)===3,'Yellow Tiger Eye defense failed.');

    foreach(['pouncing_qi_red'=>[4,true],'qi_unleashed_red'=>[7,false],'mauling_qi_red'=>[5,false]] as $id=>[$power,$go]){
        $arcReset($seats);[$o,$ref]=$attack($p,$id,$target,'crouching_tiger');
        $check(FaBAttackPower(FaBGetState())===$power&&FaBAttackHasGoAgain(FaBGetState(),$o)===$go,"Combo failed $id / $seats.");
        if($id==='mauling_qi_red'){
            FaBRunSourceMacro('Hit',$p,$id,['mzID'=>$ref,'amount'=>1]);
            foreach(FaBOpponents($p) as $victim)$check(intval(GetHealth($victim))===19,'Mauling Qi missed an opposing hero.');
            $check(intval(GetHealth($p))===20,'Mauling Qi damaged its controller.');
        }
        $arcReset($seats);[$o,$ref]=$attack($p,$id,$target,'leg_tap_red');
        $check(FaBAttackPower(FaBGetState())===intval(CardPower($id))&&!FaBAttackHasGoAgain(FaBGetState(),$o),'Combo applied without Tiger.');
    }
    $arcReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,4);
    $resolve($p,'predatory_streak_yellow');$refs=FaBChoiceRefs($p,'Banish');
    $check(count($refs)===2&&CanPlayCard($p,$refs[0]),'Streak Tigers not playable.');
    FaBWTRAddEffect($p,'IRA_CHAIN_TIGER',1);FaBWTRAddEffect($p,'IRA_NEXT_TIGER',2);
    DoPlayCard($p,$refs[0]);if(GetDecisionQueue($p))$answer($p,'p1Hero-0');
    $top=FaBStackTop();$check($top!==null&&in_array('WTR_POWER:2',(array)$top->TurnEffects,true),'Tiger did not consume Shuko buff.');
    DoResolveCard($p,FaBFindUID(intval($top->UniqueID))['mzID']);
    $check(FaBAttackPower(FaBGetState())===3&&FaBARCEffect($p,'IRA_ATTACKED_TIGER')===1,'Growl/Shuko power or Blood Scent condition missing.');
    $uid=intval(FaBGetState()['attackUID']);FaBCloseCombatChain();
    $check(FaBFindUID($uid)===null&&!FaBChoiceRefs($p,'Graveyard',['base'=>'crouching_tiger']),'Ephemeral Tiger entered graveyard.');
    $check(count(FaBChoiceRefs($p,'Banish'))===1,'Unplayed Tiger disappeared.');

    // Ambush blocks from arsenal and grants permission on the defender's next turn only.
    $arcReset($seats);[$o,$ref]=$attack(1,'leg_tap_red',$p);
    $s=FaBGetState();$s['window']='DEFEND_DECLARE';$s['combatStep']='DEFEND';FaBSetState($s);SetPriorityPlayer($p);
    AddArsenal($p,CardID:'tiger_eye_reflex_blue');
    $check(FaBDeclareBlock($p,'p'.$p.'Arsenal-0'),'Ambush did not block from arsenal.');
    FaBFinishDefendDeclaration(FaBGetState());
    $tiger=FaBIdentityFromMZ(FaBChoiceRefs($p,'Banish')[0])['object'];
    $check(intval($tiger->PlayableFromBanish)===0,'Next-turn Tiger playable early.');
    FaBCloseCombatChain();
    for($turn=1;$turn<$p;++$turn)FaBFinishEndTurn($turn);
    $check(intval(GetTurnPlayer())===$p&&intval($tiger->PlayableFromBanish)===1,'Tiger permission lost across intervening turns.');
    FaBFinishEndTurn($p);$check(intval($tiger->PlayableFromBanish)===0,'Tiger permission did not expire.');
    SetTurnPlayer($p);StartOfTurnPhase();$check(intval($tiger->PlayableFromBanish)===0,'Expired Tiger permission renewed.');

    foreach(['blue'=>1,'red'=>3] as $color=>$power){
        $arcReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddResources($p,1);
        AddHand($p,CardID:'blessing_of_qi_'.$color);DoPlayCard($p,'p'.$p.'Hand-0');
        DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
        $check(count(FaBChoiceRefs($p,'Arena'))===1&&!FaBChoiceRefs($p,'Banish'),'Blessing did not wait until next turn.');
        StartOfTurnPhase();$refs=FaBChoiceRefs($p,'Banish');
        $check(count($refs)===1&&!FaBChoiceRefs($p,'Arena'),'Blessing did not destroy itself/create Tiger.');
        DoPlayCard($p,$refs[0]);if(GetDecisionQueue($p))$answer($p,'p1Hero-0');
        DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
        $check(FaBAttackPower(FaBGetState())===$power,'Blessing buff lost in zone transfer.');
    }
    foreach(['blood_scent','mask_of_three_tails','pouncing_paws','tearing_shuko'] as $id){
        $arcReset($seats);SetTurnPlayer($p);SetPriorityPlayer($p);AddDeck($p,CardID:'growl_red');
        AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);$ref='p'.$p.'Equipment-0';
        if(in_array($id,['blood_scent','mask_of_three_tails'],true))$check(!FaBWTRCanActivate($p,$ref),'Equipment ignored activation condition.');
        FaBWTRAddEffect($p,'IRA_ATTACKED_TIGER',1);$s=FaBGetState();$s['attacker']=$p;$s['chainHits']=3;$s['combatOpen']=true;FaBSetState($s);
        $check(FaBWTRActivate($p,$ref),'Equipment could not activate '.$id);
        $check(!FaBChoiceRefs($p,'Equipment'),'Equipment destruction cost missing.');
        DoResolveCard($p,FaBFindUID(intval(FaBStackTop()->UniqueID))['mzID']);
        $check(match($id){'blood_scent'=>intval(GetResources($p))===1,'mask_of_three_tails'=>FaBHandCount($p)===1,'pouncing_paws'=>count(FaBChoiceRefs($p,'Banish'))===1,'tearing_shuko'=>FaBARCEffect($p,'IRA_NEXT_TIGER')===2},'Equipment effect failed '.$id);
    }
}

$invalid=$deck;$invalid['mainDeck'][0]='crouching_tiger';
$check(!FaBFinalizeResolvedDeck($invalid)['success'],'Ephemeral accepted in starting deck.');
$arcReset();$o=AddStack(CardID:'crouching_tiger',Controller:1,Kind:'ATTACK');$uid=intval($o->UniqueID);
FaBMoveStackUID($uid,'Graveyard',1);$check(FaBFindUID($uid)===null,'Countered Tiger failed Ephemeral replacement.');
$arcReset();SetTurnPlayer(1);SetPriorityPlayer(1);
FaBCreateTigers(1);$growl=AddHand(1,CardID:'growl_red');$qi=AddHand(1,CardID:'pouncing_qi_red');
$tiger=FaBIdentityFromMZ(FaBChoiceRefs(1,'Banish')[0])['object'];
$check(FaBIraPlayScore(1,$growl,'Hand')>FaBIraPlayScore(1,$tiger,'Banish'),'Bot should growl before an unbuffed Tiger.');
[$o,$ref]=$attack(1,'crouching_tiger',2);
$check(FaBIraPlayScore(1,$qi,'Hand')>FaBIraPlayScore(1,$tiger,'Banish'),'Bot should follow Tiger with Pouncing Qi.');
$arcReset();FaBWTRAddEffect(1,'IRA_CHAIN_TIGER',1);FaBWTRAddEffect(1,'IRA_NEXT_TIGER',2);FaBCloseCombatChain();
$check(FaBARCEffect(1,'IRA_CHAIN_TIGER')===0&&FaBARCEffect(1,'IRA_NEXT_TIGER')===2,'Growl/Shuko expiry confused chain with turn.');
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "Ira deck card interactions passed in duel and UPF.\n";
foreach([[1=>'ira',2=>'fai'],[1=>'ira',2=>'ira',3=>'ira',4=>'ira'],[1=>'ira',2=>'professor',3=>'fai',4=>'ira']] as $profiles){
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
    $s=FaBGetState();$s['botProfiles']=$profiles;FaBSetState($s);$tigers=0;
    for($steps=0;$steps<5000&&!intval(GetWinner());++$steps){
        foreach(array_keys($profiles) as $p)$tigers=max($tigers,count(FaBChoiceRefs($p,'Banish',['base'=>'crouching_tiger']))+count(FaBChoiceRefs($p,'CombatChain',['base'=>'crouching_tiger'])));

        $p=BotControllerPendingPlayerForClient();
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled: '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
    }
    $check(intval(GetWinner())>0,'Bot match exceeded 5000 steps.');
    if(!intval(GetWinner()))foreach(array_keys($profiles) as $p){echo 'Unfinished P'.$p.' life '.GetHealth($p).' hand '.implode(',',array_map(fn($r)=>FaBIdentityFromMZ($r)['object']->CardID,FaBChoiceRefs($p,'Hand'))).' equipment '.implode(',',array_map(fn($o)=>$o->CardID,FaBProfessorEquipped($p))).' deck '.count(FaBChoiceRefs($p,'Deck'))."\n";}
    $check($tigers>0,'Ira never created a Tiger.');
    echo count($profiles)."-player bot match: $steps steps; max $tigers Tigers.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
