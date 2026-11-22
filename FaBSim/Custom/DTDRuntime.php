<?php
function FaBDTDAfterMove(int $p,object $o,string $from,string $to): void {
 FaBAdvanceCapturedSourceEpochs($o,$from,$to,['dtdRadiantSourceEpoch']);
 if($from==='Banish'&&$to!=='Banish'){$s=FaBGetState();unset($s['dtdPermissions'][intval($o->UniqueID??0)]);FaBSetState($s);}
 if($from==='CombatChain'&&$to!=='CombatChain'&&!empty(FaBObjectCounters($o)['DTD_ORIGINAL_ID']))$o->CardID=FaBObjectCounters($o)['DTD_ORIGINAL_ID'];
 if($from==='Arena'&&$to!=='Arena'){$front=array_search($o->CardID,FaBDTDAngels(),true);if($front!==false)$o->CardID=$front;}
 $radiant=[];if($to==='Arena'&&$from!=='Arena'&&$o->CardID==='radiant_forcefield_yellow')$radiant[intval($o->UniqueID??0)]=true;
 if($to==='Soul'){
  if(FaBDTDHerald($o)){FaBDTDAdd($p,'HERALD_SOUL');if(GetCurrentPhase()==='MAIN'&&empty(FaBGetState()['dtdStarting'])&&(FaBMONHero($p,'prism_advent_of_thrones')||FaBMONHero($p,'prism_awakener_of_sol')))FaBRunSourceMacro('ResolveAbility',$p,'prism_advent_of_thrones',['dtdSearch'=>true,'mzID'=>FaBDTDSource(intval($o->UniqueID))]);}
 }
 if($to==='Banish'){
  if($from==='Hand')FaBSetObjectCounter($o,'DTD_HAND_BANISH',intval(GetTurnNumber()));
  
 }
 if($from==='Soul'&&$to!=='Soul'&&FaBMONSoul($p)==='')foreach(FaBMONArena($p,'radiant_forcefield') as $r){
  $found=FaBIdentityFromMZ($r);if($found!==null)$radiant[intval($found['object']->UniqueID??0)]=true;
 }
 // Deduplicate only this move event, never a still-pending layer or source epoch.
 foreach(array_keys($radiant) as $uid)FaBDTDCaptureRadiantEmptySoul($p,intval($uid));
}
/** Existing move events observe only the selected Radiant state transitions. */
function FaBDTDCaptureRadiantEmptySoul(int $p,int $uid): void {
 if(!FaBSeatIsLive($p)||$uid<1||FaBMONSoul($p)!=='')return;
 $found=FaBFindUID($uid);if($found===null||$found['zone']!=='Arena')return;
 $source=$found['object'];$controller=intval($source->Controller??$found['player']);
 if($source->CardID!=='radiant_forcefield_yellow'||$controller!==$p||HasNoAbilities($source))return;
 $epoch=FaBArmCapturedSourceEpoch($uid,'dtdRadiantSourceEpoch');
 FaBROSQueue($p,'radiant_forcefield_yellow',$uid,['rosEvent'=>'dtdRadiantEmptySoul','dtdRadiantSourceEpoch'=>$epoch]);
}
function FaBDTDResolveRadiantEmptySoul(int $p,object $layer): void {
 $params=is_array($layer->Params??null)?$layer->Params:[];
 $uid=intval($layer->SourceUniqueID??0);$epoch=intval($params['dtdRadiantSourceEpoch']??0);
 if(!FaBSeatIsLive($p)||($layer->Kind??'')!=='ABILITY'||intval($layer->Controller??0)!==$p
    ||($layer->CardID??'')!=='radiant_forcefield_yellow'||($params['rosTrigger']??false)!==true
    ||($params['rosEvent']??'')!=='dtdRadiantEmptySoul'||$uid<1||$epoch<1)return;
 $source=FaBFindUID($uid);
 if($source===null||$source['zone']!=='Arena'||intval(FaBARCCard($uid,'dtdRadiantSourceEpoch',0))!==$epoch)return;
 // The earned effect survives later Soul, ability, name and control changes, not a new Arena entry.
 $previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$p;
 try{FaBMONDestroy($uid);}finally{$GLOBALS['fabEffectController']=$previous;}
}
function FaBDTDCharged(int $p,string $id): void {
 $token=['banneret_of_courage_yellow'=>'courage','banneret_of_gallantry_yellow'=>'quicken','banneret_of_protection_yellow'=>'spellbane_aegis'][$id]??null;if($token)FaBWTRCreateArena($p,$token);
 if($id==='banneret_of_resilience_yellow')FaBELEAdd($p,'NEXT_DEFENSE');
 if($id==='banneret_of_vigor_yellow')FaBDTDAdd($p,'VIGOR');
}
function FaBDTDPlayed(int $p,object $o,string $from): void {
 $b=FaBWTRBase($o->CardID);$aa=FaBWTRIsAttackAction($o);$uid=intval($o->UniqueID);$rune=FaBDTDRuneGate($p,$o);if($rune)FaBARCSetCard($uid,'dtdRuneGate',true);
 if($from==='Banish'&&FaBMONHero($p,'blasmophet_levia_consumed')&&!FaBDTDBanishPlayable($p,$o,false)&&!FaBMONBanishPlayable($p,$o)&&empty($o->PlayableFromBanish))FaBDTDAdd($p,'BLASMOPHET_PLAY');
 if(FaBHasType($o,'Action')&&!$aa){foreach(FaBMONArena($p,'eloquence') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));FaBWTRTag($o,'GO_AGAIN');}
  if(FaBHasType($o,'Shadow')&&(FaBMONHero($p,'vynnset')||FaBMONHero($p,'vynnset_iron_maiden')))FaBRunSourceMacro('ResolveAbility',$p,'vynnset',['mzID'=>FaBDTDSource($uid)]);
 }
 $left=[];foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';$applies=($t==='DTD_NEXT_RUNE'&&$rune)||($t==='DTD_NEXT_ANGEL'&&FaBHasType($o,'Angel'))||($t==='DTD_NEXT_RUNEBLADE'&&$aa&&FaBHasType($o,'Runeblade'))||($t==='DTD_NEXT_CHARGE'&&$aa&&FaBARCCard($uid,'dtdCharged'))||($t==='DTD_NEXT_SWORD'&&FaBWTRIsWeapon($o)&&FaBHasType($o,'Sword'));
  if($t==='DTD_BEQUEST'&&$aa&&FaBHasType($o,'Runeblade'))continue;
  if(!$applies){$left[]=$e;continue;}
  if(intval($e['amount']??0))FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));if($t==='DTD_NEXT_SWORD')FaBWTRTag($o,'DTD_COURAGE_HIT');
 }FaBWTRSetEffects($p,$left);
 if($b==='chains_of_mephetis'&&$from==='Banish')FaBSetObjectCounter($o,'DTD_DOOM',1);
 if($b==='flail_of_agony')FaBARCLoseLife($p,1,$p);
}
function FaBDTDPower(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$s=FaBGetState();$victim=intval($s['defender']);$n=0;$six=FaBMONCount($p,'BANISHED_SIX')>0;
 if($b==='battlefield_breaker'&&$six)++$n;
 if($b==='diabolic_offering')$n+=($six?6:0)-intval(CardPower($o->CardID));
 if($b==='beaming_blade'&&FaBMONCount($p,'YELLOW_SOUL'))$n+=5;
 if($b==='searing_ray'&&FaBChoiceRefs($p,'Pitch',['pitch'=>2]))$n+=2;
 if($b==='soul_butcher'&&FaBMONSoul($victim)!=='')$n+=2;
 if($b==='lay_to_rest'&&FaBHasType(GetHero($victim)[0]??'','Shadow'))++$n;
 if(FaBHasType($o,'Brute')||FaBHasType($o,'Shadow'))$n+=FaBDTDCount($p,'FRENZY');
 if(FaBWTRIsAttackAction($o))foreach(FaBOpponents($p) as $seat)$n-=FaBDTDCount($seat,'TRIUMPH');
 if(FaBHasType($o,'Angel')){$f=FaBFindUID(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??$o->UniqueID));if($f)$n+=intval(FaBObjectCounters($f['object'])['POWER']??0);}
 return $n;
}
function FaBDTDDefense(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;if($b==='diabolic_offering')$n+=(FaBMONCount($p,'BANISHED_SIX')?6:0)-intval(CardDefense($o->CardID));
 if(FaBHasType($o,'Light')&&!FaBHasType($o,'Equipment'))foreach(FaBLiveSeats() as $seat)$n+=FaBDTDCount($seat,'DAYBREAK');return $n;
}
function FaBDTDGoAgain(int $p,object $o,array $s): bool {$b=FaBWTRBase($o->CardID);return ($b==='blistering_assault'&&count(FaBChoiceRefs($p,'Pitch',['pitch'=>2]))>0)||($b==='soul_cleaver'&&FaBMONSoul(intval($s['defender']))!=='');}
function FaBDTDDefended(int $p,object $o): void {
 if(FaBWTRBase($o->CardID)==='defender_of_daybreak'){$a=FaBFindUID(intval(FaBGetState()['attackUID']));if($a&&FaBHasType($a['object'],'Shadow'))FaBDTDAdd($p,'DAYBREAK');}
 if(!in_array(intval($o->UniqueID),(array)(FaBGetState()['declaredBlockUIDs']??[]),true))FaBDTDDefendEvents($p,[$o]);
 FaBDTDMirage();
}
// Mirage is state-triggered (CR 8.3.25, 6.6.5c); its layer rechecks CR 5.3.2a.
function FaBDTDMirageCondition(array $s, object $defender): bool {
 $a=FaBFindCurrentAttack($s); // Keep the qualified current-attack lookup (085).
 return !empty($s['combatOpen']) && $a!==null
  && intval($defender->ChainLink??0)===intval($s['chainLink']??0)
  && in_array($defender->Role??'', ['DEFENSE','DEFENSE_REACTION'], true)
  && !FaBHasType($a['object'],'Illusionist') && FaBAttackPower($s)>=6;
}
function FaBDTDMiragePendingIDs(int $uid, int $epoch): array {
 $ids=[];if($epoch<=0)return $ids;
 foreach(GetStack() as $layer){
  if(is_object($layer) && empty($layer->removed) && ($layer->Params['rosEvent']??'')==='dtdMirage'
   && intval($layer->SourceUniqueID??0)===$uid && intval($layer->Params['dtdMirageEpoch']??0)===$epoch)$ids[]=intval($layer->UniqueID);
 }
 return array_values(array_unique($ids));
}
function FaBDTDMirage(): void {
 $s=FaBGetState();if(($s['window']??'')==='DEFEND_DECLARE')return;
 foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){
  $o=FaBIdentityFromMZ($r)['object'];$uid=intval($o->UniqueID);
  $meta=(array)FaBARCCard($uid,'dtdMirage',[]);
  if($meta){
   $pending=FaBDTDMiragePendingIDs($uid,intval($meta['epoch']??0));
   if(($meta['pendingIDs']??[])!==$pending){$meta['pendingIDs']=$pending;FaBARCSetCard($uid,'dtdMirage',$meta);}
  }
  // Observe false states even while older layers are pending (CR 6.6.5c).
  $qualifies=!HasNoAbilities($o) && FaBHasKeyword($o,'Mirage') && FaBDTDMirageCondition($s,$o);
  if(!$qualifies){
   if(!empty($meta['activeAttack'])){$meta['activeAttack']=0;$meta['activeLink']=0;FaBARCSetCard($uid,'dtdMirage',$meta);}
   continue;
  }
  $attack=intval($s['attackUID']);$link=intval($s['chainLink']);
  if(intval($meta['activeAttack']??0)===$attack && intval($meta['activeLink']??0)===$link)continue;
  $controller=intval($o->Controller??$p)?:$p;if(!FaBSeatIsLive($controller))continue;
  $epoch=intval($meta['epoch']??0);$transition=intval($meta['transition']??0)+1;
  $meta['activeAttack']=$attack;$meta['activeLink']=$link;$meta['transition']=$transition;
  FaBARCSetCard($uid,'dtdMirage',$meta);
  FaBROSQueue($controller,$o->CardID,$uid,['rosEvent'=>'dtdMirage','dtdMirageAttack'=>$attack,'dtdMirageLink'=>$link,'dtdMirageEpoch'=>$epoch,'dtdMirageTransition'=>$transition]);
  $layer=FaBStackTop();
  if(!$layer || ($layer->Params['rosEvent']??'')!=='dtdMirage' || intval($layer->SourceUniqueID)!==$uid || intval($layer->Params['dtdMirageTransition']??0)!==$transition){$meta['activeAttack']=0;$meta['activeLink']=0;FaBARCSetCard($uid,'dtdMirage',$meta);continue;}
  if($epoch===0){$epoch=intval($layer->UniqueID);$meta['epoch']=$epoch;$layer->Params['dtdMirageEpoch']=$epoch;}
  $meta['pendingIDs']=FaBDTDMiragePendingIDs($uid,$epoch);
  FaBARCSetCard($uid,'dtdMirage',$meta);
 }
}
function FaBDTDResolveMirage(int $p, object $layer): void {
 $uid=intval($layer->SourceUniqueID??0);$epoch=intval($layer->Params['dtdMirageEpoch']??0);$event=intval($layer->UniqueID??0);
 $transition=intval($layer->Params['dtdMirageTransition']??0);
 $attack=intval($layer->Params['dtdMirageAttack']??0);$link=intval($layer->Params['dtdMirageLink']??0);
 $f=FaBFindUID($uid);$meta=(array)FaBARCCard($uid,'dtdMirage',[]);
 if(!$f || $f['zone']!=='CombatChain' || $epoch<=0 || $event<=0 || $transition<=0 || $attack<=0 || $link<=0
  || intval($meta['epoch']??0)!==$epoch || !in_array($event,(array)($meta['pendingIDs']??[]),true))return;
 // DoResolveCard removes this layer before dispatch. Consume only this event ID;
 // a newer transition/latch or another pending event must remain untouched.
 $meta['pendingIDs']=array_values(array_filter((array)$meta['pendingIDs'],fn($id)=>intval($id)!==$event));
 FaBARCSetCard($uid,'dtdMirage',$meta);
 $s=FaBGetState();
 if(intval($s['attackUID']??0)!==$attack || intval($s['chainLink']??0)!==$link || !FaBDTDMirageCondition($s,$f['object']))return;
 $current=FaBFindUID($uid);$live=(array)FaBARCCard($uid,'dtdMirage',[]);
 if(!$current || $current['zone']!=='CombatChain' || intval($live['epoch']??0)!==$epoch)return;
 // Later loss of Mirage/abilities does not cancel an already earned layer.
 // Keep the observed-state latch through callbacks; never rewrite after destroy.
 FaBMONDestroy($uid);
}
function FaBDTDDefendGroup(int $p,array $uids): void {
 $objects=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['player']===$p)$objects[]=$f['object'];}
 foreach($objects as $o){$others=array_filter($objects,fn($x)=>$x->UniqueID!==$o->UniqueID&&($x->FromZone??'')==='Hand');if(!$others)continue;
  if(in_array($o->CardID,['bastion_of_unity','plating_of_unity','pillar_of_unity'],true))FaBWTRTag($o,'WTR_DEFENSE:1');
  $token=['alluring_inducement_yellow'=>'eloquence','anthem_of_spring_blue'=>'embodiment_of_earth','call_down_the_lightning_yellow'=>'embodiment_of_lightning','chorus_of_ironsong_yellow'=>'courage','northern_winds_blue'=>'spellbane_aegis','star_struck_yellow'=>'seismic_surge'][$o->CardID]??null;
  if($token)FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID)),'dtdLightningDamage'=>0]);
  if($o->CardID==='united_we_stand_yellow')FaBDTDParty($p);
 }
 FaBDTDDefendEvents($p,$objects);
}
function FaBDTDDefendEvents(int $p,array $objects): void {
 $s=FaBGetState();$attacker=intval($s['attacker']);$a=FaBFindUID(intval($s['attackUID']));
 if($a&&array_filter($objects,fn($o)=>($o->FromZone??'')==='Hand'))for($i=0;$i<FaBDTDCount($attacker,'LIGHTNING');++$i)FaBRunSourceMacro('ResolveAbility',$attacker,'call_down_the_lightning_yellow',['mzID'=>$a['mzID'],'dtdLightningDamage'=>1,'dtdLightningTarget'=>$p]);
 if($a&&$a['object']->CardID==='decimator_great_axe'&&array_filter($objects,fn($o)=>!FaBHasType($o,'Equipment'))&&!FaBDTDCount($attacker,'DECIMATOR')){FaBDTDAdd($attacker,'DECIMATOR');FaBRunSourceMacro('ResolveAbility',$attacker,'decimator_great_axe',['mzID'=>$a['mzID']]);}
}
function FaBDTDHit(int $p,object $o,int $n): void {
 if($n<=0)return;$uid=intval($o->UniqueID);$s=FaBGetState();$r=FaBDTDSource($uid);
 if(FaBDTDCount($p,'VIGOR')){AddResources($p,intval(GetResources($p))+FaBDTDCount($p,'VIGOR'));FaBDTDClear($p,'VIGOR');}
 foreach((array)$o->TurnEffects as $tag){if($tag==='DTD_DRAW_HIT')DoDrawCard($p,1);if($tag==='DTD_AGAIN_HIT')FaBWTRTag($o,'GO_AGAIN');if($tag==='DTD_COURAGE_HIT'&&FaBFaiHeroHit())FaBWTRCreateArena($p,'courage');}
 if(FaBWTRIsAttackAction($o)){
  for($i=0;$i<FaBDTDCount($p,'SPIRIT');++$i)FaBWTRCreateArena($p,'courage');
  for($i=0;$i<FaBDTDCount($p,'BECKON');++$i)FaBRunSourceMacro('ResolveAbility',$p,'beckoning_light_red',['mzID'=>$r]);
 }
 if(FaBFaiHeroHit()&&FaBDTDCount($p,'HACK')){FaBDTDClear($p,'HACK');FaBRunSourceMacro('ResolveAbility',$p,'hack_to_reality_yellow',['mzID'=>$r,'amount'=>$n,'dtdVictim'=>intval($s['defender'])]);}
}
function FaBDTDStart(int $p): void {
 $s=FaBGetState();foreach($s['dtdPermissions']??[] as $uid=>$e)if((!$e['next']&&intval($e['created'])!==intval(GetTurnNumber()))||(!empty($e['next'])&&intval($e['activeTurn']??0)>0&&intval($e['activeTurn'])<intval(GetTurnNumber())))unset($s['dtdPermissions'][$uid]);FaBSetState($s);
 foreach(FaBMONArena($p,'chains_of_mephetis') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($o)['DTD_DOOM']??0)>0)FaBSetObjectCounter($o,'DTD_DOOM',0);else FaBMONDestroy(intval($o->UniqueID));}
 if(FaBMONHero($p,'vynnset'))FaBRunSourceMacro('StartTurn',$p,'vynnset',['mzID'=>FaBDTDSource(FaBUPRHeroUID($p))]);
}
function FaBDTDEnd(int $p): void {FaBDTDEndActionPermissions($p);foreach(['frontline_helm','frontline_plating','frontline_gauntlets','frontline_legs'] as $id)foreach(FaBCRUEquipment($p,$id) as $r)FaBWeakenEquipment($r);}
function FaBDTDBloodDebt(int $p): bool {
 if(FaBMONHero($p,'levia_redeemed'))return true;
 if(FaBMONHero($p,'blasmophet_levia_consumed')){for($i=0,$n=FaBMONBloodDebt($p);$i<$n;++$i)FaBDTDTop($p);return true;}
 if(FaBMONHero($p,'levia')&&FaBMONCount($p,'BANISHED_SIX'))return true;
 if((FaBChoiceRefs($p,'Inventory',['base'=>'blasmophet_levia_consumed'])||FaBChoiceRefs($p,'Inventory',['base'=>'levia_redeemed']))&&intval(GetHealth($p))>13&&intval(GetHealth($p))-FaBMONBloodDebt($p)<=13){FaBRunSourceMacro('EndTurn',$p,'blasmophet_levia_consumed',[]);return true;}
 return false;
}
function FaBDTDClose(): void {
 $entries=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(($o->Role??'')!=='ATTACK')continue;$b=FaBWTRBase($o->CardID);
  if(in_array($b,['deathly_delight','deathly_wail','widespread_annihilation','widespread_destruction','widespread_ruin'],true))$entries[]=[$p,$o->CardID];
  if($b==='hell_hammer'){$w=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($w&&$w['zone']==='Weapons')FaBMoveUID(intval($w['object']->UniqueID),'Banish',$p);}
 }
 foreach(FaBLiveSeats() as $p)foreach(['DAYBREAK','SPIRIT','BECKON'] as $key)FaBDTDClear($p,$key);
 foreach($entries as [$p,$id])FaBRunSourceMacro('CombatChainClosed',$p,$id,[]);
}
function FaBDTDDrawReplacement(int $p): bool {
 if(GetCurrentPhase()!=='MAIN'||!empty(FaBGetState()['dtdStarting'])||!empty(FaBGetState()['uprRefill']))return false;
 foreach(FaBLiveSeats() as $seat)if(FaBMONArena($seat,'chains_of_mephetis')){$o=FaBDTDTop($p);if($o)FaBDTDGrant($p,intval($o->UniqueID));return true;}return false;
}
function FaBDTDDestroyed(int $p,object $o): void {
 if(FaBDTDCount($p,'DIADEM'))return;
 if($o->CardID!=='diadem_of_dreamstate'&&(!FaBCRUEquipment($p,'diadem_of_dreamstate')||FaBHasType($o,'Token')||!str_contains(implode(' ',FaBKeywords($o->CardID)),'Ward')))return;
 FaBDTDAdd($p,'DIADEM');FaBRunSourceMacro('ResolveAbility',$p,'diadem_of_dreamstate',['mzID'=>FaBDTDSource(intval($o->UniqueID))]);
}
function FaBDTDPrevent(int $p,int $n,string $source): int {
 $f=FaBDamageSourceView($source);if($n>0&&$f&&FaBDamageSourceHasType($f['object'],'Shadow'))foreach(FaBLiveSeats() as $seat){$prevent=FaBDTDCount($seat,'BREAK');if($prevent){$n=max(0,$n-$prevent);FaBDTDClear($seat,'BREAK');break;}}
 if($n>0&&!FaBDTDCount($p,'DAMAGE_EVENT')){FaBDTDAdd($p,'DAMAGE_EVENT');if(FaBMONCount($p,'CHARGED')&&FaBCRUEquipment($p,'soulbond_resolve'))--$n;}
 return $n;
}
