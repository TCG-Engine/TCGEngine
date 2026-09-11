<?php
require_once __DIR__.'/wtr_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
require_once __DIR__.'/../../Core/BotController.php';
$failures=[];
$deck=FaBFaiBotDeck();
$check(FaBUPFDeckErrors($deck)===[],'Fai bot deck is not legal UPF.');
$check(NormalizeBotControllerPlayers([0,1,2,3,4,5,4])===[1,2,3,4],'Bot transport discarded multiplayer seats.');
$reset();
$s=FaBGetState();$s['botProfiles']=[3=>'fai',4=>'fai'];FaBSetState($s);SetPriorityPlayer(4);
$check(BotControllerPendingPlayerForClient()===4,'Seat four bot cannot act.');
DecisionQueueController::AddDecision(2,'MZMODAL','1|1|Yes&No',1);
$check(BotControllerPendingPlayerForClient()===0,'Bot acted while a human owes a decision.');
$reset();
$a=AddCombatChain(1,CardID:'brand_with_cinderclaw_red',Role:'ATTACK',ChainLink:1,Owner:1,Controller:1);
$b=AddCombatChain(1,CardID:'phoenix_flame_red',Role:'ATTACK',ChainLink:2,Owner:1,Controller:1);
$s=FaBGetState();$s['attacker']=1;$s['defender']=2;$s['chainLink']=2;$s['attackUID']=$b->UniqueID;$s['combatOpen']=true;FaBSetState($s);
$check(FaBAttackPower($s)===1,'Phoenix Flame did not gain power with two Draconic links.');
$check(FaBWTRAbilityCost(1,FaBWTRAbilitySpec('fai'))===1,'Fai activation cost does not count Draconic links.');
FaBWTRAddEffect(1,'STUBBY',1);FaBWTRAddEffect(1,'AOW_STATS',1);
$check(FaBAttackPower($s)===3,'Stubby / Art of War turn buffs do not apply.');
$reset();
AddDeck(1,CardID:'phoenix_flame_red');for($i=0;$i<5;++$i)AddDeck(1,CardID:'ronin_renegade_red');
FaBFaiSetup(1,true);
$check(count(FaBChoiceRefs(1,'Graveyard'))===1&&FaBHandCount(1)===4,'Fai setup did not move one Flame before drawing.');
$reset();
$double=AddCombatChain(1,CardID:'double_strike_red',Role:'ATTACK',ChainLink:1,Owner:1,Controller:1);
$s=FaBGetState();$s['attacker']=1;$s['chainLink']=1;$s['combatOpen']=true;FaBSetState($s);
OnChainLinkResolved(1,'p1CombatChain-0');
$check(count(FaBChoiceRefs(1,'Banish'))===1&&GetBanish(1)[0]->PlayableFromBanish===1,'Double Strike did not enable replay.');
$savedBanish=new Banish(GetBanish(1)[0]->Serialize(),'Banish',1,0);
$check(in_array('DOUBLE_REPLAY',$savedBanish->TurnEffects,true)&&intval(FaBObjectCounters($savedBanish)['FAI_CHAIN_PLAY']??0)===1,'Double Strike replay flags did not survive persistence.');
FaBCloseCombatChain();
$check(GetBanish(1)[0]->PlayableFromBanish===0,'Double Strike replay survived chain close.');
$reset();
AddHand(1,CardID:'ronin_renegade_red');AddDeck(1,CardID:'brand_with_cinderclaw_red');AddDeck(1,CardID:'blaze_headlong_red');
$art=AddStack(CardID:'art_of_war_yellow',Controller:1,Kind:'INSTANT');
FaBRunSourceMacro('ResolveCard',1,$art->CardID,['mzID'=>'Stack-0']);
$check(GetDecisionQueue(1)[0]->Param==='2|2|Attack_and_defense_+1&Next_attack_go_again&Defend_from_arsenal&Banish_attack_draw_two','Art of War did not offer four modes.');
$answer(1,'0,3');
$check(GetDecisionQueue(1)[0]->Type==='MZMAYCHOOSE'&&GetDecisionQueue(1)[0]->Param==='p1Hand-0','Art of War did not prompt for an eligible hand attack.');
$answer(1,'p1Hand-0');
$check(FaBHandCount(1)===2&&count(FaBChoiceRefs(1,'Banish'))===1&&FaBFaiEffect(1,'AOW_STATS')===1,'Art of War exchange failed across choices.');

