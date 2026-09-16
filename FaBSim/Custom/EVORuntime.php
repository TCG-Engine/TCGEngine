<?php
function FaBEVOAbilityRows(): array {
 $r=[];foreach(['teklovossen','teklovossen_esteemed_magnate'] as $id)$r[$id]=[['INSTANT',3,false,false,true,0,'Play next Evo as instant and draw']];
 foreach(['maxx_nitro','maxx_the_hype_nitro'] as $id)$r[$id]=[['ACTION',2,false,false,true,0,'Create Hyper Driver']];
 // Dash's private top card is rendered directly; playing it uses the normal PLAY action.
 foreach(['head','chest','arms','legs'] as $slot)$r['cogwerx_base_'.$slot]=[['INSTANT',1,false,false,true,1,'Use steam counter']];
 foreach(['command_center','engine_room','smoothbore','thruster','data_mine','battery_pack','cogspitter','charging_rods'] as $id)$r['evo_'.$id.'_yellow']=[['INSTANT',0,false,false,true,0,'Destroy a card under this']];
 foreach(['fuel_injector','steam_canister'] as $id)$r[$id.'_blue']=[['INSTANT',0,false,false,false,0,'Put on bottom of deck']];
 $r['medkit_blue']=[['ACTION',0,false,false,false,0,'Bottom this and gain life']];$r['stasis_cell_blue']=[['ACTION',0,false,true,false,0,'Bottom this and disable equipment defense']];
 foreach(['red','yellow','blue'] as $c)$r['dissolving_shield_'.$c]=[['INSTANT',0,false,false,false,1,'Prevent one damage']];
 foreach(['blu_blue','red_red','yel_yellow'] as $c)$r['backup_protocol_'.$c]=[['INSTANT',2,true,false,false,0,'Recover Mechanologist attack']];
 foreach(['prismatic_lens_yellow','quantum_processor_yellow'] as $id)$r[$id]=[['INSTANT',0,false,false,true,0,'Use item']];
 $r['grinding_gears_blue']=[['ACTION',0,false,false,false,0,'Destroy top card of a hero deck']];$r['adaptive_plating']=[['ACTION',0,false,false,false,0,'Move equipment slot']];
 $r['shriek_razors']=[['REACTION',2,true,false,false,0,'Weaken defending attack action']];$r['warband_of_bellona']=[['ACTION',2,true,true,false,0,'Charge on next attack']];return $r;
}
function FaBEVOAbilityCost(int $p,array $spec): int {$id=$spec['cardID']??'';return ($id==='teklo_leveler'&&FaBEvoCount($p)>=2?-2:0)-(FaBHasType($id,'Weapon')?FaBEVOEffect($p,'WEAPON_DISCOUNT'):0);}
function FaBEVOWeaponLegal(int $p,object $o): bool {if(FaBEVOLocked($p,intval($o->UniqueID),'NO_ACTIVATE'))return false;return match($o->CardID){'banksy','bank_breaker'=>FaBEVOEffect($p,'CRANKED')>0,'symbiosis_shot'=>intval(FaBObjectCounters($o)['STEAM']??0)>0,'teklo_leveler'=>FaBEvoCount($p)>0,'teklovossen_the_mechropotent'=>count(FaBChoiceRefs($p,'Soul'))>=2,default=>true};}
function FaBEVOAbilityLegal(int $p,array $f,array $spec): bool {
 $o=$f['object'];$id=$o->CardID;if(FaBEVOLocked($p,intval($o->UniqueID),'NO_ACTIVATE'))return false;
 if((str_starts_with($id,'cogwerx_base_')||in_array($id,['maxx_nitro','maxx_the_hype_nitro'],true))&&!FaBARCEffect($p,'ARC_BOOSTED'))return false;
 if(str_starts_with($id,'evo_')&&str_contains((string)CardFunctional_text_plain($id),'Instant - Destroy a card under this')&&!FaBEVOUnder($o))return false;
 return true;
}
function FaBEVOPrepareAbility(int $p,object $stack): bool {if(str_starts_with($stack->CardID,'evo_')&&str_contains((string)CardFunctional_text_plain($stack->CardID),'Instant - Destroy a card under this'))return FaBRunSourceMacro('PrepareCard',$p,$stack->CardID,['mzID'=>FaBDTDSource(intval($stack->UniqueID))])>0;return false;}
function FaBEVOPaid(int $p,object $o): void {if(in_array($o->CardID,['fuel_injector_blue','medkit_blue','steam_canister_blue','stasis_cell_blue'],true))FaBEVOBottomCost($p,$o);}
function FaBEVOCost(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;if(in_array($b,['annihilator_engine','terminator_tank','war_machine'],true)&&FaBEvoCount($p)>=2)$n-=3;
 if($b==='rev_up'&&FaBMONArena($p,'hyper_driver'))$n--;
 if($b==='quickfire')$n-=count(FaBMONArena($p,'hyper_driver'));
 if(FaBARCCard(intval($o->UniqueID),'evoDashPlay')||FaBEVODashTop($p,$o))++$n;return $n;
}
function FaBEVOCanPlay(int $p,array $f): bool {if($f['object']->CardID==='moonshot_yellow'&&!FaBARCEffect($p,'ARC_BOOSTED'))return false;return FaBEVOBlockLegal($p,$f);}
function FaBEVOPlayed(int $p,object $o,string $from): void {
 $uid=intval($o->UniqueID);if($from==='Deck'&&FaBARCCard($uid,'evoDashPlay'))FaBEVOAdd($p,'DASH_PLAY');
 if(FaBHasType($o,'Evo')){FaBEVOClear($p,'INSTANT_EVO');if(FaBEVOEffect($p,'TEKLO_INSTANT')){FaBEVOClear($p,'TEKLO_INSTANT');DoDrawCard($p,1);}}
 if(FaBWTRIsWeapon($o)){if(FaBEVOEffect($p,'WEAPON_DRAW')){FaBTagUID($uid,'WTR_DRAW_HIT');FaBEVOClear($p,'WEAPON_DRAW');}FaBEVOClear($p,'WEAPON_DISCOUNT');}
 if(FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)){if(FaBEVOEffect($p,'BELLONA')){FaBEVOClear($p,'BELLONA');FaBRunSourceMacro('ResolveAbility',$p,'warband_of_bellona',['mzID'=>FaBDTDSource($uid),'evoEvent'=>'charge']);}}
}
function FaBEVOBoost(int $p,int $uid,int $banished): void {
 $f=FaBFindUID($uid);$b=FaBFindUID($banished);if(!$f)return;
 if($b&&(FaBHasType($b['object'],'Equipment')||FaBHasType($b['object'],'Item')))FaBARCSetCard($uid,'evoBoostEquipment',true);
 if($b&&FaBWTRBase($b['object']->CardID)==='big_bertha')FaBRunSourceMacro('ResolveAbility',$p,$b['object']->CardID,['mzID'=>$b['mzID']]);
 if($b&&FaBWTRBase($b['object']->CardID)==='hyper_driver')foreach(FaBCRUEquipment($p,'hyper_x3') as $r){$o=FaBIdentityFromMZ($r)['object'];FaBEVOAttach($p,intval($o->UniqueID),$b['mzID']);$n=count(array_filter(FaBEVOUnder($o),fn($id)=>FaBWTRBase($id)==='hyper_driver'));if(!FaBEVOEffect($p,'HYPER_DRAW')){FaBEVOAdd($p,'HYPER_DRAW');if($n>=3)DoDrawCard($p,1);}break;}
 if(FaBWTRIsAttackAction($f['object'])){if(FaBEVOEffect($p,'NEXT_BOOST')){FaBTagUID($uid,'WTR_POWER:'.FaBEVOEffect($p,'NEXT_BOOST'));FaBEVOClear($p,'NEXT_BOOST');}
  foreach(FaBMONArena($p,'hadron_collider') as $r){$o=FaBIdentityFromMZ($r)['object'];$n=intval(FaBObjectCounters($o)['STEAM']??0);FaBMONDestroy(intval($o->UniqueID));FaBTagUID($uid,'WTR_POWER:'.$n);}
 }
 foreach(FaBProfessorEquipped($p) as $o)if(in_array($o->CardID,['evo_circuit_breaker_red','evo_atom_breaker_red','evo_face_breaker_red','evo_mach_breaker_red'],true)&&FaBEVOUnder($o)&&!HasNoAbilities($o)&&($o->CardID!=='evo_face_breaker_red'||FaBWTRIsAttackAction($f['object'])))FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID)),'evoAttackUID'=>$uid]);
}
function FaBEVOPower(int $p,object $o): int {
 if(in_array($o->Role??'', ['DEFENSE','DEFENSE_REACTION'],true))return 0;
 $b=FaBWTRBase($o->CardID);$n=0;$evos=FaBEvoCount($p);
 if(in_array($b,['annihilator_engine','terminator_tank','war_machine'],true)&&$evos>=4)$n+=3;
 if($b==='teklo_leveler'&&$evos>=4)++$n;
 if(in_array($b,['big_shot','burn_rubber','smash_and_grab'],true)&&FaBARCEffect($p,'ARC_BOOSTED')>=2)$n+=2;
 if(in_array($b,['dumpster_dive','sprocket_rocket'],true)&&FaBARCCard(intval($o->UniqueID),'evoBoostEquipment'))++$n;
 if($b==='steel_street_hoons'&&FaBEVOEffect($p,'ITEM_DESTROYED'))$n+=2;
 if(in_array($b,['fender_bender','panel_beater'],true))foreach(FaBLiveSeats() as $s)foreach(FaBChoiceRefs($s,'CombatChain') as $r){$d=FaBIdentityFromMZ($r)['object'];if(intval($d->ChainLink)===intval(FaBGetState()['chainLink'])&&in_array($d->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBHasType($d,'Equipment'))++$n;}
 if(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Mechanologist'))$n+=count(FaBMONArena($p,'penetration_script'));return $n;
}
function FaBEVOGoAgain(int $p,object $o): bool {return ($o->CardID==='teklo_leveler'&&FaBEvoCount($p)>=3)||(FaBWTRBase($o->CardID)==='soup_up'&&FaBEVOEffect($p,'ITEM_DESTROYED'))||(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Mechanologist')&&(FaBMONHero($p,'teklovossen_the_mechropotent')||FaBMONArena($p,'mhz_script')));}
function FaBEVOOverpower(int $p,object $o,array $s): bool {
 $b=FaBWTRBase($o->CardID);return (in_array($b,['annihilator_engine','terminator_tank','war_machine'],true)&&FaBEvoCount($p)>=3)||($b==='bull_bar'&&FaBMONArena($p,'hyper_driver'))||($b==='moonshot'&&FaBAttackPower($s)>=10)||($b==='torque_tuned'&&FaBEVOEffect($p,'ITEM_DESTROYED'))||(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Mechanologist')&&FaBMONArena($p,'overload_script'));
}
function FaBEVODefense(int $p,object $o): int {
 $n=0;$b=FaBWTRBase($o->CardID);if($b==='steel_street_enforcement')$n+=FaBEvoCount($p);
 if(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Mechanologist'))$n+=count(FaBMONArena($p,'security_script'));
 if(FaBHasType($o,'Evo')&&in_array($o,FaBProfessorEquipped($p),true))$n+=FaBEVOEffect($p,'FABRICATE');
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&FaBHasType($o,'Action')&&FaBWTRIsAttackAction($a['object'])&&FaBHasType($a['object'],'Mechanologist')){$n-=count(FaBMONArena(intval($s['attacker']),'polarity_reversal_script'));if(FaBWTRBase($a['object']->CardID)==='ratchet_up'&&FaBEVOEffect(intval($s['attacker']),'ITEM_DESTROYED'))--$n;}
 return $n;
}
function FaBEVOBlockLegal(int $p,array $f): bool {
 $s=FaBGetState();if($p!==intval($s['defender'])||!in_array($s['window'],['DEFEND_DECLARE','REACTION'],true))return true;$a=FaBFindUID(intval($s['attackUID']));if(!$a)return true;$o=$f['object'];$b=FaBWTRBase($a['object']->CardID);
 if(FaBEVOLocked($p,intval($o->UniqueID),'NO_DEFEND'))return false;
 if(FaBHasType($o,'Equipment')&&(in_array($b,['lay_waste','out_pace'],true)||($b==='burn_rubber'&&FaBARCEffect(intval($s['attacker']),'ARC_BOOSTED')>=2)))return false;
 if($b==='heavy_artillery'&&FaBWTRIsAttackAction($o)&&intval(CardCost($o->CardID))<FaBEvoCount(intval($s['attacker'])))return false;return true;
}
function FaBEVODestroyed(int $p,object $o,string $zone): void {
 if($zone==='Arena'&&FaBHasType($o,'Item'))FaBEVOAdd($p,'ITEM_DESTROYED');
 if(FaBHasType($o,'Illusionist')&&in_array($zone,['Arena','Equipment','CombatChain'],true))foreach(FaBMONArena($p,'phantom_tidemaw') as $r){$f=FaBIdentityFromMZ($r);FaBEVOCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);}
}
function FaBEVOAfterMove(int $p,object $o,string $from,string $to): void {if($from==='Arena'&&$to!=='Arena'&&$o->CardID==='stasis_cell_blue')FaBRunSourceMacro('ResolveCard',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID)),'evoEvent'=>'stasis']);}
function FaBEVOStart(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];if(HasNoAbilities($o))continue;if($o->CardID==='contest_the_mindfield_blue'){FaBMONDestroy(intval($o->UniqueID));continue;}
 if(FaBHasType($o,'Item')&&FaBHasKeyword($o,'Crank')&&str_contains((string)CardFunctional_text_plain($o->CardID),'At the start of your turn'))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);}
 foreach(FaBChoiceRefs($p,'Graveyard',['base'=>'shriek_razors']) as $r)FaBRunSourceMacro('StartTurn',$p,'shriek_razors',['mzID'=>$r]);
}
function FaBEVOHit(int $p,object $o,int $n): void {
 if($n<=0||!FaBWTRIsAttackAction($o)||!FaBHasType($o,'Mechanologist'))return;
 if(FaBMONArena($p,'autosave_script'))FaBTagUID(intval($o->UniqueID),'ARC_BOTTOM_HIT');
 if(FaBFaiHeroHit())foreach(FaBChoiceRefs($p,'Arena') as $r){$f=FaBIdentityFromMZ($r);if($f&&in_array(FaBWTRBase($f['object']->CardID),['boom_grenade','tick_tock_clock'],true))FaBRunSourceMacro('Hit',$p,$f['object']->CardID,['mzID'=>$r,'evoVictim'=>intval(FaBGetState()['defender'])]);}
}
function FaBEVOMustEquip(int $p): bool {
 $s=FaBGetState();$need=0;$a=FaBFindUID(intval($s['attackUID']));$eligible=[];
 if($a&&$a['object']->CardID==='meganetic_protocol_blue'){$need=FaBEvoCount(intval($s['attacker']));foreach(FaBProfessorEquipped($p) as $o)if(intval(FaBObjectCounters($o)['DEFENSE']??0)>0)$eligible[]=intval($o->UniqueID);}
 foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='EVO_MUST_DEFEND'&&intval($e['attacker']??0)===intval($s['attacker'])){$uid=intval($e['uid']);$f=FaBFindUID($uid);if($f&&$f['zone']==='Equipment'&&FaBCanBlock($p,$f['mzID']))return true;}
 $defended=0;foreach($eligible as $uid){$f=FaBFindUID($uid);if($f&&$f['zone']==='CombatChain'&&intval($f['object']->ChainLink)===intval($s['chainLink']))++$defended;}
 if($defended>=$need)return false;foreach($eligible as $uid){$f=FaBFindUID($uid);if($f&&$f['zone']==='Equipment'&&FaBCanBlock($p,$f['mzID']))return true;}return false;
}
function FaBEVOExpire(int $p): void {foreach(FaBLiveSeats() as $seat)FaBWTRSetEffects($seat,array_values(array_filter(FaBWTREffects($seat),fn($e)=>!(($e['type']??'')==='EVO_NO_ACTIVATE'&&intval($e['evoExpiresSeat']??0)===$p&&intval($e['evoAppliedTurn']??0)<intval(GetTurnNumber())))));}
