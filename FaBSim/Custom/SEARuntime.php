<?php
function FaBSEAHero(int $p): bool {return FaBMONHero($p,'gravy_bones')||FaBMONHero($p,'marlynn')||FaBMONHero($p,'puffin')||FaBMONHero($p,'scurv_stowaway');}
function FaBSEAAbilityRows(): array {
 $r=[];
 foreach(['gravy_bones','gravy_bones_shipwrecked_looter'] as $id)$r[$id]=[['INSTANT',0,false,false,false,0,'Draw then discard']];
 foreach(['marlynn','marlynn_treasure_hunter','scurv_stowaway'] as $id)$r[$id]=[['ACTION',0,false,true,false,0,'Use hero ability']];
 foreach(['puffin','puffin_hightail'] as $id)$r[$id]=[['ACTION',0,false,false,false,0,'Create Golden Cog']];
 foreach(['blue_sea_tricorn'=>3,'buccaneers_bounty'=>0,'fish_fingers'=>1,'peg_leg'=>3,'quartermasters_boots'=>2,'captains_coat'=>0,'quick_clicks'=>0,'swiftstrike_bracers'=>0,'glidewell_fins'=>1,'bandana_of_the_blue_beyond'=>0] as $id=>$cost)$r[$id]=[['ACTION',$cost,true,true,false,0,'Use equipment']];
 foreach(['head_stone','old_knocker','rust_belt','unicycle','patch_the_hole'] as $id)$r[$id]=[['INSTANT',0,true,false,false,0,'Use equipment']];
 foreach(['compass_of_sunken_depths','dead_threads','sealace_sarong'] as $id)$r[$id]=[['INSTANT',0,false,false,false,0,'Use equipment']];
 foreach(['gold_baited_hook','redspine_manta'] as $id)$r[$id]=[['ACTION',0,false,true,false,0,'Use equipment']];
 $r['hammerhead_harpoon_cannon']=[['ACTION',4,false,true,false,0,'Aim cannon']];
 $r['goldkiss_rum']=[['INSTANT',0,true,false,false,0,'Drink Goldkiss Rum']];
 foreach(['amethyst','diamond','opal','platinum','ruby'] as $gem)$r[$gem.'_amulet_blue']=[['INSTANT',0,true,false,false,0,'Use amulet']];
 foreach(['onyx','pearl','pounamu','sapphire'] as $gem)$r[$gem.'_amulet_blue']=[['ACTION',0,true,true,false,0,'Use amulet']];
 foreach(['anka_drag_under','chum_friendly_first_mate','chowder_hearty_cook','sawbones_dock_hand','shelly_hardened_traveler'] as $b)$r[$b.'_yellow']=[['INSTANT',0,false,false,false,0,'Use ally ability']];
 $r['moray_le_fay_yellow']=[['INSTANT',1,false,false,false,0,'Give ally a power counter']];
 $r['kelpie_tangled_mess_yellow']=[['ACTION',1,false,true,false,0,'Tap hero or ally']];
 $r['cutty_shark_quick_clip_yellow']=[['ACTION',1,false,true,true,0,'Boost next ally attack']];
 foreach(['cloud_city_steamboat'=>2,'cloud_skiff'=>1,'cogwerx_dovetail'=>3,'cogwerx_zeppelin'=>2,'palantir_aeronought'=>3,'sky_skimmer'=>1,'jolly_bludger'=>3] as $b=>$limit)foreach(['red','yellow','blue'] as $c)$r[$b.'_'.$c]=[['INSTANT',0,false,false,false,0,'Tap a cog to enhance attack']];
 foreach(['rally_the_coast_guard_red','rally_the_coast_guard_yellow','rally_the_coast_guard_blue'] as $id)$r[$id]=[['INSTANT',0,false,false,true,0,'Discard for three defense']];
 foreach(['bam_bam_yellow','burn_bare','deny_redemption_red'] as $id)$r[$id]=[['INSTANT',0,false,false,false,0,'Discard to use instant']];
 $r['cogwerx_blunderbuss']=[['INSTANT',0,false,false,false,0,'Tap cog for next attack go again']];
 $r['polly_cranka']=[['ACTION',0,false,false,false,0,'Unequip Polly Cranka']];
 return $r;
}
function FaBSEACogLimit(string $id): int {return ['cloud_city_steamboat'=>2,'cloud_skiff'=>1,'cogwerx_dovetail'=>3,'cogwerx_zeppelin'=>2,'palantir_aeronought'=>3,'sky_skimmer'=>1,'jolly_bludger'=>3][FaBWTRBase($id)]??0;}
function FaBSEADiscardAbility(string $id): bool {return in_array($id,['bam_bam_yellow','burn_bare','deny_redemption_red'],true);}
function FaBSEATapCost(string $id): bool {return in_array($id,['gravy_bones','gravy_bones_shipwrecked_looter','marlynn','marlynn_treasure_hunter','puffin','puffin_hightail','scurv_stowaway','compass_of_sunken_depths','dead_threads','sealace_sarong','gold_baited_hook','redspine_manta','hammerhead_harpoon_cannon','polly_cranka','anka_drag_under_yellow','chum_friendly_first_mate_yellow','chowder_hearty_cook_yellow','sawbones_dock_hand_yellow','shelly_hardened_traveler_yellow','moray_le_fay_yellow','kelpie_tangled_mess_yellow'],true);}
function FaBSEAGoldCost(string $id): bool {return in_array($id,['gravy_bones','gravy_bones_shipwrecked_looter','marlynn','marlynn_treasure_hunter','puffin','puffin_hightail','scurv_stowaway'],true);}
function FaBSEACogCost(string $id): bool {return FaBSEACogLimit($id)>0||in_array($id,['rust_belt','cogwerx_blunderbuss'],true);}
function FaBSEASpecialZone(array $f): bool {
 $o=$f['object'];if(FaBSEADiscardAbility($o->CardID))return $f['zone']==='Hand';
 return $f['zone']==='CombatChain'&&intval($o->ChainLink)===intval(FaBGetState()['chainLink'])&&((FaBSEACogLimit($o->CardID)>0&&$o->Role==='ATTACK')||(FaBWTRBase($o->CardID)==='rally_the_coast_guard'&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)));
}
function FaBSEAAbilityLegal(int $p,array $f): bool {
 $o=$f['object'];$id=$o->CardID;$u=intval($o->UniqueID);
 if(FaBSEATapCost($id)&&!FaBSEACanTap($o))return false;
 if(FaBSEAGoldCost($id)&&FaBHVYGold($p)==='')return false;
 if(FaBSEACogCost($id)&&FaBSEARefs($p,'readyCog')==='')return false;
 if(FaBSEACogLimit($id)>0){if(!FaBSEASpecialZone($f))return false;if(intval(FaBARCCard($u,'seaCogTurn',-1))===intval(GetTurnNumber())&&intval(FaBARCCard($u,'seaCogUses'))>=FaBSEACogLimit($id))return false;}
 if($id==='burn_bare'){ $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||!FaBIsDefendingHero($p,$s)||!FaBHasKeyword($a['object'],'Phantasm'))return false;}if(FaBSEADiscardAbility($id))return $f['zone']==='Hand';
 if(FaBWTRBase($id)==='rally_the_coast_guard'&&(!FaBSEASpecialZone($f)||FaBHandCount($p)===0))return false;
 if(in_array($id,['anka_drag_under_yellow','chum_friendly_first_mate_yellow'],true)&&FaBSEARefs($p,'watery','Hand')==='')return false;
 if(in_array($id,['old_knocker','goldkiss_rum'],true)&&!FaBSEACanTap(GetHero($p)[0]))return false;
 if($id==='captains_coat'&&!FaBSEACount($p,'DRAWN'))return false;
 if(in_array($id,['quick_clicks','swiftstrike_bracers'],true)&&!FaBSEACount($p,'NIMBLISM'))return false;
 if($id==='dead_threads'&&!FaBSEACount($p,'ALLY_GRAVE'))return false;
 if($id==='bandana_of_the_blue_beyond'&&!FaBHandCount($p))return false;
 if($id==='sealace_sarong'&&!array_filter(GetArsenal($p),fn($a)=>!empty($a->FaceDown)&&FaBHasType($a,'Arrow')&&intval(CardPitch($a->CardID))===3))return false;
 return true;
}
function FaBSEAPaid(int $p,object $o): void {
 $id=$o->CardID;$u=intval($o->UniqueID);
 if(FaBSEATapCost($id))$o->Status=1;if($id==='polly_cranka')FaBMoveUID($u,'Banish',intval($o->Owner));
 if(FaBSEADiscardAbility($id))FaBDiscardChoice($p,FaBDTDSource($u));
 if(in_array($id,['old_knocker','goldkiss_rum'],true))GetHero($p)[0]->Status=1;
 if($id==='goldkiss_rum'){FaBSEAAdd($p,'RUM_LOCK');if(FaBMONHero($p,'scurv_stowaway'))AddResources($p,intval(GetResources($p))+1);}
 if($id==='hammerhead_harpoon_cannon')FaBSEAAdd($p,'CANNON');
 if(FaBSEACogLimit($id)>0){$n=intval(FaBARCCard($u,'seaCogTurn',-1))===intval(GetTurnNumber())?intval(FaBARCCard($u,'seaCogUses')):0;FaBARCSetCard($u,'seaCogUses',$n+1);FaBARCSetCard($u,'seaCogTurn',intval(GetTurnNumber()));}
}
function FaBSEAPower(int $p,object $o): int {$b=FaBWTRBase($o->CardID);$f=FaBFindUID(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0));$n=$f?intval(FaBObjectCounters($f['object'])['POWER']??0):0;if(FaBSEAHighTide($p)){$n+=match($b){'battalion_barque'=>2,'conqueror_of_the_high_seas','hms_barracuda','hms_kraken','hms_marlin'=>1,default=>0};}if($b==='gold_hunter_longboat'&&FaBSEALessGold($p))$n+=2;return $n;}
function FaBSEAGoAgain(int $p,object $o): bool {return $o->CardID==='limpit_hop_a_long_yellow'||(FaBSEAHighTide($p)&&in_array(FaBWTRBase($o->CardID),['swiftwater_sloop','conqueror_of_the_high_seas'],true));}
function FaBSEAOverpower(int $p,object $o): bool {return (FaBSEAHighTide($p)&&in_array(FaBWTRBase($o->CardID),['hms_barracuda','hms_kraken','hms_marlin'],true))||(FaBWTRBase($o->CardID)==='gold_hunter_marauder'&&FaBSEALessGold($p));}
function FaBSEACost(int $p,object $o): int {$n=0;if(FaBWTRBase($o->CardID)==='gold_hunter_ketch'&&FaBSEALessGold($p))$n-=2;if(FaBSEAWatery($o)&&FaBHasType($o,'Ally'))foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='SEA_QUARTER'&&intval($e['amount']??0)>0)$n-=3;return $n;}
function FaBSEAPlayed(int $p,object $o,string $from): void {
 if($from==='Weapons'&&$o->CardID==='claw_of_vynserakai')FaBARCSetCard(intval($o->SourceUniqueID),'seaClawTurn',intval(GetTurnNumber()));
 if(FaBWTRBase($o->CardID)==='nimblism')FaBSEAAdd($p,'NIMBLISM');
 if(FaBSEAWatery($o)&&FaBHasType($o,'Ally')){$effects=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='SEA_QUARTER'&&--$e['amount']<=0)continue;$effects[]=$e;}FaBWTRSetEffects($p,$effects);}
 if($from==='Graveyard'&&FaBSEAWatery($o)){if(!FaBSEACount($p,'WATERY_PLAY')&&FaBChoiceRefs($p,'Equipment',['base'=>'compass_of_sunken_depths']))FaBWTRTag($o,'GO_AGAIN');FaBSEAAdd($p,'WATERY_PLAY');}
 if(FaBWTRBase($o->CardID)==='blow_for_a_blow'&&FaBARCLowerLife($p))FaBWTRTag($o,'GO_AGAIN');$attack=($o->Kind??'')==='ATTACK'||FaBHasType($o,'Attack');$remaining=[];
 foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='SEA_NEXT'&&($attack||in_array($e['kind']??'',['naa','action'],true))&&FaBSEAMatches($o,$e['kind'])&&(!intval($e['source']??0)||intval($e['source'])===intval($o->SourceUniqueID??FaBObjectCounters($o)['WEAPON_UID']??0))){if($e['power'])FaBWTRTag($o,'WTR_POWER:'.$e['power']);if($e['tag']!=='')FaBWTRTag($o,$e['tag']);if($e['tag']==='SEA_RED_POWER'&&intval(CardPitch($o->CardID))===1)FaBWTRTag($o,'WTR_POWER:1');if($e['tag']==='SEA_HARPOON_OVERPOWER'&&str_contains($o->CardID,'harpoon'))FaBWTRTag($o,'OVERPOWER');if($e['tag']==='SEA_HARPOON_GOLD'&&str_contains($o->CardID,'harpoon'))FaBWTRTag($o,'SEA_GOLD_HIT');}else $remaining[]=$e;}FaBWTRSetEffects($p,$remaining);
}
function FaBSEAAfterMove(int $p,object $o,string $from,string $to,?object $source=null): void {
 if($to==='Graveyard'){FaBSEAGraveEntered($p,$o,$from,$source);if($from==='Arena'&&$o->CardID==='oysten_heart_of_gold_yellow')FaBHVYToken(intval($source->Controller??$p),'gold');}
 if($to==='Arena'&&$from!=='Arena'&&$o->CardID==='gold')FaBSEAAdd($p,'GOLD_GAINED');
 if($to==='Arena'&&$from===''&&$o->CardID==='golden_cog')FaBEVOEnter($p,$o);
}
function FaBSEADrew(int $p,int $n): void {
 if($n<1)return;FaBSEAAdd($p,'DRAWN',$n);if(GetCurrentPhase()!=='MAIN'||!empty(FaBGetState()['uprRefill']))return;
 foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Arena',['base'=>'escalate_bloodshed']) as $r)FaBARCLoseLife($p,$n,$v);
 if($p===intval(GetTurnPlayer())&&FaBMONHero($p,'marlynn'))for($i=0;$i<$n;$i++)FaBSEAQueue($p,GetHero($p)[0],'seaMarlynn');
 foreach(FaBOpponents($p) as $v)if(FaBSEACount($v,'ANKA')){FaBSEAClear($v,'ANKA');FaBROSQueue($p,'anka_drag_under_yellow',0,['rosEvent'=>'seaDiscard']);}
}
function FaBSEACranked(int $p): void {FaBSEAAdd($p,'CRANK');if(FaBSEACount($p,'CRANK')===2&&FaBMONHero($p,'puffin'))DoDrawCard($p,1);}
function FaBSEAStart(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena',['base'=>'shifting_tides']) as $r)FaBRunSourceMacro('StartTurn',$p,FaBIdentityFromMZ($r)['object']->CardID,['mzID'=>$r]);
 foreach(FaBChoiceRefs($p,'Arena',['base'=>'clap_em_in_irons']) as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBSEANeedsPrepare(string $id): bool {return FaBSEAGoldCost($id)||FaBSEACogCost($id)||in_array($id,['sealace_sarong','bandana_of_the_blue_beyond','anka_drag_under_yellow','chum_friendly_first_mate_yellow'],true)||FaBWTRBase($id)==='rally_the_coast_guard';}
function FaBSEAArsenalFaceUp(int $p,object $o,string $from): void {
 if($from==='Arsenal')return;$b=FaBWTRBase($o->CardID);
 if($b==='dry_powder_shot')FaBWTRTag($o,'WTR_POWER:2');
 if($b==='swift_shot')FaBWTRTag($o,'GO_AGAIN');
 if(in_array($b,['entangling_shot','nettling_shot','scouting_shot'],true))FaBSEAQueue($p,$o,'seaLoaded');
}
function FaBSEAEnd(int $p): void {
 foreach(FaBLiveSeats() as $v)foreach(FaBWTREffects($v) as $e)if(($e['type']??'')==='SEA_RETURN_CONTROL'){$f=FaBFindUID(intval($e['uid']));if($f)FaBSEASteal(intval($e['player']),$f['mzID']);}
 foreach(FaBChoiceRefs($p,'Equipment',['base'=>'gold_baited_hook']) as $r)if(!FaBSEACount($p,'GOLD_GAINED'))FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
 foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);if($b==='riddle_with_regret')FaBSEAQueue($v,$o,'seaRegret',count(FaBChoiceRefs($p,'Arena',['type'=>'Aura'])));if($v===$p&&$b==='loan_shark'&&!FaBSEACount($p,'GOLD_GAINED'))FaBSEAQueue($p,$o,'seaLoan');if($b==='escalate_bloodshed'&&!FaBHVYCount($p,'WEAPON_ATTACKED'))FaBMONDestroy(intval($o->UniqueID));}
}
function FaBSEAWeaponCanAttack(int $p,array $f): bool {
 $o=$f['object'];$id=$o->CardID;$s=FaBGetState();$cost=['spitfire'=>0,'cogwerx_blunderbuss'=>2,'claw_of_vynserakai'=>1,'sticky_fingers'=>0][$id]??null;
 return $cost!==null&&($id!=='claw_of_vynserakai'||FaBARCCard(intval($o->UniqueID),'seaClawTurn',-1)!==intval(GetTurnNumber()))&&$f['player']===$p&&in_array($f['zone'],['Weapons','Equipment'],true)&&!HasNoAbilities($o)&&!FaBUPRFrozen($o)&&FaBSeatIsLive($p)&&intval(GetPriorityPlayer())===$p&&intval(GetTurnPlayer())===$p&&!FaBHasPendingDecision()&&$s['pendingPayment']===null&&in_array($s['window'],['ACTION','RESOLUTION'],true)&&intval(GetActionPoints($p))>0&&FaBCRUWeaponReady($o)&&FaBAvailablePitch($p)>=FaBWTRAbilityCost($p,['timing'=>'ACTION','cost'=>$cost,'cardID'=>$id,'sourceUID'=>intval($o->UniqueID)])&&($id!=='spitfire'||FaBSEARefs($p,'readyCog')!=='')&&FaBDTDRestrictions($p,$o,true,true);
}
function FaBSEAWeaponAttack(int $p,array $f): bool {
 if(!FaBSEAWeaponCanAttack($p,$f))return false;$o=$f['object'];$u=intval($o->UniqueID);$id=$o->CardID;
 $target=FaBClaimOrRequestAttackTarget($p,$u,'ACTIVATE');if($target===null)return true;if($target===false)return false;
 if($id==='sticky_fingers'){$o=FaBMoveUID($u,'Arena',$p);return FaBMONArenaAttack($p,FaBFindUID($u),$target);}
 $stack=AddStack(CardID:$id,Controller:$p,Kind:'ATTACK',SourceZone:'Weapons',SourceUniqueID:$u,Params:['attackTarget'=>$target]);$stack->TurnEffects=(array)$o->TurnEffects;
 $cost=FaBWTRAbilityCost($p,['timing'=>'ACTION','cost'=>['spitfire'=>0,'cogwerx_blunderbuss'=>2,'claw_of_vynserakai'=>1][$id],'cardID'=>$id,'sourceUID'=>$u]);$s=FaBGetState();$s['pendingPayment']=['player'=>$p,'uid'=>intval($stack->UniqueID),'weaponUID'=>$u,'cost'=>$cost,'fromZone'=>'Weapons','kind'=>'ATTACK','isWeaponAttack'=>true,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];$s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);
 if($id==='spitfire')FaBRunSourceMacro('PrepareCard',$p,$id,['mzID'=>FaBDTDSource(intval($stack->UniqueID))]);else FaBTryCompletePayment();return true;
}
function FaBSEAAllyCost(object $o): ?int {if(!FaBHasType($o,'Ally'))return null;$text=(string)CardFunctional_text_plain($o->CardID);if(preg_match('/Action - ([^:]+): Attack/',$text,$m))return substr_count($m[1],'{r}');return null;}
function FaBSEADeclared(int $p,object $o): void {
 FaBSEAAdd($p,'ATTACKED');$s=FaBGetState();foreach($s['attackTargets']??[$s['attackTarget']??[]] as $t)if(($t['type']??'')==='HERO'){$v=intval($t['player']);if(!FaBSEACount($v,'ATTACKED_HERO'))FaBSEAIslandGold(1);FaBSEAAdd($v,'ATTACKED_HERO');}
 if(FaBHasType($o,'Club')&&FaBSEACount($p,'BAM'))FaBWTRTag($o,'SEA_BAM_HIT');
}
function FaBSEAHit(int $p,object $o,int $n): void {
 if($n<1)return;if(in_array('SEA_UNTAP_HERO',(array)$o->TurnEffects,true))FaBSEAUntap('p'.$p.'Hero-0');if(!FaBFaiHeroHit())return;$v=intval(FaBGetState()['defender']);$tags=(array)$o->TurnEffects;
 if(in_array('SEA_GOLD_HIT',$tags,true))FaBHVYToken($p,'gold');
 if(in_array('SEA_STEAL_GOLD',$tags,true)&&!FaBSEAStealGold($p,$v))FaBHVYToken($p,'gold');
 if(in_array('SEA_UNTAP_HERO',$tags,true))FaBSEAUntap('p'.$p.'Hero-0');
 if(in_array('SEA_ANCHOR',$tags,true))foreach(explode('&',FaBSEAPermanents($v,'heroAlly',false)) as $r)FaBSEATap($r);
 if(in_array('SEA_BAM_HIT',$tags,true))FaBROSQueue($p,'bam_bam_yellow',intval($o->UniqueID),['rosEvent'=>'seaBam','rosTarget'=>$v]);
}
function FaBSEAPrevent(int $p,int $n,?object $ally=null): int {
 if($n<=0)return $n;$keys=$ally===null?['CAUTION','SAWBONES']:(FaBHasType($ally,'Pirate')?['SAWBONES']:[]);
 foreach($keys as $key)if(FaBSEACount($p,$key)){$reduce=FaBSEACount($p,$key);FaBSEAClear($p,$key);$n=max(0,$n-$reduce);}return $n;
}
function FaBSEADamaged(int $p,int $v,int $n,string $ref=''): void {if($n>0&&$p!==$v)FaBHVYToken($p,'gold',FaBSEAIslandGold(-$n));$f=FaBIdentityFromMZ($ref);if($n>0&&$f&&FaBWTRBase($f['object']->CardID)==='jolly_bludger'){FaBARCSetCard(intval($f['object']->UniqueID),'seaDamage',$n);FaBSEAQueue($p,$f['object'],'seaJolly',$v);}}
function FaBSEADefended(int $p,object $o): void {if(FaBWTRIsAttackAction($o)&&FaBSEACount($p,'SHELLY')){FaBWTRTag($o,'WTR_DEFENSE:'.FaBSEACount($p,'SHELLY'));FaBSEAClear($p,'SHELLY');}}
function FaBSEADrawReplacement(int $p): bool {foreach(FaBOpponents($p) as $v)if(FaBSEACount($v,'NOT_SO_FAST')&&($GLOBALS['seaDrawSource']??'')==='gold'){FaBSEAClear($v,'NOT_SO_FAST');$old=$GLOBALS['seaDrawSource'];$GLOBALS['seaDrawSource']='';try{DoDrawCard($v,1);}finally{$GLOBALS['seaDrawSource']=$old;}return true;}return false;}
function FaBSEABegin(int $p): void {
 foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Arena',['base'=>'escalate_bloodshed']) as $r)FaBSEAQueue($v,FaBIdentityFromMZ($r)['object'],'seaEscalate',$p);
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);if($b==='preach_modesty'){$n=intval(FaBObjectCounters($o)['BALANCE']??0);if($n>0)FaBSetObjectCounter($o,'BALANCE',$n-1);else FaBMONDestroy(intval($o->UniqueID));}if($b==='surface_shaking')FaBSEAQueue($p,$o,'seaSurface',count(FaBChoiceRefs($p,'Arena',['base'=>'seismic_surge'])));}
 foreach(FaBChoiceRefs($p,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];$u=intval($o->UniqueID);if(intval(FaBARCCard($u,'seaReturnFire'))===$p){FaBARCSetCard($u,'seaReturnFire',0);if(FaBELEArsenalSpace($p)){FaBMoveUID($u,'Temp',$p);FaBSEAArsenal($p,FaBDTDSource($u),3);}}}
}
function FaBSEAChumTargets(int $p,array $targets): array {$forced=[];foreach($targets as $t){$f=FaBFindUID(intval($t['uid']));if(!$f||$f['zone']!=='Arena'||$f['object']->CardID!=='chum_friendly_first_mate_yellow'||HasNoAbilities($f['object']))continue;foreach(FaBWTREffects($f['player']) as $e)if(($e['type']??'')==='SEA_CHUM'&&intval($e['uid'])===intval($t['uid']))$forced[]=$t;}return $forced?:$targets;}
function FaBSEAActionAbilityPaid(int $p,object $stack): void {
 if(($stack->Params['arcSpec']['timing']??'')!=='ACTION')return;$left=[];
 foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='SEA_NEXT'&&($e['kind']??'')==='action')$stack->Params['arcSpec']['goAgain']=true;else $left[]=$e;}FaBWTRSetEffects($p,$left);
}