$reset();
$brand=AddCombatChain(1,CardID:'brand_with_cinderclaw_red',Role:'ATTACK',ChainLink:1,Owner:1,Controller:1);
$s=FaBGetState();$s['attacker']=1;$s['defender']=2;$s['chainLink']=1;$s['combatOpen']=true;FaBSetState($s);
OnAttackDeclared(1,'p1CombatChain-0',1,2);
$double=AddStack(CardID:'double_strike_red',Controller:1,Kind:'ATTACK');
FaBWTRCardPlayed(1,'Stack-0','double_strike_red','Hand');
$check(FaBHasType($double,'Draconic')&&FaBFaiEffect(1,'FAI_BRAND')===0,'Brand did not convert exactly the next attack.');
$moved=FaBMoveStackUID(intval($double->UniqueID),'CombatChain',1);$moved->Role='ATTACK';$moved->ChainLink=2;
FaBFaiDoubleStrike(1,'p1CombatChain-1');
$check(FaBFaiChainCount(1)===2,'Departed Double Strike lost its Draconic chain-link identity.');
$replay=FaBMoveUID(intval($double->UniqueID),'Hand',1);
$check(FaBFaiChainCount(1)===2,'Moving a departed attack changed its chain-link identity.');

$reset();
AddCombatChain(1,CardID:'phoenix_flame_red',Role:'ATTACK',ChainLink:1,Owner:1,Controller:1);
AddCombatChain(1,CardID:'phoenix_flame_red',Role:'ATTACK',ChainLink:2,Owner:1,Controller:1);
$form=AddCombatChain(1,CardID:'phoenix_form_red',Role:'ATTACK',ChainLink:3,Owner:1,Controller:1);
$s=FaBGetState();$s['attacker']=1;$s['defender']=2;$s['chainLink']=3;$s['attackUID']=$form->UniqueID;$s['combatOpen']=true;FaBSetState($s);
$check(FaBAttackPower($s)===5&&FaBAttackHasGoAgain($s,$form),'Phoenix Form conditional power/go again failed.');
$resentment=AddHand(1,CardID:'raging_onslaught_red');
$check(!FaBFaiBanishAttack(1,'p1Hand-0',true),'Resentment allowed cost equal to Draconic link count.');
AddHand(1,CardID:'blaze_headlong_red');
$check(FaBFaiBanishAttack(1,'p1Hand-1',true)&&GetBanish(1)[0]->PlayableFromBanish===1,'Resentment did not grant turn-long play.');
$check(FaBWTRCostModifier(1,GetBanish(1)[0])===-1,'Resentment cost reduction failed.');

$reset();
foreach([1,2,3,4] as $p)AddDeck($p,CardID:'ronin_renegade_red');
AddArsenal(3,CardID:'snatch_red');FaBFaiPromise();
$check(count(FaBChoiceRefs(3,'Deck'))===1&&count(FaBChoiceRefs(4,'Arsenal'))===1,'Promise did not fill only empty arsenals across all seats.');
$s=FaBGetState();$s['defender']=4;$s['chainLink']=3;FaBSetState($s);FaBFaiBreakArsenal();
$check(count(FaBChoiceRefs(4,'Arsenal'))===1,'Breaking Point triggered below rupture.');
$s['chainLink']=4;FaBSetState($s);FaBFaiBreakArsenal();
$check(count(FaBChoiceRefs(4,'Arsenal'))===0,'Breaking Point did not destroy the targeted hero arsenal.');

$reset();
$s=FaBGetState();$s['chainLink']=4;$s['attacker']=1;$s['defender']=2;FaBSetState($s);
AddCombatChain(1,CardID:'brand_with_cinderclaw_red',Role:'ATTACK',ChainLink:1);
AddCombatChain(1,CardID:'red_hot_red',Role:'ATTACK',ChainLink:4);
AddDeck(1,CardID:'ronin_renegade_red');AddDeck(1,CardID:'brand_with_cinderclaw_blue');
FaBRunSourceMacro('AttackDeclared',1,'red_hot_red',['mzID'=>'p1CombatChain-1','attacker'=>1,'defender'=>2]);
$check(GetDecisionQueue(1)[0]->Type==='MZCHOOSE','Red Hot did not prompt for its reveal damage target.');
$answer(1,'p4Hero-0');
$check(intval(GetHealth(4))===19&&count(FaBChoiceRefs(1,'Deck'))===2,'Red Hot damage/reveal/shuffle continuation failed.');

$reset();
$hero=&GetHero(1);$hero=[];AddHero(1,CardID:'fai',Owner:1,Controller:1,Status:2);
AddGraveyard(1,CardID:'phoenix_flame_red');AddResources(1,3);
$check(FaBWTRActivate(1,'p1Hero-0'),'Fai activation could not be announced.');
$check(!FaBWTRCanActivate(1,'p1Hero-0'),'Fai could activate twice in a turn.');
$layer=FaBStackTop();DoResolveCard(1,'Stack-'.$layer->mzIndex);
$answer(1,'p1Graveyard-0');
$check(FaBHandCount(1)===1&&intval(GetResources(1))===0,'Fai activation did not pay and return Flame.');

