<?php
require_once __DIR__.'/evr_test.php';
$failures=[];
foreach([2,4] as $seats){
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);
 $a=AddCombatChain($p,CardID:'swarming_gloomveil_red',Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:1);
 $s=FaBGetState();$s['attacker']=$p;$s['defender']=1;$s['attackUID']=$a->UniqueID;$s['chainLink']=1;$s['combatOpen']=true;FaBSetState($s);
 FaBEVRCreate($p,'runechant',3);FaBEVRAttack($p,$a);
 $check(in_array('GO_AGAIN',$a->TurnEffects,true)&&in_array('WTR_POWER:1',$a->TurnEffects,true)&&in_array('EVR_NO_PREVENT',$a->TurnEffects,true),'Gloomveil failed to count created auras.');
 $check(!FaBEVRCount(1,'AURAS'),'Aura counts leaked to another hero.');
 $f=FaBWTRCreateArena($p,'runeblood_incantation_red');$uid=$f->UniqueID;
 $check(intval(FaBObjectCounters($f)['VERSE'])===3,'Incantation missing verse counters.');
 $before=FaBARCRunechants($p);FaBEVRStart($p);$check(FaBARCRunechants($p)===$before+1&&intval(FaBObjectCounters($f)['VERSE'])===2,'Incantation did not generate a Runechant.');
 $pounder=FaBWTRCreateArena($p,'teklo_pounder_blue');FaBEVRBoost($p,intval($a->UniqueID));FaBEVRBoost($p,intval($a->UniqueID));
 $check(intval(FaBObjectCounters($pounder)['STEAM'])===2,'Pounder triggered more than once.');
 $sphere=FaBWTRCreateArena(1,'dissolution_sphere_yellow');$before=intval(GetHealth(1));DoDamage($p,FaBFindUID(intval($a->UniqueID))['mzID'],1,1,'PHYSICAL');$check(intval(GetHealth(1))===$before,'Sphere failed to prevent one.');
 FaBWTRAddEffect(1,'EVR_NO_PREVENT',1,['source'=>$p]);FaBWTRCreateArena(1,'spectral_shield');DoDamage($p,FaBFindUID(intval($a->UniqueID))['mzID'],1,1,'ARCANE');$check(intval(GetHealth(1))===$before-1&&count(FaBMONArena(1,'spectral_shield'))===0,'Unpreventable arcane must destroy Ward without reducing damage.');
 FaBWTRCreateArena($p,'signal_jammer_blue');$s=FaBGetState();$s['cardsPlayedThisTurn']['1']=['take_aim_red'];FaBSetState($s);$o=AddHand(1,CardID:'read_the_glide_path_red');$check(!FaBEVRCanPlay(1,FaBFindUID(intval($o->UniqueID))),'Signal Jammer did not restrict opposing hero.');
 $o=AddHand($p,CardID:'heavens_claws_blue');$hidden=FaBEVRPrivateHand(1,$p);$check($hidden&&FaBFindUID(intval($o->UniqueID))['zone']==='Hand','Private view moved the real hand.');FaBEVRForgetHand($hidden);$check(FaBFindUID($hidden[0])===null,'Private hand view persisted.');
 $w=AddWeapons($p,CardID:'dreadbore',Owner:$p,Controller:$p);FaBEVRExtraBow(FaBFindUID(intval($w->UniqueID))['mzID'],2);$check(intval(FaBObjectCounters($w)['EVR_BOW_USES'])===2,'Tri-shot lost bow identity.');
 $rep=AddCombatChain($p,CardID:'fractal_replication_red',Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:1);$herald=AddCombatChain($p,CardID:'herald_of_erudition_yellow',Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:1);FaBEVRFractalCopy($p,$rep);$check(FaBEVRFractalValue($rep,'POWER')===5&&FaBHasKeyword($rep,'Phantasm'),'Fractal failed to copy stats/phantasm.');
 $ward=FaBWTRCreateArena($p,'haze_bending_blue');$before=count(FaBMONArena($p,'spectral_shield'));FaBMONDestroy(intval($ward->UniqueID));$check(count(FaBMONArena($p,'spectral_shield'))===$before+1,'Haze Bending failed to see its own destruction.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);
 $h=AddHand($p,CardID:'pulverize_red');$heaveUID=intval($h->UniqueID);AddHand($p,CardID:'thunder_quake_blue');
 FaBRunSourceMacro('ResolveAbility',$p,'pulverize_red',['mzID'=>FaBFindUID($heaveUID)['mzID']]);
 $check((GetDecisionQueue($p)[0]->Tooltip??'')==='Heave_a_card','Heave did not offer card choice.');$answer($p,FaBFindUID($heaveUID)['mzID']);
 $check(!str_contains(GetDecisionQueue($p)[0]->Param,FaBFindUID($heaveUID)['mzID']),'Heave offered itself as pitch.');$answer($p,explode('&',GetDecisionQueue($p)[0]->Param)[0]);
 $check(FaBFindUID($heaveUID)['zone']==='Arsenal'&&intval(FaBFindUID($heaveUID)['object']->FaceDown)===0&&count(FaBMONArena($p,'seismic_surge'))===3,'Heave did not load face-up and make three Surge tokens.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);
 $h=AddHand($p,CardID:'imposing_visage_blue');$check(FaBCardCost($h,$p)===3,'Imposing Visage lost its fixed cost.');
 $brew=AddHand($p,CardID:'crazy_brew_blue');$party=AddStack(CardID:'life_of_the_party_red',Controller:$p,Kind:'ATTACK',SourceZone:'Hand');$uid=intval($party->UniqueID);
 $s=FaBGetState();$s['pendingPayment']=['player'=>$p,'uid'=>$uid,'cost'=>2];FaBSetState($s);FaBEVRLifeParty($p,$uid,FaBFindUID(intval($brew->UniqueID))['mzID']);
 $check(intval(FaBGetState()['pendingPayment']['cost'])===0&&count($party->TurnEffects)===3,'Crazy Brew failed to choose all modes and waive cost.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);
 FaBWTRCreateArena($p,'talisman_of_recompense_yellow');$check(FaBEVRPitch($p,1,false)===3&&FaBEVRPitch($p,1,true)===3&&!FaBMONArena($p,'talisman_of_recompense'),'Recompense did not replace one resource with three.');
 $attack=AddCombatChain(1,CardID:'bingo_red',Owner:1,Controller:1,Role:'ATTACK',ChainLink:1);$uid=intval($attack->UniqueID);$s=FaBGetState();$s['attacker']=1;$s['defender']=$p;$s['attackUID']=$uid;$s['chainLink']=1;$s['combatOpen']=true;FaBSetState($s);
 FaBWTRAddEffect($p,'EVR_STEADFAST',6,['sourceUID'=>$uid]);$before=intval(GetHealth($p));DoDamage(1,FaBFindUID($uid)['mzID'],$p,5,'PHYSICAL');DoDamage(1,FaBFindUID($uid)['mzID'],$p,3,'PHYSICAL');$check(intval(GetHealth($p))===$before-2,'Steadfast did not consume a source-specific prevention pool.');
 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);FaBEVRRound($p);
 $saved=FaBWTREffects(1);$check(count(array_filter($saved,fn($e)=>($e['expiresAtStartOf']??0)===$p))===1,'Round duration is missing its originating seat.');
 FaBEVRStart(1);$check(FaBEVRCount(1,'ROUND')===1,'Round expired on another hero turn.');FaBEVRStart($p);$check(FaBEVRCount(1,'ROUND')===0,'Round did not expire on owner next turn.');

 $arcReset($seats);$p=$seats;SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(10);
 $a=AddCombatChain($p,CardID:'romping_club',Owner:$p,Controller:$p,Role:'ATTACK',ChainLink:1);FaBWTRTag($a,'EVR_SHATTER');
 $e=AddCombatChain(1,CardID:'ironrot_helm',Owner:1,Controller:1,Role:'DEFENSE',FromZone:'Equipment',ChainLink:1);$equipmentUID=intval($e->UniqueID);
 $s=FaBGetState();$s['attacker']=$p;$s['defender']=1;$s['attackUID']=$a->UniqueID;$s['chainLink']=1;$s['combatOpen']=true;$s['attackTarget']=['type'=>'HERO','player'=>1,'uid'=>GetHero(1)[0]->UniqueID,'zone'=>'Hero'];FaBSetState($s);$before=intval(GetHealth(1));
 FaBBeginDamageStep();$check((GetDecisionQueue($p)[0]->Tooltip??'')==='Destroy_equipment_instead_of_damage','Shatter did not offer its replacement.');
 $answer($p,FaBFindUID($equipmentUID)['mzID']);$check(FaBFindUID($equipmentUID)['zone']==='Graveyard'&&intval(GetHealth(1))===$before&&FaBGetState()['window']==='DAMAGE','Shatter dealt damage or failed to resume damage step.');

}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}echo "EVR outcomes passed in duels and UPF.\n";
