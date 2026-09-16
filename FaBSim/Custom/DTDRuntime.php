<?php
function FaBDTDAfterMove(int $p,object $o,string $from,string $to): void {
 if($from==='CombatChain'&&$to!=='CombatChain'&&!empty(FaBObjectCounters($o)['DTD_ORIGINAL_ID']))$o->CardID=FaBObjectCounters($o)['DTD_ORIGINAL_ID'];
 if($from==='Arena'&&$to!=='Arena'){$front=array_search($o->CardID,FaBDTDAngels(),true);if($front!==false)$o->CardID=$front;}
 if($to==='Arena'&&$o->CardID==='radiant_forcefield_yellow'&&FaBMONSoul($p)==='')FaBMONDestroy(intval($o->UniqueID));
 if($to==='Soul'){
  if(FaBDTDHerald($o)){FaBDTDAdd($p,'HERALD_SOUL');if(GetCurrentPhase()==='MAIN'&&empty(FaBGetState()['dtdStarting'])&&(FaBMONHero($p,'prism_advent_of_thrones')||FaBMONHero($p,'prism_awakener_of_sol')))FaBRunSourceMacro('ResolveAbility',$p,'prism_advent_of_thrones',['dtdSearch'=>true,'mzID'=>FaBDTDSource(intval($o->UniqueID))]);}
 }
 if($to==='Banish'){
  if($from==='Hand')FaBSetObjectCounter($o,'DTD_HAND_BANISH',intval(GetTurnNumber()));
  
 }
 if($from==='Soul'&&FaBMONSoul($p)==='')foreach(FaBMONArena($p,'radiant_forcefield') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
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
function FaBDTDMirage(): void {
 $s=FaBGetState();if(($s['window']??'')==='DEFEND_DECLARE')return;$a=FaBFindUID(intval($s['attackUID']));if(!$a||FaBHasType($a['object'],'Illusionist')||FaBAttackPower($s)<6)return;
 foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBHasKeyword($o,'Mirage'))FaBMONDestroy(intval($o->UniqueID));}
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
 $s=FaBGetState();foreach($s['dtdPermissions']??[] as $uid=>$e)if(!$e['next']&&intval($e['created'])!==intval(GetTurnNumber()))unset($s['dtdPermissions'][$uid]);FaBSetState($s);
 foreach(FaBMONArena($p,'chains_of_mephetis') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($o)['DTD_DOOM']??0)>0)FaBSetObjectCounter($o,'DTD_DOOM',0);else FaBMONDestroy(intval($o->UniqueID));}
 if(FaBMONHero($p,'vynnset'))FaBRunSourceMacro('StartTurn',$p,'vynnset',['mzID'=>FaBDTDSource(FaBUPRHeroUID($p))]);
}
function FaBDTDEnd(int $p): void {foreach(['frontline_helm','frontline_plating','frontline_gauntlets','frontline_legs'] as $id)foreach(FaBCRUEquipment($p,$id) as $r)FaBWeakenEquipment($r);}
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
 $f=FaBIdentityFromMZ($source);if($n>0&&$f&&FaBHasType($f['object'],'Shadow'))foreach(FaBLiveSeats() as $seat){$prevent=FaBDTDCount($seat,'BREAK');if($prevent){$n=max(0,$n-$prevent);FaBDTDClear($seat,'BREAK');break;}}
 if($n>0&&!FaBDTDCount($p,'DAMAGE_EVENT')){FaBDTDAdd($p,'DAMAGE_EVENT');if(FaBMONCount($p,'CHARGED')&&FaBCRUEquipment($p,'soulbond_resolve'))--$n;}
 return $n;
}
