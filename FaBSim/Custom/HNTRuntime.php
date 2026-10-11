<?php
function FaBHNTAbilityRows(): array {
 $r=[];foreach(['cindra','cindra_dracai_of_retribution'] as $id)$r[$id]=[['INSTANT',3,false,false,true,0,'Equip Draconic daggers']];
 $r['fealty']=[['INSTANT',0,true,false,false,0,'Make next card Draconic']];
 foreach(['arakni_black_widow','arakni_funnel_web','arakni_redback','arakni_tarantula','arakni_orb_weaver'] as $id)$r[$id]=[[$id==='arakni_orb_weaver'?'INSTANT':'REACTION',0,false,false,true,0,'Discard Assassin card']];
 foreach(['hand_of_vengeance','path_of_vengeance','vow_of_vengeance'] as $id)$r[$id]=[['REACTION',0,true,false,false,0,'Invoke vengeance']];
 foreach(['heart_of_vengeance','enchanted_quiver','misfire_dampener','tremorshield_sabatons'] as $id)$r[$id]=[['INSTANT',0,true,false,false,0,'Use equipment']];
 $r['dragonscaler_flight_path']=[['INSTANT',3,true,false,false,0,'Give Draconic attack go again']];
 $r['coat_of_allegiance']=[['ACTION',0,true,true,false,0,'Gain resource and pledge allegiance']];
 $r['imperial_seal_of_command_red']=[['ACTION',0,true,true,false,0,'Forbid defense reactions']];
 $r['bunker_beard']=[['DEFENSE_REACTION',0,true,false,false,0,'Defend from arsenal']];
 $r['quickdodge_flexors']=[['DEFENSE_REACTION',1,false,false,false,0,'Add as defending equipment']];
 foreach(['reapers_call','tip_off','under_the_trap_door','shelter_from_the_storm','war_cry_of_bellona','war_cry_of_themis'] as $b)foreach(['red','yellow','blue'] as $c)$r[$b.'_'.$c]=[['INSTANT',0,false,false,false,0,'Discard to use instant ability']];return $r;
}
function FaBHNTDiscardAbility(string $id): bool {return in_array(FaBWTRBase($id),['reapers_call','tip_off','under_the_trap_door','shelter_from_the_storm','war_cry_of_bellona','war_cry_of_themis'],true);}
function FaBHNTAbilityLegal(int $p,array $f): bool {
 $id=$f['object']->CardID;if(FaBHNTDiscardAbility($id))return $f['zone']==='Hand';
 if(in_array($id,FaBHNTAgents(),true)){if(FaBROSRefs($p,'Hand','Assassin')==='')return false;if($id!=='arakni_orb_weaver')return FaBHNTAffectsAttack($p,$id==='arakni_tarantula'?'DAGGER':'ASSASSIN')!=='';}
 if(in_array($id,['hand_of_vengeance','path_of_vengeance'],true))return FaBFaiHeroHit()&&FaBHNTArakni(intval(FaBGetState()['defender']));
 if($id==='dragonscaler_flight_path')return FaBHNTAffectsAttack($p,'DRACONIC')!=='';
 if($id==='vow_of_vengeance')return FaBHNTArakniTargets($p)!=='';
 if($id==='quickdodge_flexors')return $f['zone']==='Equipment';return true;
}
function FaBHNTPaid(int $p,object $o): void {if(FaBHasType($o,'Draconic'))FaBHNTClear($p,'IGNITE_CHAIN');if(FaBHNTDiscardAbility($o->CardID))FaBDiscardChoice($p,FaBFindUID(intval($o->UniqueID))['mzID']);}
function FaBHNTAbilityCost(int $p,array $spec): int {
 $id=$spec['cardID']??'';$n=0;if(in_array($id,['cindra','cindra_dracai_of_retribution','dragonscaler_flight_path'],true))$n-=FaBFaiChainCount($p);
 if(FaBHasType($id,'Dagger')&&FaBHasType($id,'Weapon')){
  if(FaBMONHero($p,'fang')&&count(FaBMONArena($p,'fealty'))>=3)--$n;
  if($id==='graphene_chelicera'&&FaBMONHero($p,'arakni_orb_weaver'))--$n;
  $u=intval($spec['sourceUID']??0);$n-=FaBHNTWrathAmount($u);if($u&&FaBARCCard($u,'hntPerforateTurn',-1)===intval(GetTurnNumber()))$n-=intval(FaBARCCard($u,'hntPerforate'));
 }
 if(FaBHasType($id,'Draconic'))$n-=FaBHNTCount($p,'IGNITE_CHAIN');if(FaBHasType($id,'Weapon')&&FaBHNTVengeanceTarget($p,$spec['attackTarget']??null))$n-=FaBHNTCount($p,'VENGEANCE');return $n;
}
function FaBHNTCardDraconic(int $p,object $o): bool {return in_array('Draconic',(array)CardTypes($o->CardID),true)||in_array('FAI_DRACONIC',(array)($o->TurnEffects??[]),true)||FaBHNTCount($p,'FEALTY_NEXT')>0;}
function FaBHNTCost(int $p,object $o): int {$n=0;if(FaBWTRIsAttackAction($o)&&FaBHNTVengeanceTarget($p,$o->Params['attackTarget']??null))$n-=FaBHNTCount($p,'VENGEANCE');$text=(string)CardFunctional_text_plain($o->CardID);if(str_contains($text,'This costs {r} less to play for each Draconic chain link'))$n-=FaBFaiChainCount($p);if(FaBWTRBase($o->CardID)==='stains_of_the_redback'&&FaBHNTMarkedTarget())--$n;if(FaBHNTCardDraconic($p,$o))$n-=FaBHNTCount($p,'IGNITE_CHAIN')+(FaBHNTCount($p,'BLOOD_DISCOUNT')>0?1:0);return $n;}
function FaBHNTCanPlay(int $p,object $o): bool {
 $b=FaBWTRBase($o->CardID);if(in_array($b,['exposed','outed','lay_low'],true)&&FaBHNTMarked($p))return false;
 if(FaBHNTCount($p,'ONLY_DRACONIC')&&!FaBHNTCardDraconic($p,$o))return false;
 if(in_array($b,['brothers_of_flame','sisters_of_fire'],true)&&FaBFaiChainCount($p)<2)return false;
 if($b==='hunts_end'&&count(FaBMONArena($p,'fealty'))<3)return false;
 if($b==='oath_of_loyalty'&&FaBARCEffect($p,'ARC_ACTIONS')>0)return false;
 if(FaBHasType($o,'Defense Reaction'))foreach(FaBLiveSeats() as $v)if(FaBHNTCount($v,'NO_DR'))return false;
 $f=FaBFindUID(intval($o->UniqueID));if($f&&$f['zone']==='Hand'&&FaBHNTNamed($o->CardID))return false;
 if(FaBHasType($o,'Attack Reaction')){
  $text=(string)CardFunctional_text_plain($o->CardID);
  if(str_contains($text,'Target dagger attack')&&!str_contains($text,'Choose')&&!str_contains($text,'choose')&&FaBHNTAffectsAttack($p,'DAGGER')==='')return false;
  if($b==='two_sides_to_the_blade'&&FaBHNTSidesOptions($p)===''||$b==='tarantula_toxin'&&FaBHNTToxinOptions($p)===''||$b==='take_up_the_mantle'&&FaBHNTAffectsAttack($p,'STEALTHAA')===''||$b==='stains_of_the_redback'&&FaBHNTAffectsAttack($p,'STEALTH')===''||$b==='jagged_edge'&&FaBHNTAffectsAttack($p,'WEAPON')===''||$b==='nip_at_the_heels'&&FaBHNTAffectsAttack($p,'SMALL')==='')return false;
 }return true;
}
function FaBHNTNamed(string $id): bool {foreach(FaBLiveSeats() as $p)foreach(FaBMONArena($p,'null_time_zone') as $r){$o=FaBIdentityFromMZ($r)['object'];if(!HasNoAbilities($o)&&strcasecmp((string)FaBARCCard(intval($o->UniqueID),'hntName',''),CardName($id))===0)return true;}return false;}
function FaBHNTPlayed(int $p,object $o,string $from): void {
 $uid=intval($o->UniqueID);$weapon=$from==='Weapons';$attack=$weapon||FaBWTRIsAttackAction($o);$b=FaBWTRBase($o->CardID);
 if(!$weapon){if(FaBHNTCount($p,'FEALTY_NEXT')){FaBWTRTag($o,'FAI_DRACONIC');FaBHNTClear($p,'FEALTY_NEXT');}if(FaBHasType($o,'Draconic')){FaBHNTAdd($p,'DRACONIC');FaBHNTAdd($p,'DRACONIC_LINK');if(FaBHNTCount($p,'BLOOD_DISCOUNT'))FaBHNTAdd($p,'BLOOD_DISCOUNT',-1);}if($b==='oath_of_loyalty')FaBHNTAdd($p,'ONLY_DRACONIC');}
 if(FaBHasType($o,'Draconic'))FaBHNTClear($p,'IGNITE_CHAIN');if(!$attack)return;if(FaBHNTVengeanceTarget($p,$o->Params['attackTarget']??null))FaBHNTClear($p,'VENGEANCE');
 if($weapon&&$o->CardID==='kunai_of_retribution'){$f=FaBFindUID(FaBHNTWeaponUID($o));if($f)FaBSetObjectCounter($f['object'],'HNT_KUNAI',1);}
 if(FaBHasKeyword($o,'Stealth')){FaBHNTAdd($p,'STEALTH');if(array_sum(array_map(fn($v)=>FaBHNTCount($v,'STEALTH'),FaBLiveSeats()))===1)foreach(FaBLiveSeats() as $v)if(FaBMONHero($v,'arakni_5lp3d_7hru_7h3_cr4x')){FaBWTRTag($o,'GO_AGAIN');break;}}
 $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='HNT_NEXT'&&FaBHNTMatch($o,$e['kind'])){FaBWTRTag($o,'WTR_POWER:'.$e['amount']);foreach($e['tags'] as $tag)FaBWTRTag($o,$tag);}else $left[]=$e;}FaBWTRSetEffects($p,$left);
}
function FaBHNTPower(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;if(FaBHNTMarkedTarget()){$n+=FaBHNTCount($p,'ENGAGEMENT');if(in_array($b,['mark_of_the_huntsman','plunge_the_prospect','outed'],true))++$n;if($b==='hunt_to_the_ends_of_rathe')$n+=2;if(FaBHasKeyword($o,'Stealth')&&(FaBMONHero($p,'arakni_marionette')||FaBMONHero($p,'arakni_web_of_deceit')))++$n;}
 if(FaBHasType($o,'Draconic'))foreach(FaBLiveSeats() as $v)$n+=FaBHNTCount($v,'COALS');
 if(FaBHasType($o,'Dagger'))$n+=FaBHNTCount($p,'DAGGER_POWER')+FaBHNTWrathAmount(FaBHNTWeaponUID($o));
 if(FaBWTRIsWeapon($o))$n+=count(FaBMONArena($p,'sharpened_senses'));
 if($b==='grow_claws'&&FaBHNTPreviousDraconic())++$n;if($b==='cut_through'&&FaBHNTCount($p,'DAGGER_HIT_CHAIN'))++$n;
 if($b==='obsidian_fire_vein'&&FaBHNTCount($p,'DRACONIC_LINK'))++$n;return $n;
}
function FaBHNTGoAgain(int $p,object $o): bool {
 $b=FaBWTRBase($o->CardID);if(in_array($b,['kunai_of_retribution','hunters_klaive','mark_of_the_huntsman'],true))return true;if(in_array($b,['burning_blade_dance','hot_on_their_heels','mark_with_magma','demonstrate_devotion','display_loyalty'],true)&&FaBFaiChainCount($p)>=2)return true;
 if($b==='grow_wings'&&FaBHNTPreviousDraconic()||$b==='march_of_loyalty'&&FaBHNTCount($p,'FEALTY')||$b==='cut_through'&&FaBHNTCount($p,'DAGGER_HIT_CHAIN')||$b==='obsidian_fire_vein'&&FaBHNTCount($p,'DRACONIC_LINK'))return true;
 if(FaBHasType($o,'Dagger')&&FaBHNTCount($p,'DAGGER_GO'))return true;
 // The threshold is evaluated dynamically; do not cache a power bonus on declaration.
 if(FaBWTRIsWeapon($o)&&FaBMONArena($p,'sharpened_senses')&&FaBAttackPower(FaBGetState())>2*intval(CardPower($o->CardID)))return true;return false;
}
function FaBHNTDefense(int $p,object $o): int {$b=FaBWTRBase($o->CardID);$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(str_starts_with($b,'blade_beckoner_')&&$a&&FaBWTRIsWeapon($a['object']))return 1;if(str_starts_with($b,'red_alert_'))foreach(FaBLiveSeats() as $v)if(!empty($s['arakniAttackReactions'][FaBArakniReactionKey($v)]))return 1;return 0;}
function FaBHNTDeclared(int $p,object $o): void {
 foreach(FaBLiveSeats() as $v)FaBHNTClear($v,'DRACONIC_LINK');
 if(($o->FromZone??'')!=='Weapons'&&FaBHasType($o,'Draconic'))FaBHNTAdd($p,'DRACONIC_LINK');
 $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);FaBHNTAdd($p,'ATTACKS');FaBHNTAdd($p,FaBWTRIsWeapon($o)?'WEAPON_ATTACK':'AA_ATTACK');
 if(FaBFaiHeroHit())FaBHNTAdd($p,'ATTACKED_'.intval(FaBGetState()['defender']));
 if(FaBHNTMarkedTarget()){
  if(in_array($b,['graphene_chelicera','scuttle_the_canal'],true)||FaBHNTCount($p,'KNIFE'))FaBWTRTag($o,'GO_AGAIN');
  if(FaBHNTCount($p,'BOUNTY')){FaBWTRTag($o,'WTR_POWER:'.FaBHNTCount($p,'BOUNTY'));FaBHNTClear($p,'BOUNTY');}
 }
 if(FaBHNTCount($p,'ATTACKS')===4)foreach(FaBMONArena($p,'prowess_of_agility') as $r){$a=FaBIdentityFromMZ($r)['object'];FaBROSQueue($p,$a->CardID,intval($a->UniqueID));}
}
function FaBHNTChainResolved(object $o): void {$s=FaBGetState();$s['hntPreviousDraconic']=FaBHasType($o,'Draconic');FaBSetState($s);}
function FaBHNTClose(): void {foreach(FaBLiveSeats() as $p){foreach(FaBChoiceRefs($p,'Weapons') as $r){$o=FaBIdentityFromMZ($r)['object'];FaBSetObjectCounter($o,'HNT_WRATH_CHAIN',0);if(!empty(FaBObjectCounters($o)['HNT_KUNAI']))FaBMONDestroy(intval($o->UniqueID));}FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!(str_starts_with($e['type']??'','HNT_')&&str_ends_with($e['type']??'','_CHAIN'))&&!(($e['type']??'')==='HNT_NEXT'&&!empty($e['chain'])))));} $s=FaBGetState();$s['hntPreviousDraconic']=false;FaBSetState($s);}
function FaBHNTStart(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);if($b==='null_time_zone'){if(intval(FaBObjectCounters($o)['STEAM']??0)>0)FaBARCSteam($o,-1);else FaBMONDestroy(intval($o->UniqueID));}
 if(in_array($b,['agility_stance','power_stance','flurry_stance','blessing_of_vynserakai'],true)){FaBMONDestroy(intval($o->UniqueID));if($b==='agility_stance')FaBHNTAdd($p,'DAGGER_GO');if($b==='power_stance')FaBHNTAdd($p,'DAGGER_POWER');if($b==='flurry_stance')foreach(FaBUPRUIDs(FaBHNTEqpDaggers($p)) as $u)FaBHNTExtra($u);if($b==='blessing_of_vynserakai')FaBHNTNext($p,'ANY',4-intval(CardPitch($o->CardID)),'FAI_DRACONIC');}}
 if(count(FaBChoiceRefs($p,'Graveyard',['base'=>'loyalty_beyond_the_grave']))>=2)FaBROSQueue($p,'loyalty_beyond_the_grave_red');
 foreach(FaBChoiceRefs($p,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'hntTrapUntilStart')){$o->PlayableFromBanish=0;FaBARCSetCard(intval($o->UniqueID),'hntTrapUntilStart',false);}}
}
function FaBHNTEnd(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);if($b==='sharpened_senses'||$b==='prowess_of_agility'&&FaBHNTCount($p,'ATTACKS')<3||$b==='fealty'&&!FaBHNTCount($p,'FEALTY')&&!FaBHNTCount($p,'DRACONIC'))FaBMONDestroy(intval($o->UniqueID));}
 foreach(FaBLiveSeats() as $v)foreach(FaBCRUEquipment($v,'quickdodge_flexors') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'hntDefendedTurn',-1)===intval(GetTurnNumber()))FaBMONDestroy(intval($o->UniqueID));}
 $f=FaBFindUID(FaBUPRHeroUID($p));if(!$f||HasNoAbilities($f['object']))return;$id=$f['object']->CardID;
 if(in_array($id,FaBHNTAgents(),true))FaBHNTReturnBrood($p);
 elseif(in_array($id,['arakni_marionette','arakni_web_of_deceit'],true))foreach(FaBOpponents($p) as $v)if(FaBHNTMarked($v)){FaBHNTBecome($p);break;}
}
function FaBHNTBlockLegal(int $p,array $f): bool {$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));return FaBWTRBase($f['object']->CardID)!=='put_in_context'||($a&&intval(CardPower($a['object']->CardID))<=3);}
function FaBHNTWeaponLegal(int $p): bool {foreach(FaBOpponents($p) as $v)if(FaBHNTCount($v,'KABUTO'))return false;return true;}