$reset();
AddGraveyard(1,CardID:'phoenix_flame_red');$rise=AddStack(CardID:'rise_from_the_ashes_red',Controller:1);
FaBRunSourceMacro('ResolveCard',1,$rise->CardID,['mzID'=>'Stack-0']);$answer(1,'p1Graveyard-0');
$attack=AddStack(CardID:'blaze_headlong_red',Controller:1);
FaBWTRCardPlayed(1,'Stack-1','blaze_headlong_red','Hand');
$check(FaBHandCount(1)===1&&in_array('WTR_POWER:3',$attack->TurnEffects,true),'Rise did not return Flame and buff the next Ninja attack.');
$check(!in_array('GO_AGAIN',$attack->TurnEffects,true),'Blaze gained go again without another red played.');
$blaze=AddStack(CardID:'blaze_headlong_red',Controller:1);FaBWTRCardPlayed(1,'Stack-2','blaze_headlong_red','Hand');
$check(in_array('GO_AGAIN',$blaze->TurnEffects,true),'Blaze did not gain go again after another red.');
$check(!in_array('WTR_POWER:3',$blaze->TurnEffects,true),'Rise buff affected more than one attack.');

$reset();
$s=FaBGetState();$s['attacker']=1;$s['defender']=4;$s['chainHits']=2;$s['daggerHits']=2;FaBSetState($s);
$salt=AddCombatChain(1,CardID:'salt_the_wound_yellow',Role:'ATTACK',ChainLink:3);$s['attackUID']=$salt->UniqueID;FaBSetState($s);
$check(FaBAttackPower($s)===4,'Salt the Wound did not count earlier hits.');
AddCombatChain(4,CardID:'ronin_renegade_red',Role:'DEFENSE',ChainLink:1);
AddCombatChain(4,CardID:'brand_with_cinderclaw_blue',Role:'DEFENSE',ChainLink:2);
$tenacity=AddCombatChain(1,CardID:'tenacity_yellow',Role:'ATTACK',ChainLink:4);
FaBRunSourceMacro('AttackDeclared',1,'tenacity_yellow',['mzID'=>'p1CombatChain-1','attacker'=>1,'defender'=>4]);
$check(in_array('WTR_POWER:2',$tenacity->TurnEffects,true),'Tenacity did not count defending cards on earlier links.');
FaBRunSourceMacro('Hit',1,'stab_wound_blue',['mzID'=>'p1CombatChain-1','amount'=>1]);
$check(intval(GetHealth(4))===18,'Stab Wound did not apply dagger-hit life loss.');
FaBWTRAddEffect(4,'AOW_ARSENAL',1);AddArsenal(4,CardID:'ronin_renegade_red');
$s=FaBGetState();$s['window']='DEFEND_DECLARE';FaBSetState($s);
$check(FaBCanBlock(4,'p4Arsenal-0'),'Art of War did not enable attack-action defense from arsenal.');

// Real four-seat bot self-play, including all generated decision continuations.
$reset();
foreach([1,2,3,4] as $p){
    $hero=&GetHero($p);$hero=[];AddHero($p,CardID:'fai',Owner:$p,Controller:$p,Status:2);
    foreach($deck['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p,Status:2);
    foreach($deck['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p,Status:2);
    foreach($deck['mainDeck'] as $id)AddDeck($p,CardID:$id);
    FaBShuffleDeck($p);FaBFaiSetup($p,true);
}
$s=FaBGetState();$s['botProfiles']=[1=>'fai',2=>'fai',3=>'fai',4=>'fai'];FaBSetState($s);
$steps=0;
for(;$steps<2500&&!intval(GetWinner());++$steps){
    $p=BotControllerPendingPlayerForClient();
    if(!$p){$failures[]='Self-play stalled without a pending bot: '.json_encode(FaBGetState());break;}
    $r=ProcessBotControllerStep(1,'FaBSim');
    if(empty($r['applied'])){$failures[]='Bot stalled for seat '.$p.' in '.FaBGetState()['window'].' decision '.json_encode(GetDecisionQueue($p));break;}
}
$check(intval(GetWinner())>0,'Self-play failed to finish in 2500 steps (turn '.GetTurnNumber().').');
if($failures){foreach($failures as $f)fwrite(STDERR,"FAIL: $f\n");exit(1);}
echo 'Fai card and bot checks passed; four-player match finished in '.$steps." steps.\n";
