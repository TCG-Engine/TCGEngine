<?php
// Dusk till Dawn: seat-aware rules and persistent UID references.
function FaBDTDCount(int $p,string $key): int {return FaBARCEffect($p,'DTD_'.$key);}
function FaBDTDAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'DTD_'.$key,$n,$data);}
function FaBDTDClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='DTD_'.$key)));}
function FaBDTDSource(int $uid): string {return FaBFindUID($uid)['mzID']??'';}
function FaBDTDLostSeats(): array {return array_values(array_filter(FaBLiveSeats(),fn($p)=>FaBDTDCount($p,'LOST')>0));}
function FaBDTDHerald(object $o): bool {foreach(FaBOUTNames($o) as $name)if(str_contains($name,'Herald'))return true;return false;}
function FaBDTDAngels(): array {return ['figment_of_erudition_yellow'=>'suraya_archangel_of_erudition','figment_of_judgment_yellow'=>'themis_archangel_of_judgment','figment_of_protection_yellow'=>'aegis_archangel_of_protection','figment_of_ravages_yellow'=>'sekem_archangel_of_ravages','figment_of_rebirth_yellow'=>'avalon_archangel_of_rebirth','figment_of_tenacity_yellow'=>'metis_archangel_of_tenacity','figment_of_triumph_yellow'=>'victoria_archangel_of_triumph','figment_of_war_yellow'=>'bellona_archangel_of_war'];}
function FaBDTDAwaken(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Arena')return;$id=FaBDTDAngels()[$f['object']->CardID]??null;if($id)$f['object']->CardID=$id;}
function FaBDTDRefs(int $p,string $zone,string $kind='',bool $all=false): string {
 $out=[];foreach($all?FaBLiveSeats():[$p] as $seat)foreach(FaBChoiceRefs($seat,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];
  if($zone==='Banish'&&!empty($o->FaceDown))continue;
  $ok=match($kind){'figment'=>FaBHasType($o,'Figment'),'yellow'=>intval(CardPitch($o->CardID))===2,'yellowAction'=>intval(CardPitch($o->CardID))===2&&FaBHasType($o,'Action'),'aa'=>FaBWTRIsAttackAction($o),'action'=>FaBHasType($o,'Action'),'redAura'=>FaBHasType($o,'Aura')&&intval(CardPitch($o->CardID))===1,'item2'=>FaBHasType($o,'Item')&&intval(CardCost($o->CardID))<=2,default=>true};if($ok)$out[]=$r;
 }return implode('&',$out);
}
function FaBDTDAttacks(int $p,string $kind): string {
 $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f)return '';$o=$f['object'];
 $ok=match($kind){'herald'=>FaBWTRIsAttackAction($o)&&FaBDTDHerald($o),'light'=>FaBHasType($o,'Light'),'lightWarrior'=>FaBHasType($o,'Light')&&FaBHasType($o,'Warrior'),'angelHerald'=>FaBHasType($o,'Angel')||FaBDTDHerald($o),'dawnblade'=>str_starts_with($o->CardID,'dawnblade'),default=>true};return $ok?$f['mzID']:'';
}
function FaBDTDFaceDown(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Banish'){$f['object']->FaceDown=1;$f['object']->PlayableFromBanish=0;}}
function FaBDTDTokenHeroes(string $refs,string $id): void {foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Hero'&&FaBSeatIsLive($f['player']))FaBWTRCreateArena($f['player'],$id);}}
function FaBDTDParty(int $p): void {
 $names=['boltyn'=>'courage','bravo'=>'seismic_surge','briar'=>'embodiment_of_earth','dorinthea'=>'courage','lexi'=>'embodiment_of_lightning','oldhim'=>'spellbane_aegis','prism'=>'spectral_shield','shiyana'=>'eloquence'];
 foreach([$p] as $seat){$h=GetHero($seat)[0]??null;if(!$h)continue;foreach($names as $name=>$token)if(str_contains(strtolower(CardName($h->CardID)),$name))FaBWTRCreateArena($seat,$token);}
}
function FaBDTDHandBanish(int $p,int $uid): void {
 $r=FaBRandomHandUID($p);if(!$r)return;$f=FaBFindUID($r);$six=FaBMONBasePower($p,$f['object'])>=6;FaBMoveUID($r,'Banish',$p);FaBARCSetCard($uid,'dtdSix',$six);FaBARCSetCard($uid,'dtdBanished',$r);
}
function FaBDTDFrenzy(int $p,int $uid): void {$n=0;$six=0;foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasKeyword($o,'Blood Debt'))++$n;if(FaBMONBasePower($p,$o)>=6)++$six;FaBMoveUID(intval($o->UniqueID),'Banish',$p);}FaBARCSetCard($uid,'dtdDraw',$n);FaBARCSetCard($uid,'dtdPower',$six);}
function FaBDTDGrant(int $p,int $uid,bool $next=false): void {$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Banish')return;$s=FaBGetState();$s['dtdPermissions'][$uid]=['player'=>$p,'created'=>intval(GetTurnNumber()),'next'=>$next];FaBSetState($s);}
function FaBDTDPermission(int $p,object $o): bool {$e=FaBGetState()['dtdPermissions'][intval($o->UniqueID)]??null;if(!$e||intval($e['player'])!==$p)return false;return !$e['next']?intval($e['created'])===intval(GetTurnNumber()):$p===intval(GetTurnPlayer())&&intval(GetTurnNumber())>intval($e['created']);}
function FaBDTDRuneGate(int $p,object $o): bool {$f=FaBFindUID(intval($o->UniqueID));$from=$f['zone']??'';if($from==='Stack')$from=$o->SourceZone??'';return $from==='Banish'&&empty($o->FaceDown)&&FaBHasKeyword($o,'Rune Gate')&&FaBARCRunechants($p)>=intval(CardCost($o->CardID));}
function FaBDTDBanishPlayable(int $p,object $o,bool $heroPermission=true): bool {
 if(!empty($o->FaceDown))return false;$b=FaBWTRBase($o->CardID);
 if(FaBDTDPermission($p,$o)||FaBDTDRuneGate($p,$o))return true;
 if($heroPermission&&FaBMONHero($p,'blasmophet_levia_consumed')&&FaBHasKeyword($o,'Blood Debt')&&!FaBDTDCount($p,'BLASMOPHET_PLAY'))return true;
 if($b==='slithering_shadowpede')return intval(FaBObjectCounters($o)['DTD_HAND_BANISH']??-1)===intval(GetTurnNumber());
 if($b==='hungering_demigon'){foreach(FaBOpponents($p) as $seat)if(FaBMONSoul($seat)!=='')return true;return false;}
 return in_array($b,['chains_of_mephetis','dimenxxional_vortex','funeral_moon','grim_feast','putrid_stirrings','requiem_for_the_damned','vile_inquisition'],true);
}
function FaBDTDCost(int $p,object $o): int {
 if(FaBWTRBase($o->CardID)==='numbskull')return 0;
 $b=FaBWTRBase($o->CardID);$n=0;$f=FaBFindUID(intval($o->UniqueID));$from=($f['zone']??'')==='Stack'?($o->SourceZone??''):($f['zone']??'');
 if(FaBDTDRuneGate($p,$o))$n-=intval(CardCost($o->CardID));
 if($from==='Banish'&&in_array($b,['dimenxxional_vortex','grim_feast','vile_inquisition'],true))$n-=2;
 if($b==='runic_reckoning')$n-=FaBARCRunechants($p);
 if(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Runeblade')&&FaBDTDCount($p,'BEQUEST'))$n-=FaBARCRunechants($p);
 return $n;
}
function FaBDTDAsInstant(int $p,object $o): bool {$b=FaBWTRBase($o->CardID);return (in_array($b,['blessing_of_salvation','cleansing_light'],true)&&FaBMONCount($p,'SOUL_ADDED')>0)||(in_array($b,['funeral_moon','requiem_for_the_damned'],true)&&count(FaBDTDLostSeats())>0);}
function FaBDTDRestrictions(int $p,object $o,bool $action,bool $attack): bool {
 foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';if($t==='DTD_CENSOR'&&strcasecmp(CardName($o->CardID),$e['name']??'')===0)return false;
  if($p!==intval(GetTurnPlayer()))continue;
  if($t==='DTD_WAR'&&$action&&!$attack)return false;if($t==='DTD_PEACE'&&$action&&$attack)return false;
  if($t==='DTD_STAR'&&$attack&&intval(CardPower($o->CardID))<=intval($e['amount']))return false;
 }return true;
}
function FaBDTDCanPlay(int $p,array $f): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);
 if(!FaBDTDRestrictions($p,$o,FaBHasType($o,'Action'),FaBWTRIsAttackAction($o)))return false;
 if($f['zone']==='Banish'&&!empty($o->FaceDown))return false;
 if($b==='oblivion'&&FaBARCRunechants($p)!==6)return false;
 if(in_array($b,['ram_raider','shaden_scream','shaden_swing','tribute_to_demolition','tribute_to_the_legions_of_doom','expendable_limbs'],true)){
  $values=[];foreach(FaBChoiceRefs($p,'Hand') as $r){$c=FaBIdentityFromMZ($r)['object'];if(intval($c->UniqueID)!==intval($o->UniqueID))$values[]=FaBMONPitchValue($p,$c->CardID);}
  if(!$values||FaBAvailablePitch($p,intval($o->UniqueID))-min($values)<FaBCardCost($o,$p))return false;
 }
 $kind=['angelic_descent'=>'herald','angelic_wrath'=>'herald','lumina_lance'=>'light','resounding_courage'=>'lightWarrior','chorus_of_ironsong'=>'dawnblade'][$b]??null;
 if($kind&&FaBDTDAttacks($p,$kind)==='')return false;
 if($b==='celestial_resolve'&&FaBDTDHeraldCards()==='')return false;
 if($b==='celestial_reprimand'&&(FaBDTDAttacks($p,'herald')===''||FaBDefendingChoices(intval(FaBGetState()['defender']),true)===''))return false;
 if($b==='beseech_the_demigon'&&FaBDTDRefs($p,'Banish','aa')==='')return false;
 if($b==='tear_through_the_portal'&&FaBDTDPortal($p,intval(CardPitch($o->CardID)))==='')return false;
 if($b==='cleansing_light'&&FaBDTDRefs($p,'Arena','redAura',true)==='')return false;
 return true;
}
function FaBDTDHeraldCards(): string {$out=[];foreach(FaBLiveSeats() as $p)foreach(['CombatChain','Stack'] as $z)foreach($z==='Stack'?GetStack():GetCombatChain($p) as $o)if(empty($o->removed)&&FaBWTRIsAttackAction($o)&&FaBDTDHerald($o))$out[]=FaBDTDSource(intval($o->UniqueID));return implode('&',array_unique(array_filter($out)));}
function FaBDTDAbilityRows(): array {
 $r=['prism_advent_of_thrones'=>[['INSTANT',2,false,false,true,0,'Awaken a figment']],'prism_awakener_of_sol'=>[['INSTANT',2,false,false,true,0,'Awaken a figment']], 'luminaris_celestial_fury'=>[['INSTANT',2,false,false,true,0,'Give angel or Herald go again']], 'empyrean_rapture'=>[['INSTANT',1,false,false,true,0,'Gain ward 1']], 'ironsong_versus'=>[['ACTION',1,false,true,true,0,'Sword hit creates Courage']], 'scepter_of_pain'=>[['ACTION',2,false,false,true,0,'Deal arcane damage and create Runechants']], 'spoiled_skull'=>[['ACTION',1,false,true,false,0,'Play a random banished action']], 'grimoire_of_the_haunt'=>[['INSTANT',1,false,false,false,0,'Create Eloquence']], 'levia_redeemed'=>[['ACTION',0,false,false,false,0,'Transform into Levia Redeemed']]];
 $r['blasmophet_levia_consumed']=$r['levia_redeemed'];
 foreach(['radiant_view','radiant_raiment','radiant_flow'] as $id)$r[$id]=[['INSTANT',0,false,false,false,0,'Banish with soul to prevent damage']];
 foreach(['red','yellow','blue'] as $color)$r['v_for_valor_'.$color]=[['REACTION',1,true,false,false,0,'Charge and empower attack']];return $r;
}
function FaBDTDAbilityLegal(int $p,array $f,array $spec): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);
 if(!FaBDTDRestrictions($p,$o,$spec['timing']==='ACTION',false))return false;
 if(in_array($b,['prism_advent_of_thrones','prism_awakener_of_sol'],true))return FaBMONSoul($p)!==''&&FaBDTDRefs($p,'Arena','figment')!=='';
 if(in_array($b,['radiant_view','radiant_raiment','radiant_flow'],true))return FaBMONSoul($p)!=='';
 if($b==='v_for_valor'){foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBAvailablePitch($p,intval($o->UniqueID))>=FaBWTRAbilityCost($p,$spec))return true;}return false;}
 if($b==='luminaris_celestial_fury')return FaBDTDAttacks($p,'angelHerald')!=='';
 if($b==='spoiled_skull')return count(array_unique(array_map(fn($r)=>CardName(FaBIdentityFromMZ($r)['object']->CardID),array_filter(explode('&',FaBDTDRefs($p,'Banish','action'))))))>=3;
 if(in_array($b,['levia_redeemed','blasmophet_levia_consumed'],true))return $f['zone']==='Inventory'&&FaBMONBloodDebt($p)>=13&&FaBMONHero($p,'levia');
 return true;
}
function FaBDTDPrepareAbility(int $p,object $o): bool {return isset(FaBDTDAbilityRows()[$o->CardID])&&FaBRunSourceMacro('PrepareCard',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))])>0;}
function FaBDTDPaid(int $p,object $o): void {
 if(FaBHasType($o,'Hero'))FaBDTDAdd($p,'HERO_USED');
 if(in_array($o->CardID,['radiant_view','radiant_raiment','radiant_flow','spoiled_skull','grimoire_of_the_haunt'],true))FaBMoveUID(intval($o->UniqueID),'Banish',$p);
}
function FaBDTDAbilityCost(int $p,array $spec): int {return in_array($spec['cardID']??'', ['prism_advent_of_thrones','prism_awakener_of_sol'],true)&&$p===intval(GetTurnPlayer())&&FaBDTDCount($p,'HERALD_SOUL')&&!FaBDTDCount($p,'HERO_USED')&&FaBCRUEquipment($p,'empyrean_rapture')?-2:0;}
function FaBDTDTransform(int $p,string $id): void {
 $refs=array_merge(FaBChoiceRefs($p,'Inventory',['base'=>$id]),FaBChoiceRefs($p,'Inventory',['base'=>$id==='levia_redeemed'?'blasmophet_levia_consumed':'levia_redeemed']));if(!$refs)return;
 $uid=intval(FaBIdentityFromMZ($refs[0])['object']->UniqueID);
 foreach(FaBChoiceRefs($p,'Hero') as $r)FaBMoveUID(intval(FaBIdentityFromMZ($r)['object']->UniqueID),'Soul',$p);
 $heroes=&GetHero($p);$heroes=[];$o=FaBMoveUID($uid,'Hero',$p);$o->CardID=$id;$o->TurnEffects=[];$o->Counters=[];AddHealth($p,intval(CardHealth($id)));
 FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='NO_HERO_ABILITY')));
}
function FaBDTDRedeem(int $p): void {foreach(FaBChoiceRefs($p,'Banish') as $r)FaBDTDFaceDown($r);FaBDTDTransform($p,'levia_redeemed');}
function FaBDTDDinner(int $p,int $uid): void {$refs=FaBChoiceRefs($p,'Graveyard');$refs=array_values(array_filter($refs,fn($r)=>intval(FaBIdentityFromMZ($r)['object']->UniqueID)!==$uid));$n=0;for($i=0;$i<3&&$refs;++$i){$j=EngineRandomInt(0,count($refs)-1);$r=$refs[$j];array_splice($refs,$j,1);$o=FaBIdentityFromMZ($r)['object'];if(FaBWTRIsAttackAction($o)&&FaBMONBasePower($p,$o)>=6){FaBMoveUID(intval($o->UniqueID),'Deck',$p);++$n;}}if($n>0)FaBShuffleDeck($p);FaBCRUGainLife($p,$n);FaBMoveUID($uid,'Banish',$p);}
function FaBDTDTop(int $p,string $to='Banish'): ?object {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);return $f?FaBMoveUID(intval($f['object']->UniqueID),$to,$p):null;}
function FaBDTDVile(int $p,int $victim): void {$o=FaBDTDTop($victim);if($o&&intval(CardPitch($o->CardID))===1)FaBARCLoseLife($victim,1,$p);}
function FaBDTDUnitedOrder(int $p): array {$seats=FaBLiveSeats();$idx=array_search($p,$seats,true);return array_merge(array_slice($seats,$idx+1),array_slice($seats,0,$idx+1));}
function FaBDTDDiplomacy(int $p,string $mode): void {FaBWTRAddEffect($p,$mode==='0'?'DTD_WAR':'DTD_PEACE',1,[],true);}
function FaBDTDNameLock(int $p,string $name): void {FaBDTDAdd($p,'CENSOR',1,['name'=>$name,'expiresAfterTurnOf'=>$p]);}
function FaBDTDHackTargets(int $victim,int $n): string {return implode('&',array_filter(FaBChoiceRefs($victim,'Arena',['type'=>'Aura','maxCost'=>$n]),fn($r)=>!FaBHasType(FaBIdentityFromMZ($r)['object'],'Token')));}
function FaBDTDFindFigment(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Deck'||!FaBHasType($f['object'],'Figment'))return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o)FaBRunSourceMacro('ResolveCard',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))]);}
function FaBDTDTopChoice(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Graveyard')FaBARCToDeck($p,intval($f['object']->UniqueID),true);}
function FaBDTDBellona(int $p): void {foreach(FaBChoiceRefs($p,'Arena',['type'=>'Angel']) as $r){$o=FaBIdentityFromMZ($r)['object'];FaBSetObjectCounter($o,'POWER',intval(FaBObjectCounters($o)['POWER']??0)+1);}}
function FaBDTDPeekSoul(int $p,array $uids,string $chosen): void {$f=FaBIdentityFromMZ($chosen);if($f&&in_array(intval($f['object']->UniqueID),$uids,true)&&intval(CardPitch($f['object']->CardID))===2){FaBRevealChoices($p,$chosen);FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);}FaBDYNFinishPeek($p,$uids,false);}
function FaBDTDPrayer(int $p): bool {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return false;FaBRevealChoices($p,$r);if(intval(CardPitch($f['object']->CardID))!==2)return false;FaBMoveUID(intval($f['object']->UniqueID),'Hand',$p);return true;}
function FaBDTDBanishSoul(int $p,string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Soul'&&$f['player']===$p){FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);++$n;}}return $n;}
function FaBDTDLance(string $r,string $modes): void {foreach(explode(',',$modes) as $mode)FaBDYNTag($r,['WTR_POWER:2','DTD_DRAW_HIT','DTD_AGAIN_HIT'][intval($mode)]??'');}
function FaBDTDNasreth(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Soul')return;$light=FaBHasType($f['object'],'Light');FaBMoveUID(intval($f['object']->UniqueID),'Banish',$f['player']);if($light)FaBCRUGainLife($p,1);}
function FaBDTDPortal(int $p,int $pitch): string {return implode('&',array_filter(FaBChoiceRefs($p,'Banish',['type'=>'Action']),fn($r)=>empty(FaBIdentityFromMZ($r)['object']->FaceDown)&&intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===$pitch));}
function FaBDTDSkull(int $p,int $uid,string $refs): void {$cards=[];$names=[];foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Banish'||$f['player']!==$p||!empty($f['object']->FaceDown)||!FaBHasType($f['object'],'Action'))return;$cards[]=intval($f['object']->UniqueID);$names[]=CardName($f['object']->CardID);}if(count($cards)!==3||count(array_unique($names))!==3)return;FaBARCSetCard($uid,'dtdSkull',$cards[EngineRandomInt(0,2)]);}
function FaBDTDHalve(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;FaBWTRTag($f['object'],'DTD_HALF_BASE');}
function FaBDTDSteal(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o)$o->Controller=$p;}
function FaBDTDInspect(int $p,int $victim,bool $public): array {if($public)FaBRevealChoices($victim,implode('&',FaBChoiceRefs($victim,'Hand')));return FaBDYNPrivateHand($p,$victim);}
function FaBDTDFinishInspect(int $p,int $victim,array $uids,string $chosen,int $uid,bool $copy): void {
 $f=FaBDYNPrivateOriginal($chosen);if($f&&$f['player']===$victim&&$f['zone']==='Hand'&&FaBWTRIsAttackAction($f['object'])){
  if($copy){$a=FaBFindUID($uid);if($a){$c=FaBObjectCounters($a['object']);$c['DTD_ORIGINAL_ID']=$a['object']->CardID;$a['object']->Counters=$c;$a['object']->CardID=$f['object']->CardID;}}
  else{FaBRevealChoices($victim,$f['mzID']);FaBARCToDeck($victim,intval($f['object']->UniqueID),false);FaBWTRCreateArena($victim,'ponder');}
 }foreach($uids as $u){$f=FaBFindUID(intval($u));if($f&&$f['zone']==='Temp')$f['object']->removed=true;}
}
function FaBDTDDawnblades(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Weapons'),fn($r)=>str_starts_with(FaBIdentityFromMZ($r)['object']->CardID,'dawnblade')));}
function FaBDTDFreezeTargets(int $p,string $kind): string {return $kind==='Equipment'?implode('&',FaBChoiceRefs($p,'Equipment')):implode('&',FaBChoiceRefs($p,'Arena',['type'=>$kind]));}
function FaBDTDOpposingTargets(int $p): string {return implode('&',array_filter(explode('&',FaBUPRAnyTargets($p)),fn($r)=>FaBIdentityFromMZ($r)['player']!==$p));}
function FaBDTDHideBanish(int $p): void {foreach(FaBChoiceRefs($p,'Banish') as $r)FaBDTDFaceDown($r);}
function FaBDTDForcefield(int $p,array $used=[]): bool {return FaBDTDForcefieldUID($p,$used)>0;}
function FaBDTDForcefieldUID(int $p,array $used=[]): int {if(FaBMONSoul($p)==='')return 0;foreach(FaBMONArena($p,'radiant_forcefield') as $r){$uid=intval(FaBIdentityFromMZ($r)['object']->UniqueID);if(!in_array($uid,$used,true))return $uid;}return 0;}
function FaBDTDPreventionChoices(int $p,int $n,array $used=[],string $type='PHYSICAL'): string {
 if($p<1||$n<1||!FaBSeatIsLive($p))return '';$out=FaBMSTWardRefs($p);if($type==='ARCANE')$out=array_merge($out,FaBROSShelters($p));
 foreach(['shroud_of_darkness','cloak_of_darkness','grasp_of_darkness','dance_of_darkness'] as $id)$out=array_merge($out,FaBCRUEquipment($p,$id));
 if(FaBDTDForcefield($p,$used))$out=array_merge($out,FaBChoiceRefs($p,'Soul'));
 if($n>=intval(GetHealth($p))&&FaBDTDCount($p,'MORLOCK'))foreach(['Hand','Arsenal'] as $z)$out=array_merge($out,FaBChoiceRefs($p,$z,['base'=>'minerva_themis']));
 return implode('&',$out);
}
function FaBDTDPreventionPay(int $p,string $r,int $n,array &$used=[]): int {
 if(in_array($r,FaBROSShelters($p),true)){$o=FaBIdentityFromMZ($r)['object'];FaBMONDestroy(intval($o->UniqueID));return 1;}if(!in_array($r,explode('&',FaBDTDPreventionChoices($p,$n,$used)),true))return 0;$f=FaBIdentityFromMZ($r);if(FaBMSTWardActive($f['object'])){$value=FaBMSTWard($p,$f['object']);FaBMONDestroy(intval($f['object']->UniqueID));return $value;}$value=$f['zone']==='Soul'?1:2;if($f['zone']==='Soul')$used[]=FaBDTDForcefieldUID($p,$used);
 if($f['object']->CardID==='minerva_themis'){$value=$n;FaBDTDClear($p,'MORLOCK');}
 FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);return $value;
}
function FaBDTDStorePrevention(int $p,int $source,int $n): void {if($n>0)FaBDTDAdd($p,'PREVENT_PACKET',$n,['sourceUID'=>$source]);}
function FaBDTDConsumePrevention(int $p,int $source,int $n): int {$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='DTD_PREVENT_PACKET'&&intval($e['sourceUID']??0)===$source)$n=max(0,$n-intval($e['amount']));else $left[]=$e;}FaBWTRSetEffects($p,$left);return $n;}
function FaBDTDUnpreventable(int $p,int $source,string $type): bool {return FaBUPRUnpreventable($source)||($type==='ARCANE'&&FaBEVRUnpreventable($p,0));}
function FaBDTDCombatPrevention(): bool {
 $s=FaBGetState();$uid=intval($s['attackUID']);$targets=$s['attackTargets']??[];if(!$targets)$targets=[$s['attackTarget']??[]];
 foreach($targets as $target){if(($target['type']??'HERO')!=='HERO')continue;$victim=intval($target['player']??0);if(!$victim)continue;
  $key='dtdPrevention'.$victim;if(FaBARCCard($uid,$key))continue;$n=max(0,FaBAttackPower($s)-FaBDefenseValue($s,$victim));$o=FaBFindUID($uid);if($o&&in_array('WTR_DOUBLE_DAMAGE',(array)$o['object']->TurnEffects,true))$n*=2;
  FaBARCSetCard($uid,$key,true);
  if(FaBDTDPreventionChoices($victim,$n)==='')continue;
  FaBRunSourceMacro('ResolveAbility',$victim,'radiant_forcefield_yellow',['mzID'=>FaBDTDSource($uid),'dtdDamage'=>$n,'dtdSourceUID'=>$uid]);return true;
 }return false;
}
function FaBDTDArmRune(int $p,int $uid): void {if(FaBDTDCount($p,'UNPREVENTABLE_RUNE')){FaBDTDClear($p,'UNPREVENTABLE_RUNE');FaBTagUID($uid,'UPR_UNPREVENTABLE');}}
function FaBDTDCostPitchChoices(int $p,int $reserve=1): string {
 $cost=intval(FaBGetState()['pendingPayment']['cost']??0);$refs=FaBChoiceRefs($p,'Hand');$out=[];
 foreach($refs as $r){$o=FaBIdentityFromMZ($r)['object'];$rest=array_values(array_filter($refs,fn($other)=>$other!==$r));if(count($rest)<$reserve)continue;$values=array_map(fn($other)=>FaBMONPitchValue($p,FaBIdentityFromMZ($other)['object']->CardID),$rest);
  $available=intval(GetResources($p))+FaBMONPitchValue($p,$o->CardID)+array_sum($values)-($reserve&&$values?min($values):0);
  if($available>=$cost&&in_array($r,explode('&',FaBARCPitchChoices($p)),true))$out[]=$r;
 }return implode('&',$out);
}
function FaBDTDBeginActionPhase(): void {$s=FaBGetState();$s['dtdStarting']=false;FaBSetState($s);}
