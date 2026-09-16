<?php
/** The Hunted: hero conditions and persistent identities are independent of seat order. */
function FaBHNTCount(int $p,string $k): int {return FaBARCEffect($p,'HNT_'.$k);}
function FaBHNTAdd(int $p,string $k,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'HNT_'.$k,$n,$data);}
function FaBHNTClear(int $p,string $k): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='HNT_'.$k)));}
function FaBHNTMarked(int $p): bool {if(!FaBSeatIsLive($p))return false;$f=FaBFindUID(FaBUPRHeroUID($p));return $f&&!empty(FaBObjectCounters($f['object'])['HNT_MARKED']);}
function FaBHNTMark(int $p,bool $marked=true): void {if(!FaBSeatIsLive($p))return;$f=FaBFindUID(FaBUPRHeroUID($p));if($f&&FaBSeatIsLive($p))FaBSetObjectCounter($f['object'],'HNT_MARKED',$marked?1:0);}
function FaBHNTMarkedBadge($o=null): int {$o??=$GLOBALS['obj']??null;return is_object($o)?intval(FaBObjectCounters($o)['HNT_MARKED']??0):0;}
function FaBHNTMarkedTarget(): bool {return FaBFaiHeroHit()&&FaBHNTMarked(intval(FaBGetState()['defender']));}
function FaBHNTArakni(int $p): bool {$f=FaBFindUID(FaBUPRHeroUID($p));return $f&&str_starts_with(strtolower(CardName($f['object']->CardID)),'arakni');}
function FaBHNTAgents(): array {return ['arakni_black_widow','arakni_funnel_web','arakni_orb_weaver','arakni_redback','arakni_tarantula','arakni_trap_door'];}
function FaBHNTBecome(int $p,int $index=-1): void {
 $f=FaBFindUID(FaBUPRHeroUID($p));if(!$f)return;$o=$f['object'];$original=FaBObjectCounters($o)['HNT_BASE_HERO']??$o->CardID;
 $c=FaBObjectCounters($o);$c['HNT_BASE_HERO']=$original;$o->Counters=$c;$o->CardID=FaBHNTAgents()[$index>=0&&$index<6?$index:random_int(0,5)];
 if($o->CardID==='arakni_trap_door')FaBROSQueue($p,$o->CardID,intval($o->UniqueID));
}
function FaBHNTReturnBrood(int $p): void {$f=FaBFindUID(FaBUPRHeroUID($p));if($f){$original=FaBObjectCounters($f['object'])['HNT_BASE_HERO']??null;if($original)$f['object']->CardID=$original;}}
function FaBHNTHitMarked(int $uid,int $v): bool {return (bool)FaBARCCard($uid,'hntHitMarked'.$v);}
// Capture the condition before removing it. Hit abilities see the hit event, not the later condition.
function FaBHNTBeforeHit(int $p,object $o,int $v): void {
 $marked=FaBHNTMarked($v);FaBARCSetCard(intval($o->UniqueID),'hntHitMarked'.$v,$marked);
 if($p!==$v&&in_array($v,FaBOpponents($p),true))FaBHNTMark($v,false);
 if($marked&&FaBHasKeyword($o,'Stealth')&&(FaBMONHero($p,'arakni_marionette')||FaBMONHero($p,'arakni_web_of_deceit')))FaBWTRTag($o,'GO_AGAIN');
 if($marked&&(FaBMONHero($p,'cindra')||FaBMONHero($p,'fang')))FaBHVYToken($p,'fealty',1,$p,false);
 if($marked)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$c=FaBIdentityFromMZ($r)['object'];if(intval($c->UniqueID)===intval($o->UniqueID)||HasNoAbilities($c)||($c->Role??'')!=='ATTACK')continue;$b=FaBWTRBase($c->CardID);if($b==='defang_the_dragon'&&FaBMONHero($v,'fang')||$b==='extinguish_the_flames'&&FaBMONHero($v,'cindra'))DoDrawCard($p,1);}
}
function FaBHNTHit(int $p,object $o,int $v): void {
 $uid=intval($o->UniqueID);$marked=FaBHNTHitMarked($uid,$v);$tags=(array)($o->TurnEffects??[]);
 if(FaBHasType($o,'Dagger')){
  $owner=intval($o->Owner??$p)?:$p;
  if(FaBMONHero($owner,'arakni_tarantula'))FaBARCLoseLife($v,1,$owner);
  if(FaBHNTCount($owner,'POISON_CHAIN'))FaBARCLoseLife($v,FaBHNTCount($owner,'POISON_CHAIN'),$owner);
  if($marked&&FaBHNTCount($p,'SAVOR')){DoDrawCard($p,FaBHNTCount($p,'SAVOR'));FaBHNTClear($p,'SAVOR');}
  $weapon=FaBHNTWeaponUID($o);if(FaBARCCard($weapon,'hntMarkNextTurn',-1)===intval(GetTurnNumber())){FaBARCSetCard($weapon,'hntMarkNextTurn',-1);FaBHNTMark($v);}
 }
 if(in_array('HNT_MARK',$tags,true))FaBHNTMark($v);
 if($marked&&in_array('HNT_EXTRA_MARKED',$tags,true))FaBROSQueue($p,'twist_and_turn_red',$uid,['rosEvent'=>'extra']);
 if(in_array('HNT_BLACK_WIDOW',$tags,true))FaBROSQueue($p,'mark_of_the_black_widow_red',$uid,['rosTarget'=>$v]);
 if(in_array('HNT_FUNNEL_WEB',$tags,true))FaBROSQueue($p,'mark_of_the_funnel_web_red',$uid,['rosTarget'=>$v]);
 if(FaBHNTCount($p,'SEAL')){FaBHNTClear($p,'SEAL');foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($v,'Arsenal'))) as $u)FaBMONDestroy($u);}
}
function FaBHNTAnyHit(int $p,object $o): void {
 if(FaBHasType($o,'Dagger')){FaBHNTAdd($p,'DAGGER_HIT_CHAIN');foreach(FaBCRUEquipment($p,'blood_splattered_vest') as $r){$a=FaBIdentityFromMZ($r)['object'];FaBROSQueue($p,$a->CardID,intval($a->UniqueID));}}
 if(in_array('HNT_EXTRA',(array)$o->TurnEffects,true))FaBROSQueue($p,'twist_and_turn_red',intval($o->UniqueID),['rosEvent'=>'extra']);
}
function FaBHNTPseudoHit(int $p,int $uid,int $v,int $n): void {
 $f=FaBFindUID($uid);if(!$f||$n<=0||!FaBSeatIsLive($v))return;$o=$f['object'];FaBHNTBeforeHit($p,$o,$v);FaBHNTAnyHit($p,$o);
 $s=FaBGetState();$s['daggerHits']=intval($s['daggerHits']??0)+1;FaBSetState($s);
 FaBOUTDaggerHit($p,$o,$v,$n);if($o->CardID==='spiders_bite')FaBDYNAdd($v,'SPIDER');FaBHNTHit($p,$o,$v);
 if($o->CardID==='hunters_klaive')FaBHNTMark($v);
 if(FaBWTRBase($o->CardID)==='kiss_of_death')FaBARCLoseLife($v,1,$p);
 if($o->CardID==='mark_of_the_huntsman')FaBROSQueue($p,$o->CardID,$uid,['rosTarget'=>$v]);
}
function FaBHNTWeaponUID(object $o): int {return intval(FaBObjectCounters($o)['WEAPON_UID']??FaBObjectCounters($o)['MON_SOURCE_UID']??$o->UniqueID);}
function FaBHNTExtra(int $uid): void {$f=FaBFindUID($uid);if(!$f)return;$u=FaBHNTWeaponUID($f['object']);$w=FaBFindUID($u);if($w){$tags=(array)$w['object']->TurnEffects;$tags[]='CRU_EXTRA_ATTACK';$w['object']->TurnEffects=$tags;}}
function FaBHNTEqpDaggers(int $p,string $zone='Weapons',bool $draconic=false): string {return implode('&',array_filter(FaBChoiceRefs($p,$zone,['type'=>'Dagger']),fn($r)=>($o=FaBIdentityFromMZ($r)['object'])&&FaBWTRIsWeapon($o)&&(!$draconic||FaBHasType($o,'Draconic'))));}
// Kiss of Death is a dagger in play, but is not a weapon that can be equipped.
function FaBHNTDaggers(int $p): string {$refs=FaBChoiceRefs($p,'Weapons',['type'=>'Dagger']);foreach(['Arena','CombatChain'] as $zone)foreach(FaBChoiceRefs($p,$zone,['type'=>'Dagger']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(empty(FaBObjectCounters($o)['WEAPON_UID']))$refs[]=$r;}return implode('&',$refs);}
function FaBHNTEquip(int $p,string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if(!$f||$f['player']!==$p||$f['zone']!=='Graveyard'||!FaBWTRIsWeapon($f['object'])||!FaBArakniWeaponSpace($p))continue;$o=FaBMoveUID($u,'Weapons',$p);if($o){$o->Status=2;$o->TurnEffects=[];$o->Counters=[];FaBARCSetCard($u,'hntPerforateTurn',-1);FaBARCSetCard($u,'hntMarkNextTurn',-1);}}}
function FaBHNTGraphene(int $p): void {if(FaBArakniWeaponSpace($p))AddWeapons($p,CardID:'graphene_chelicera',Owner:$p,Controller:$p,Status:2);}
function FaBHNTNext(int $p,string $kind,int $power=0,string $tags='',bool $chain=false): void {FaBHNTAdd($p,'NEXT',$power,['kind'=>$kind,'tags'=>array_filter(explode(',',$tags)),'chain'=>$chain]);}
function FaBHNTMatch(object $o,string $kind): bool {return match($kind){'ANY'=>true,'AA'=>FaBWTRIsAttackAction($o),'STEALTHAA'=>FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Stealth'),'DRACONIC'=>FaBHasType($o,'Draconic'),'STEALTH'=>FaBHasKeyword($o,'Stealth'),'SMALL'=>intval(CardPower($o->CardID))<=3,'ANGEL'=>FaBHasType($o,'Angel'),default=>FaBDYNMatch($o,$kind)};}
function FaBHNTAffectsAttack(int $p,string $kind): string {$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));return $f&&$s['attacker']===$p&&FaBHNTMatch($f['object'],$kind)?$f['mzID']:'';}
function FaBHNTPreviousDraconic(): bool {return !empty(FaBGetState()['hntPreviousDraconic']);}
function FaBHNTVest(int $uid): void {$f=FaBFindUID($uid);if(!$f||!FaBDYNEquipmentStillPresent($uid))return;$o=$f['object'];$n=intval(FaBObjectCounters($o)['STAIN']??0)+1;AddResources($f['player'],intval(GetResources($f['player']))+1);FaBSetObjectCounter($o,'STAIN',$n);if($n>=3)FaBMONDestroy($uid);}
function FaBHNTAfterMove(int $p,object $o,string $from,string $to): void {if($to==='Arena'&&$from!=='Arena'){if($o->CardID==='fealty')FaBHNTAdd($p,'FEALTY');if($o->CardID==='seismic_surge')FaBHNTAdd($p,'SEISMIC');}}
function FaBHNTPrevent(int $p,int $n): int {if($n<=0)return $n;$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='HNT_CALM'&&$n>0){--$n;if(--$e['amount']<=0)continue;}$left[]=$e;}FaBWTRSetEffects($p,$left);return $n;}
function FaBHNTOpenHands(int $p): int {$n=count(FaBChoiceRefs($p,'Equipment',['type'=>'Quiver']));foreach(FaBChoiceRefs($p,'Weapons') as $r)$n+=FaBHasType(FaBIdentityFromMZ($r)['object'],'2H')?2:1;return max(0,2-$n);}
function FaBHNTUIDDraconic(int $uid): bool {$f=FaBFindUID($uid);return $f&&FaBHasType($f['object'],'Draconic');}
function FaBHNTTagged(int $uid,string $tag): bool {$f=FaBFindUID($uid);return $f&&in_array($tag,(array)$f['object']->TurnEffects,true);}
function FaBHNTDestroyWeapon(int $uid): void {$f=FaBFindUID($uid);if($f)FaBMONDestroy(FaBHNTWeaponUID($f['object']));}
function FaBHNTExtraIfWeaponOrAlly(string $ref): void {$f=FaBIdentityFromMZ($ref);if($f&&(FaBWTRIsWeapon($f['object'])||FaBHasType($f['object'],'Ally')))FaBHNTExtra(intval($f['object']->UniqueID));}
function FaBHNTBuffDaggers(int $p,int $power,bool $extra=false): void {foreach(FaBUPRUIDs(FaBHNTEqpDaggers($p)) as $u){FaBTagUID($u,'WTR_POWER:'.$power);if($extra)FaBHNTExtra($u);}}
function FaBHNTWrath(int $p): void {foreach(FaBUPRUIDs(FaBHNTEqpDaggers($p)) as $u){$o=FaBFindUID($u)['object'];FaBSetObjectCounter($o,'HNT_WRATH_CHAIN',intval(FaBObjectCounters($o)['HNT_WRATH_CHAIN']??0)+1);}}
function FaBHNTWrathAmount(int $uid): int {$f=FaBFindUID($uid);return $f?intval(FaBObjectCounters($f['object'])['HNT_WRATH_CHAIN']??0):0;}
function FaBHNTPerforate(string $ref): void {$f=FaBIdentityFromMZ($ref);if(!$f)return;$u=intval($f['object']->UniqueID);$n=FaBARCCard($u,'hntPerforateTurn',-1)===intval(GetTurnNumber())?intval(FaBARCCard($u,'hntPerforate')):0;FaBARCSetCard($u,'hntPerforateTurn',intval(GetTurnNumber()));FaBARCSetCard($u,'hntPerforate',$n+1);FaBHNTExtra($u);}
function FaBHNTArakniTargets(int $p): string {return implode('&',array_filter(explode('&',FaBDYNHeroTargets($p)),fn($r)=>($f=FaBIdentityFromMZ($r))&&FaBHNTArakni($f['player'])));}
function FaBHNTRevealReactions(int $v): bool {if(!FaBSeatIsLive($v))return false;$refs=FaBChoiceRefs($v,'Hand');FaBRevealChoices($v,implode('&',$refs));return count(array_filter($refs,fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Attack Reaction')))>0;}
function FaBHNTBanishPlay(int $uid): void {$f=FaBFindUID($uid);if(!$f)return;$p=intval($f['object']->Owner??$f['player'])?:$f['player'];$o=FaBMoveUID($uid,'Banish',$p);if($o)$o->PlayableFromBanish=1;}
function FaBHNTTrap(int $p,int $uid,bool $nextStart): void {$f=FaBFindUID($uid);if(!$f)return;FaBHNTBanishPlay($uid);$f=FaBFindUID($uid);if(!$f)return;$o=$f['object'];if($nextStart){$o->FaceDown=1;$o->PlayableFromBanish=FaBHasType($o,'Trap')?1:0;FaBARCSetCard($uid,'hntTrapUntilStart',true);}else FaBARCSetCard($uid,'hntTrapReplaceTurn',intval(GetTurnNumber()));}
function FaBHNTNullSteam(int $uid): void {$f=FaBFindUID($uid);if($f)FaBSetObjectCounter($f['object'],'STEAM',2);}
function FaBHNTSchism(): void {foreach(FaBLiveSeats() as $p){FaBShuffleDeck($p);$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if($f){$o=FaBMoveUID(intval($f['object']->UniqueID),'Arsenal',$p);if($o)$o->FaceDown=1;}}}
function FaBHNTBubble(int $p): void {foreach(FaBChoiceRefs($p,'Deck') as $r){$f=FaBIdentityFromMZ($r);FaBRevealChoices($p,$r);if(intval(CardPitch($f['object']->CardID))===1){FaBHNTBanishPlay(intval($f['object']->UniqueID));break;}}FaBShuffleDeck($p);}
function FaBHNTScale(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;FaBDYNCounter($r,'DEFENSE',-1);if(FaBCurrentDefense($f['object'],$f['player'])<=0)FaBMONDestroy(intval($f['object']->UniqueID));}
function FaBHNTDefended(int $p,string $b): void {$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a)return;if($b==='kabuto_of_imperial_authority'){FaBHNTAdd($p,'KABUTO');return;}if($b==='smoke_out'&&intval(CardPitch($a['object']->CardID))===1||$b==='den_of_the_spider'&&FaBAttackPower($s)>intval(CardPower($a['object']->CardID))||$b==='lair_of_the_spider'&&FaBAttackHasGoAgain($s,$a['object']))FaBHNTMark(intval($s['attacker']));}
function FaBHNTDefend(int $p,int $uid,bool $quick=false): void {$f=FaBFindUID($uid);$s=FaBGetState();if(!$f||$f['player']!==$p||!in_array($f['zone'],['Arsenal','Equipment','Hand'],true))return;$from=$f['zone'];$o=FaBMoveUID($uid,'CombatChain',$p);if(!$o)return;$o->Role='DEFENSE';$o->FromZone=$from;$o->ChainLink=intval($s['chainLink']);FaBSetObjectCounter($o,'DEFENDING_HERO',intval($s['defender']));if($quick){FaBWTRTag($o,'WTR_DEFENSE:2');FaBARCSetCard($uid,'hntDefendedTurn',intval(GetTurnNumber()));}OnDefended($p,FaBFindUID($uid)['mzID'],$p);}
function FaBHNTFaceUpInstant(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Arsenal'){$f['object']->FaceDown=0;FaBARCSetCard(intval($f['object']->UniqueID),'instantTurn',intval(GetTurnNumber()));}}
function FaBHNTStealthAA(int $p,string $zone): string {return implode('&',array_filter(FaBChoiceRefs($p,$zone),fn($r)=>($o=FaBIdentityFromMZ($r)['object'])&&FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Stealth')));}
function FaBHNTCopy(int $uid,string $ref): void {$f=FaBFindUID($uid);$t=FaBIdentityFromMZ($ref);if(!$f||!$t)return;$id=$t['object']->CardID;FaBDYNBanishChoice($ref,$f['player']);$c=FaBObjectCounters($f['object']);$c['HNT_ORIGINAL']=$c['HNT_ORIGINAL']??$f['object']->CardID;$f['object']->Counters=$c;$f['object']->CardID=$id;}
function FaBHNTDamageRecord(int $p,int $v,int $n,int $source,string $type): void {
 if($n<=0)return;$f=FaBFindUID($source);$u=$f&&FaBHasType($f['object'],'Ally')?FaBHNTWeaponUID($f['object']):FaBUPRHeroUID($p);FaBHNTAdd($v,'DAMAGED_BY',1,['uid'=>$u]);
 FaBHNTOwnDamage($p,$n,$type);
}
function FaBHNTOwnDamage(int $p,int $n,string $type): void {if($n<=0)return;
 if($type==='ARCANE'&&!FaBHNTCount($p,'ARCANE_DEALT')){FaBHNTAdd($p,'ARCANE_DEALT');foreach(FaBMONArena($p,'ring_of_roses') as $r)FaBCRUGainLife($p,1);}
}
function FaBHNTShock(int $p): void {$seen=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')!=='HNT_DAMAGED_BY')continue;$u=intval($e['uid']);if(isset($seen[$u]))continue;$seen[$u]=true;$f=FaBFindUID($u);if(!$f||!in_array($f['player'],FaBOpponents($p),true))continue;if($f['zone']==='Hero')FaBARCLoseLife($f['player'],1,$p);elseif(FaBHasType($f['object'],'Ally')){$f['object']->Damage=intval($f['object']->Damage)+1;if(intval($f['object']->Damage)>=FaBUPRHealth($f['object']))FaBMONDestroy($u);}}}
function FaBHNTOnePower(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard'),fn($r)=>is_numeric(CardPower(FaBIdentityFromMZ($r)['object']->CardID))&&intval(CardPower(FaBIdentityFromMZ($r)['object']->CardID))===1));}
function FaBHNTRottenReady(): bool {foreach(FaBLiveSeats() as $p)if(FaBHNTOnePower($p)==='')return false;return true;}
function FaBHNTSpurWinner(array $choices): int {if(!$choices)return 0;$highest=max($choices);$winners=array_keys(array_filter($choices,fn($n)=>$n===$highest));return count($winners)===1?intval($winners[0]):0;}
function FaBHNTRevealSpur(array $choices): void {$s=FaBGetState();$s['hntRevealedNumbers']=$choices;FaBSetState($s);if(function_exists('SetFlashMessage')){ $parts=[];foreach($choices as $p=>$n)$parts[]='Player '.$p.': '.$n;SetFlashMessage('Spur Locked — '.implode(', ',$parts));}}
function FaBHNTFaceDownActions(int $p): string {return implode('&',array_filter(explode('&',FaBROSRefs($p,'Arsenal','NAA')),fn($r)=>($f=FaBIdentityFromMZ($r))&&!empty($f['object']->FaceDown)));}
function FaBHNTPreviousGustwave(): bool {foreach(FaBGetState()['outPreviousNames'][intval(GetTurnPlayer())]??[] as $name)if(str_contains($name,'Gustwave'))return true;return false;}
function FaBHNTSidesKinds(int $p): array {$r=[];foreach(['DAGGER','STEALTHAA'] as $k)if(FaBHNTAffectsAttack($p,$k)!=='')$r[]=$k;return $r;}
function FaBHNTSidesOptions(int $p): string {return implode('&',FaBHNTSidesKinds($p));}
function FaBHNTSidesKind(int $p,int $i): string {return FaBHNTSidesKinds($p)[$i]??'';}
function FaBHNTStealthDefenders(int $p): string {return FaBHNTAffectsAttack($p,'STEALTH')!==''?FaBDefendingChoices(intval(FaBGetState()['defender']),true):'';}
function FaBHNTToxinOptions(int $p): string {$k=[];if(FaBHNTAffectsAttack($p,'DAGGER')!=='')$k[]='DAGGER';if(FaBHNTStealthDefenders($p)!=='')$k[]='DEFENDER';return implode('&',$k);}
function FaBHNTToxinKinds(int $p,string $modes): array {$k=explode('&',FaBHNTToxinOptions($p));return array_values(array_filter(array_map(fn($i)=>$k[intval($i)]??null,explode(',',$modes))));}
function FaBHNTWhiskerKinds(int $p): array {$k=[];if(FaBHNTAffectsAttack($p,'DAGGER')!=='')$k[]='POWER';if(FaBHNTDaggers($p)!=='')$k=array_merge($k,['EXTRA','MARK']);return $k;}
function FaBHNTWhiskerOptions(int $p): string {return implode('&',FaBHNTWhiskerKinds($p));}
function FaBHNTWhiskerKind(int $p,int $i): string {return FaBHNTWhiskerKinds($p)[$i]??'';}
function FaBHNTNamedWeapons(int $p,string $name): string {return implode('&',array_filter(FaBChoiceRefs($p,'Weapons'),fn($r)=>str_contains(CardName(FaBIdentityFromMZ($r)['object']->CardID),$name)));}
function FaBHNTAllWeapons(int $p): string {$r=[];foreach(array_merge([$p],FaBAdjacentOpponents($p)) as $v)$r=array_merge($r,FaBChoiceRefs($v,'Weapons'));return implode('&',$r);}
function FaBHNTAllBanish(int $p): string {$r=[];foreach(array_merge([$p],FaBAdjacentOpponents($p)) as $v)$r=array_merge($r,FaBChoiceRefs($v,'Banish'));return implode('&',$r);}
function FaBHNTFaceDown(string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&$f['zone']==='Banish')$f['object']->FaceDown=1;}}
function FaBHNTIsAbility(int $uid): bool {$f=FaBFindUID($uid);return $f&&($f['object']->Kind??'')==='ABILITY';}
function FaBHNTBellona(int $p,int $source,int $amount): void {$f=FaBFindUID($source);if(!$f||$amount<=0||!FaBWTRIsWeapon($f['object']))return;$uid=FaBHNTWeaponUID($f['object']);$left=[];$triggers=0;foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='HNT_BELLONA'&&intval($e['uid'])===$uid&&$amount<=intval($e['amount']))++$triggers;else $left[]=$e;}FaBWTRSetEffects($p,$left);for($i=0;$i<$triggers;++$i)FaBROSQueue($p,'war_cry_of_bellona_yellow',0,['rosEvent'=>'reflect:'.$amount,'rosTarget'=>$f['player']]);}
function FaBHNTReflect(int $p,int $v,int $n): void {$GLOBALS['hntUnpreventableReflection']=true;try{DoDamage($p,'',$v,$n,'PHYSICAL');}finally{unset($GLOBALS['hntUnpreventableReflection']);}}
function FaBHNTVengeanceTarget(int $p,?array $target=null): bool {if(!FaBHNTCount($p,'VENGEANCE'))return false;if($target!==null)return ($target['type']??'')==='HERO'&&FaBHNTArakni(intval($target['player']??0));foreach(FaBLegalAttackTargets($p) as $t)if(($t['type']??'')==='HERO'&&FaBHNTArakni(intval($t['player'])))return true;return false;}
