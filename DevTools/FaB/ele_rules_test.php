<?php
require_once __DIR__.'/ele_test.php';
$failures=[];
$eleReset=function(int $seats)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}SetTurnNumber(10);SetTurnPlayer($seats);SetPriorityPlayer($seats);};
$elePlay=function(int $p,string $ref)use($answer){if(!DoPlayCard($p,$ref))throw new RuntimeException('Illegal ELE play '.$ref);$d=GetDecisionQueue($p)[0]??null;if($d&&str_contains($d->Tooltip,'attack_target'))$answer($p,'p1Hero-0');};
$eleResolve=function(){if($top=FaBStackTop())DoResolveCard(intval($top->Controller),FaBFindUID(intval($top->UniqueID))['mzID']);};
foreach([2,4] as $seats){$p=$seats;
 $eleReset($seats);AddResources($p,3);AddHand($p,CardID:'oaken_old_red');AddHand($p,CardID:'pulse_of_isenloft_blue');
 $elePlay($p,'p'.$p.'Hand-0');$d=GetDecisionQueue($p)[0];$check($d->Param==='p'.$p.'Hand-1','Fusion must use own hand.');$answer($p,$d->Param);$answer($p,'p'.$p.'Hand-1');
 $uid=intval(FaBStackTop()->UniqueID);$check(FaBELEFused($uid)&&FaBHandCount($p)===1,'Dual-element fusion discarded or failed.');$eleResolve();
 $s=FaBGetState();$check(FaBAttackPower($s)===9&&FaBCurrentAttackHasKeyword($s,'Dominate'),'Fused Oaken Old missed power/dominate.');
 AddHand(1,CardID:'autumns_touch_red');AddHand(1,CardID:'winters_grasp_blue');OnHit($p,FaBFindUID($uid)['mzID'],1);
 $check((GetDecisionQueue(1)[0]->Type??'')==='MZREARRANGE','Oaken Old did not let defender order cards.');$answer(1,GetDecisionQueue(1)[0]->Param);$check(FaBHandCount(1)===0&&count(FaBChoiceRefs(1,'Deck'))===2,'Oaken Old did not bottom two cards.');

 $eleReset($seats);AddResources($p,3);AddHand($p,CardID:'oaken_old_red');AddHand($p,CardID:'pulse_of_isenloft_blue');$elePlay($p,'p'.$p.'Hand-0');$answer($p,'PASS');$uid=intval(FaBStackTop()->UniqueID);$eleResolve();
 $check(!FaBELEFused($uid)&&FaBAttackPower(FaBGetState())===7&&!FaBELECount($p,'FUSED'),'Declining fusion still granted bonuses.');

 $eleReset($seats);FaBELEFrost($p,2);FaBWTRCreateArena(1,'channel_lake_frigid_blue');AddHand($p,CardID:'heavens_claws_blue');AddHand($p,CardID:'autumns_touch_blue');AddHand($p,CardID:'winters_grasp_blue');
 $check(FaBCardCost(GetHand($p)[0],$p)===4,'Frostbite and Channel costs did not stack.');$elePlay($p,'p'.$p.'Hand-0');
 $check(count(FaBMONArena($p,'frostbite'))===2&&FaBGetState()['window']==='PITCH','Frostbite disappeared before payment.');DoPitchCard($p,'p'.$p.'Hand-1');DoPitchCard($p,'p'.$p.'Hand-2');
 $check(count(FaBMONArena($p,'frostbite'))===0&&intval(GetResources($p))===2,'Tax payment failed to consume Frostbite.');

 $eleReset($seats);$h=&GetHero($p);$h=[];AddHero($p,CardID:'lexi',Owner:$p,Controller:$p);AddEquipment($p,CardID:'new_horizon',Owner:$p,Controller:$p);AddArsenal($p,CardID:'pulse_of_volthaven_red',FaceDown:1);AddHand($p,CardID:'blizzard_bolt_red');
 $check(!FaBELEArsenalSpace($p),'Face-down arsenal incorrectly adds a zone.');$check(FaBWTRActivate($p,'p'.$p.'Hero-0'),'Lexi activation rejected.');$answer($p,'p'.$p.'Arsenal-0');$eleResolve();$answer($p,'p1Hero-0');
 $check(FaBELEArsenalSpace($p)&&count(FaBMONArena(1,'frostbite'))===1,'Lexi reveal or Ice effect failed.');$check(FaBARCLoadArsenal($p,'p'.$p.'Hand-0',true),'New Horizon refused second arsenal.');
 $check(!FaBELEArsenalSpace($p)&&count(FaBChoiceRefs($p,'Arsenal'))===2,'New Horizon capacity incorrect.');FaBMONDestroy(intval(GetEquipment($p)[0]->UniqueID));$check(!FaBChoiceRefs($p,'Arsenal'),'Destroying New Horizon left arsenal cards.');

 $eleReset($seats);$h=&GetHero($p);$h=[];AddHero($p,CardID:'oldhim',Owner:$p,Controller:$p);AddHand($p,CardID:'pulse_of_isenloft_blue');AddHand(1,CardID:'heavens_claws_red');
 $a=AddCombatChain(1,CardID:'heavens_claws_red',Owner:1,Controller:1,Role:'ATTACK',ChainLink:1);$s=FaBGetState();$s['window']='REACTION';$s['attacker']=1;$s['defender']=$p;$s['attackUID']=intval($a->UniqueID);$s['chainLink']=1;$s['attackTarget']=['type'=>'HERO','player'=>$p,'uid'=>GetHero($p)[0]->UniqueID,'zone'=>'Hero'];$s['combatOpen']=true;FaBSetState($s);SetTurnPlayer(1);
 $check(FaBWTRActivate($p,'p'.$p.'Hero-0'),'Oldhim defense activation rejected.');DoPitchCard($p,'p'.$p.'Hand-0');$eleResolve();
 $check((GetDecisionQueue(1)[0]->Tooltip??'')==='Put_hand_card_on_top','Oldhim Ice did not ask attacking seat.');$answer(1,'p1Hand-0');
 $check(FaBHandCount(1)===0&&DoDamage(1,'',$p,3)===1,'Oldhim Earth/Ice pitch effects failed.');

 $eleReset($seats);$o=FaBWTRCreateArena($p,'channel_mount_heroic_red');$uid=intval($o->UniqueID);AddPitch($p,CardID:'autumns_touch_blue');AddPitch($p,CardID:'winters_grasp_blue');
 FaBEndTurn($p);$d=GetDecisionQueue($p)[0];$check($d->Type==='MZMULTICHOOSE'&&str_contains($d->Param,'p'.$p.'Pitch-0')&&!str_contains($d->Param,'Pitch-1'),'Channel upkeep selected wrong pitch element.');$answer($p,'p'.$p.'Pitch-0');
 $check(FaBFindUID($uid)['zone']==='Arena'&&count(FaBChoiceRefs($p,'Deck'))+FaBHandCount($p)===2&&!FaBChoiceRefs($p,'Pitch'),'Channel upkeep failed before pitch return.');
 SetTurnPlayer($p);SetPriorityPlayer($p);AddPitch($p,CardID:'autumns_touch_red');FaBELEEnd($p);$check(FaBFindUID($uid)['zone']==='Graveyard','Channel survived insufficient pitch for second flow counter.');

 $eleReset($seats);$h=&GetHero($p);$h=[];AddHero($p,CardID:'briar',Owner:$p,Controller:$p);AddResources($p,10);AddHand($p,CardID:'explosive_growth_red');AddHand($p,CardID:'autumns_touch_blue');$elePlay($p,'p'.$p.'Hand-0');$answer($p,'p'.$p.'Hand-1');$eleResolve();$answer($p,'p1Hero-0');
 $s=FaBGetState();$uid=intval($s['attackUID']);$check(intval(GetHealth(1))===19&&FaBELECount($p,'CHAIN_POWER')===1&&count(FaBMONArena($p,'embodiment_of_earth'))===1,'Explosive Growth arcane / Briar trigger failed.');
 DoDamage($p,FaBFindUID($uid)['mzID'],1,1);$check(FaBELECount($p,'CHAIN_POWER')===2&&count(FaBMONArena($p,'embodiment_of_earth'))===1,'Briar triggered twice or Growth missed physical damage.');
 FaBCloseCombatChain();$check(FaBELECount($p,'CHAIN_POWER')===0,'Growth buff survived chain closure.');

 $eleReset($seats);AddResources($p,3);AddEquipment($p,CardID:'spellbound_creepers',Owner:$p,Controller:$p);FaBELEAdd($p,'AA_COMBAT');AddHand($p,CardID:'weave_earth_red');AddActionPoints($p,0);
 $check(FaBWTRActivate($p,'p'.$p.'Equipment-0'),'Creepers activation failed.');$eleResolve();$elePlay($p,'p'.$p.'Hand-0');$eleResolve();
 $check(intval(GetActionPoints($p))===1&&!FaBELECount($p,'NEXT_INSTANT'),'Instant non-attack did not grant go again or consume permission.');
 $check(intval(FaBObjectCounters(GetEquipment($p)[0])['BIND']??0)===1,'Creepers did not pay bind counter.');FaBELEEnd($p);$check(!FaBChoiceRefs($p,'Equipment'),'Creepers survived insufficient arcane damage.');

 $eleReset($seats);AddResources($p,8);AddWeapons($p,CardID:'shiver',Owner:$p,Controller:$p);
 $check(FaBWTRActivate($p,'p'.$p.'Weapons-0'),'Shiver activation failed.');$eleResolve();$check(intval(GetActionPoints($p))===1&&!FaBWTRCanActivate($p,'p'.$p.'Weapons-0'),'Shiver granted AP or extra activation.');
 FaBELEAdd($p,'SNAP');$check(FaBWTRActivate($p,'p'.$p.'Weapons-0'),'Snap Shot did not grant extra bow use.');$eleResolve();$check(!FaBWTRCanActivate($p,'p'.$p.'Weapons-0'),'Snap Shot grants unlimited uses.');

 $eleReset($seats);AddResources($p,3);AddHand($p,CardID:'bramble_spark_red');AddHand($p,CardID:'autumns_touch_blue');AddHand($p,CardID:'heavens_claws_red');
 $elePlay($p,'p'.$p.'Hand-0');$answer($p,'p'.$p.'Hand-1');$eleResolve();$elePlay($p,'p'.$p.'Hand-2');$eleResolve();$answer($p,'p1Hero-0');
 $check(intval(GetHealth(1))===19&&FaBAttackPower(FaBGetState())===8,'Bramble Spark failed: health '.GetHealth(1).' power '.FaBAttackPower(FaBGetState()).' effects '.json_encode(FaBFindUID(intval(FaBGetState()['attackUID']))['object']->TurnEffects));

 $eleReset($seats);AddResources($p,3);AddHand($p,CardID:'sow_tomorrow_blue');AddGraveyard($p,CardID:'weave_earth_red');AddGraveyard($p,CardID:'autumns_touch_red');$elePlay($p,'p'.$p.'Hand-0');$eleResolve();
 $d=GetDecisionQueue($p)[0];$check($d->Param==='p'.$p.'Graveyard-1','Blue Sow Tomorrow ignored minimum cost or included itself.');$answer($p,$d->Param);$check(count(FaBChoiceRefs($p,'Banish'))===1&&count(FaBChoiceRefs($p,'Deck'))===1,'Sow Tomorrow failed return/banish.');

 $eleReset($seats);AddResources($p,1);AddHand($p,CardID:'pulse_of_candlehold_yellow');AddGraveyard($p,CardID:'autumns_touch_red');AddGraveyard($p,CardID:'heavens_claws_blue');$elePlay($p,'p'.$p.'Hand-0');$uid=intval(FaBStackTop()->UniqueID);$eleResolve();
 $d=GetDecisionQueue($p)[0];$check(!str_contains($d->Param,'Graveyard-2'),'Pulse can target itself.');$answer($p,'p'.$p.'Graveyard-0&p'.$p.'Graveyard-1');$d=GetDecisionQueue($p)[0];$answer($p,$d->Param);$check(count(FaBChoiceRefs($p,'Deck'))===2&&FaBFindUID($uid)['zone']==='Banish','Pulse did not return chosen cards in order.');

 $eleReset($seats);AddResources($p,3);AddWeapons($p,CardID:'titans_fist',Owner:$p,Controller:$p);FaBWTRAddEffect($p,'ELE_ENDLESS',1,['expiresAfterTurnOf'=>$p]);
 $check(FaBWTRActivate($p,'p'.$p.'Weapons-0'),'Weapon activation under Endless Winter failed.');if(GetDecisionQueue($p))$answer($p,'p1Hero-0');
 $check(count(FaBMONArena($p,'frostbite'))===1,'Endless Winter Frostbite was consumed by the same activation.');

 $eleReset($seats);AddHand($p,CardID:'ball_lightning_red');$elePlay($p,'p'.$p.'Hand-0');$eleResolve();$ref=FaBFindUID(intval(FaBGetState()['attackUID']))['mzID'];
 $check(DoDamage($p,$ref,1,1)===2,'Ball Lightning did not amplify Lightning damage.');FaBELEClose();$check(FaBELEDamageBonus($p,$ref,1,'PHYSICAL')===1,'Ball Lightning survived chain closure.');

 $eleReset($seats);$other=$seats===4?2:1;AddHealth($p,10);AddHealth($other,14);AddHand($p,CardID:'awakening_blue');AddHand($p,CardID:'autumns_touch_blue');AddDeck($p,CardID:'glacial_footsteps_blue');AddDeck($p,CardID:'heavens_claws_red');
 $elePlay($p,'p'.$p.'Hand-0');$answer($p,'p'.$p.'Hand-1');$eleResolve();$check(str_contains(GetDecisionQueue($p)[0]->Param,'p'.$other.'Hero-0'),'Awakening omitted nonadjacent opposing hero.');$answer($p,'p'.$other.'Hero-0');
 $check(count(FaBMONArena($p,'seismic_surge'))===8,'Awakening ignored chosen life difference or fusion.');$d=GetDecisionQueue($p)[0];$answer($p,explode('&',$d->Param)[0]);$check(count(FaBChoiceRefs($p,'Hand',['base'=>'glacial_footsteps']))===1&&!FaBChoiceRefs($p,'Temp'),'Awakening search failed.');

 $eleReset($seats);$k=FaBWTRCreateArena($p,'korshem_crossroad_of_elements');$uid=intval($k->UniqueID);AddHand(1,CardID:'pulse_of_isenloft_blue');FaBRevealChoices(1,'p1Hand-0');$check((GetDecisionQueue(1)[0]->Tooltip??'')==='Korshem_reveal_bonus','Korshem asked the wrong hero.');$answer(1,'0');$check(intval(GetResources(1))===1,'Korshem failed resource mode.');FaBELEEnd($p);$check(FaBFindUID($uid)['zone']==='Arena','Active Korshem was destroyed.');SetTurnNumber(11);FaBELEEnd($p);$check(FaBFindUID($uid)['zone']==='Graveyard','Inactive Korshem survived.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}echo "ELE outcome checks passed in duels and UPF.\n";
