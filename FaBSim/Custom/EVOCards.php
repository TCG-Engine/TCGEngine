<?php
// Bright Lights: public materials, explicit controllers, and UID-safe choices.
function FaBEVOEffect(int $p,string $key): int {return FaBARCEffect($p,'EVO_'.$key);}
function FaBEVOAdd(int $p,string $key,int $n=1,array $extra=[]): void {FaBWTRAddEffect($p,'EVO_'.$key,$n,$extra);}
function FaBEVOClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='EVO_'.$key)));}
function FaBEVOMaxx(int $p): bool {return FaBMONHero($p,'maxx_nitro')||FaBMONHero($p,'maxx_the_hype_nitro');}
function FaBEVODash(int $p): bool {return FaBMONHero($p,'dash_io')||FaBMONHero($p,'dash_database');}
function FaBEVOTeklo(int $p): bool {return FaBMONHero($p,'teklovossen')||FaBMONHero($p,'teklovossen_esteemed_magnate');}
function FaBEVOCrank(int $p,object $o): bool {return !HasNoAbilities($o)&&(FaBHasKeyword($o,'Crank')||(FaBWTRBase($o->CardID)==='hyper_driver'&&FaBEVOMaxx($p)));}
function FaBEVORefs(int $p,string $zone,string $kind='',bool $all=false): string {
 $out=[];foreach($all?FaBLiveSeats():[$p] as $seat)foreach(FaBChoiceRefs($seat,$zone) as $r){$f=FaBIdentityFromMZ($r);$o=$f['object'];if(!empty($o->FaceDown))continue;
  $ok=match($kind){'scrap'=>FaBHasType($o,'Item')||FaBHasType($o,'Equipment'),'item'=>FaBHasType($o,'Item'),'cheap'=>FaBHasType($o,'Item')&&is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))<=1,'mechCheap'=>FaBHasType($o,'Mechanologist')&&FaBHasType($o,'Item')&&is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))<=1,'crank'=>FaBEVOCrank($seat,$o),'evo'=>FaBHasType($o,'Evo'),'aa'=>FaBWTRIsAttackAction($o),'six'=>FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))===6,'mechAA'=>FaBWTRIsAttackAction($o)&&FaBHasType($o,'Mechanologist'),'driver'=>FaBWTRBase($o->CardID)==='hyper_driver','proto'=>str_starts_with($o->CardID,'proto_base_'),'angel'=>FaBHasType($o,'Angel')&&FaBHasType($o,'Ally'),'action'=>FaBHasType($o,'Action'),default=>true};if($ok)$out[]=$r;
 }return implode('&',$out);
}
function FaBEVOItemTargets(int $p,string $kind='',bool $all=false,bool $stack=false): string {
 $r=FaBEVORefs($p,'Arena',$kind?:'item',$all);if($stack)foreach(GetStack() as $o)if(is_object($o)&&empty($o->removed)&&intval($o->Controller)===$p&&FaBHasType($o,'Item')&&($kind!=='crank'||FaBEVOCrank($p,$o)))$r.=($r?'&':'').'Stack-'.$o->mzIndex;return $r;
}
function FaBEVOSteamTargets(int $p,bool $all=false): string {$out=[];foreach($all?FaBLiveSeats():[$p] as $s)foreach(['Equipment','Arena','Weapons','CombatChain'] as $z)foreach(FaBChoiceRefs($s,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if($z==='CombatChain'&&($o->FromZone??'')!=='Equipment')continue;if(FaBHasType($o,'Item')||FaBHasType($o,'Equipment')||FaBHasType($o,'Weapon'))$out[]=$r;}return implode('&',$out);}
function FaBEVOSteam(string $r,int $n=1): void {$f=FaBIdentityFromMZ($r);if($f)FaBARCSteam($f['object'],$n);}
function FaBEVORemoveSteam(string $r): int {$f=FaBIdentityFromMZ($r);if(!$f)return 0;$n=intval(FaBObjectCounters($f['object'])['STEAM']??0);FaBARCSteam($f['object'],-$n);return $n;}
function FaBEVOAP(int $p,int $n=1): void {if($p===intval(GetTurnPlayer())&&GetCurrentPhase()==='MAIN')AddActionPoints($p,intval(GetActionPoints($p))+$n);}
function FaBEVOEnter(int $p,object $o,bool $triggerStasis=true): void {
 if(!FaBHasType($o,'Item'))return;$id=$o->CardID;$b=FaBWTRBase($id);$n=null;
 if(str_contains((string)CardFunctional_text_plain($id),'At the start of your turn')&&FaBHasKeyword($o,'Crank')){$n=in_array($b,['hadron_collider','mini_forcefield'],true)?5-intval(CardPitch($id)):($b==='dissolving_shield'?4-intval(CardPitch($id)):1);}
 if(in_array($b,['clamp_press','null_time_zone'],true))$n=2;
 if($b==='hyper_driver'&&FaBHasType($o,'Token'))$n=2;
 if($n!==null)FaBEVOCounter($o,'STEAM',intval(FaBObjectCounters($o)['STEAM']??0)+$n);
 if(FaBEVOCrank($p,$o)&&intval(FaBObjectCounters($o)['STEAM']??0)>0)FaBRunSourceMacro('ResolveAbility',$p,'master_cog_yellow',['mzID'=>FaBDTDSource(intval($o->UniqueID)),'evoEvent'=>'crank']);
 foreach(FaBChoiceRefs($p,'Weapons',['base'=>'symbiosis_shot']) as $r)if(intval(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['STEAM']??0)<6)FaBRunSourceMacro('ResolveAbility',$p,'symbiosis_shot',['mzID'=>$r]);
 if($triggerStasis&&$b==='stasis_cell')FaBRunSourceMacro('ResolveCard',$p,$id,['mzID'=>FaBDTDSource(intval($o->UniqueID)),'evoEvent'=>'stasis']);
}
function FaBEVODoCrank(int $p,int $uid): void {$f=FaBFindUID($uid);if(!$f||$f['player']!==$p||$f['zone']!=='Arena'||!FaBEVOCrank($p,$f['object'])||intval(FaBObjectCounters($f['object'])['STEAM']??0)<1)return;FaBARCSteam($f['object'],-1);FaBEVOAdd($p,'CRANKED');FaBEVOAP($p);}
function FaBEVOUnder(object $o): array {return array_values((array)(FaBObjectCounters($o)['SUBCARDS']??[]));}
function FaBEVOAttach(int $p,int $parent,string $r): bool {
 $f=FaBFindUID($parent);$c=FaBIdentityFromMZ($r);if(!$f||!$c||$parent===intval($c['object']->UniqueID))return false;
 $under=FaBEVOUnder($f['object']);$under=array_merge($under,FaBEVOUnder($c['object']),[$c['object']->CardID]);$c['object']->removed=true;FaBEVOCounter($f['object'],'SUBCARDS',$under);return true;
}
function FaBEVOUnderPreview(int $p,int $uid): string {$f=FaBFindUID($uid);if(!$f)return ''; $refs=[];foreach(FaBEVOUnder($f['object']) as $index=>$id){$o=AddTemp($p,CardID:$id);FaBARCSetCard(intval($o->UniqueID),'evoUnder',[$uid,$index]);$refs[]='p'.$p.'Temp-'.$o->mzIndex;}return implode('&',$refs);}
function FaBEVODestroyUnder(int $p,int $uid,string $r,string $previews): bool {
 $f=FaBIdentityFromMZ($r);$data=$f?FaBARCCard(intval($f['object']->UniqueID),'evoUnder'):null;$parent=FaBFindUID($uid);$ok=false;
 if($parent&&is_array($data)&&intval($data[0])===$uid){$cards=FaBEVOUnder($parent['object']);$i=intval($data[1]);if(isset($cards[$i])){if(!FaBHasType($cards[$i],'Token'))AddGraveyard($p,CardID:$cards[$i],Owner:$p);array_splice($cards,$i,1);FaBEVOCounter($parent['object'],'SUBCARDS',$cards);$ok=true;}}
 foreach(explode('&',$previews) as $ref){$f=FaBIdentityFromMZ($ref);if($f&&$f['zone']==='Temp')$f['object']->removed=true;}return $ok;
}
function FaBEVOTransform(int $p,string $r,string $drivers=''): bool {
 $f=FaBIdentityFromMZ($r);if(!$f)return false;$base=FaBEvoBase($p,$f['object']);if(!$base)return false;
 $old=$base->CardID;$new=$f['object']->CardID;$uid=intval($f['object']->UniqueID);$under=FaBEVOUnder($base);$under[]=$old;
 foreach(FaBUPRUIDs($drivers) as $d){$df=FaBFindUID($d);if(!$df||$df['player']!==$p||!in_array($df['zone'],['Arena','Stack'],true)||FaBWTRBase($df['object']->CardID)!=='hyper_driver')return false;}
 foreach(FaBUPRUIDs($drivers) as $d){$df=FaBFindUID($d);$under[]=$df['object']->CardID;$df['object']->removed=true;}
 $base->removed=true;$o=$f['zone']==='Stack'?FaBMoveStackUID($uid,'Equipment',$p):FaBMoveUID($uid,'Equipment',$p);if(!$o)return false;
 $o->Status=2;$o->TurnEffects=[];$o->Counters=['SUBCARDS'=>$under];$o->Owner=$p;$o->Controller=$p;
 FaBEVOSteelTriggers($p,$old,$new);FaBEVOEquipped($p,$o);return true;
}
function FaBEVOSteelTriggers(int $p,string $old,string $new,bool $hero=false): void {
 if($old===$new||!FaBHasType($old,'Evo')||!FaBHasType($new,'Evo'))return;
 foreach([$old,$new] as $id)if(str_starts_with($id,'evo_steel_soul_'))for($i=0;$i<($hero?2:1);++$i)FaBRunSourceMacro('ResolveAbility',$p,$id,['mzID'=>'','evoEvent'=>'steel']);
}
function FaBEVOEquipped(int $p,object $o): void {if(in_array($o->CardID,['adaptive_plating','adaptive_dissolver'],true)&&empty(FaBObjectCounters($o)['EVO_SLOT']))FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))]);if(str_starts_with($o->CardID,'cogwerx_base_'))FaBEVOCounter($o,'STEAM',1);if(in_array($o->CardID,['evo_zoom_call_yellow','evo_buzz_hive_yellow','evo_whizz_bang_yellow','evo_zip_line_yellow','evo_heartdrive_blue','evo_recall_blue','evo_shortcircuit_blue','evo_speedslip_blue'],true))FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID)),'mstEvent'=>'equip']);}
function FaBEVOFifth(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$uid=intval($f['object']->UniqueID);FaBMoveUID($uid,'Deck',$p);$deck=&GetDeck($p);$live=array_values(array_filter($deck,fn($o)=>is_object($o)&&empty($o->removed)));$card=null;foreach($live as $i=>$o)if(intval($o->UniqueID)===$uid){$card=$o;array_splice($live,$i,1);break;}if($card)array_splice($live,min(4,count($live)),0,[$card]);$deck=$live;}
function FaBEVOEquipProto(int $p,string $r,string $slot=''): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Inventory'||!str_starts_with($f['object']->CardID,'proto_base_'))return;foreach(FaBProfessorEquipped($p) as $o)foreach(['Head','Chest','Arms','Legs'] as $s)if(FaBHasType($f['object'],$s)&&FaBHasType($o,$s))FaBMONDestroy(intval($o->UniqueID));$o=FaBMoveUID(intval($f['object']->UniqueID),'Equipment',$p);if($o)FaBEVOEquipped($p,$o);}
function FaBEVOScrap(int $p,int $uid,string $refs): void {$n=0;foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&$f['player']===$p&&$f['zone']==='Graveyard'&&(FaBHasType($f['object'],'Item')||FaBHasType($f['object'],'Equipment'))){FaBMoveUID($u,'Banish',$p);++$n;}}FaBARCSetCard($uid,'evoScrap',$n);}
function FaBEVODashTop(int $p,object $o): bool {$refs=FaBChoiceRefs($p,'Deck');return FaBEVODash($p)&&!FaBEVOEffect($p,'DASH_PLAY')&&$refs&&intval(FaBIdentityFromMZ($refs[0])['object']->UniqueID)===intval($o->UniqueID)&&FaBHasType($o,'Mechanologist')&&FaBHasType($o,'Item')&&is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))<=1;}
function FaBEVOAsInstant(int $p,object $o): bool {return FaBEVODashTop($p,$o)||(FaBHasType($o,'Evo')&&(FaBEVOEffect($p,'INSTANT_EVO')||FaBEVOEffect($p,'TEKLO_INSTANT')));}
function FaBEVOBanishPlayable(int $p,object $o): bool {return empty($o->FaceDown)&&FaBEVOTeklo($p)&&FaBHasType($o,'Evo');}
function FaBEVOAttackRefs(): string {$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));return $f&&$f['zone']==='CombatChain'&&!empty($s['combatOpen'])?$f['mzID']:'';}
function FaBEVOPutItem(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||!FaBHasType($f['object'],'Item')||intval(CardCost($f['object']->CardID))>1)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o){$o->Controller=$p;FaBARCEnterItem($p,$o);}}
function FaBEVOShuffle(int $p,string $refs): void {foreach(FaBUPRUIDs($refs) as $uid)FaBMoveUID($uid,'Deck',$p);FaBShuffleDeck($p);}
function FaBEVOBottomCost(int $p,object $o): void {FaBARCToDeck(intval($o->Owner??0)>0?intval($o->Owner):$p,intval($o->UniqueID),false);}
function FaBEVOStasis(string $r,bool $defense=false): void {$f=FaBIdentityFromMZ($r);if(!$f)return;FaBEVOAdd($f['player'],$defense?'NO_DEFEND':'NO_ACTIVATE',1,['uid'=>intval($f['object']->UniqueID),'expiresAfterTurnOf'=>$defense?intval(GetTurnPlayer()):0,'persistentUntilUsed'=>!$defense,'evoExpiresSeat'=>$f['player'],'evoAppliedTurn'=>intval(GetTurnNumber())]);}
function FaBEVOLocked(int $p,int $uid,string $kind): bool {foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='EVO_'.$kind&&intval($e['uid']??0)===$uid)return true;return false;}
function FaBEVOModular(int $p,int $uid,string $slot): void {$f=FaBFindUID($uid);if(!$f||!in_array($slot,['Head','Chest','Arms','Legs'],true))return;foreach(FaBProfessorEquipped($p) as $o)if(intval($o->UniqueID)!==$uid&&FaBHasType($o,$slot))FaBMONDestroy(intval($o->UniqueID));FaBEVOCounter($f['object'],'EVO_SLOT',$slot);}
function FaBEVOSingularity(int $p,int $uid): void {
 $evos=array_values(array_filter(FaBProfessorEquipped($p),fn($o)=>FaBHasType($o,'Evo')));$weapons=FaBChoiceRefs($p,'Weapons');$hero=GetHero($p)[0]??null;if(count($evos)!==4||!$weapons||!$hero)return;$ids=[];
 foreach($evos as $o){$ids[]=$o->CardID;foreach(FaBEVOUnder($o) as $id)AddSoul($p,CardID:$id);$o->Counters=[];FaBMoveUID(intval($o->UniqueID),'Soul',$p);}
 foreach([$weapons[0],FaBDTDSource(intval($hero->UniqueID))] as $r){$f=FaBIdentityFromMZ($r);if($f)FaBMoveUID(intval($f['object']->UniqueID),'Soul',$p);}
 $h=&GetHero($p);$h=[];$o=FaBMoveUID($uid,'Hero',$p);if(!$o)return;$o->CardID='teklovossen_the_mechropotent';$o->Counters=[];$o->TurnEffects=[];
 foreach($ids as $id)FaBEVOSteelTriggers($p,$id,$o->CardID,true);
}
function FaBEVODeckView(int $p,bool $self): ?object {if(!$self||!FaBEVODash($p))return null;$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);return $f?clone $f['object']:null;}
function FaBEVOClearPeek(int $p,array $uids): void {FaBDYNFinishPeek($p,$uids,false);}
function FaBEVOPutDriver(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||FaBWTRBase($f['object']->CardID)!=='hyper_driver')return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o)FaBARCEnterItem($p,$o);}
function FaBEVOHyperScrap(int $p,int $uid,string $refs): void {$drivers=0;foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&FaBWTRBase($f['object']->CardID)==='hyper_driver')++$drivers;}FaBEVOScrap($p,$uid,$refs);FaBARCSetCard($uid,'evoDrivers',$drivers);}
function FaBEVODestroyDefenders(): void {$s=FaBGetState();foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))FaBMONDestroy(intval($o->UniqueID));}}
function FaBEVOEquipment(int $p,bool $all=false,bool $evo=false): string {$refs=[];foreach($all?FaBLiveSeats():[$p] as $seat){foreach(FaBProfessorEquipped($seat) as $o)if(!$evo||FaBHasType($o,'Evo'))$refs[]=FaBDTDSource(intval($o->UniqueID));if(FaBMONHero($seat,'teklovossen_the_mechropotent'))$refs[]=FaBDTDSource(FaBUPRHeroUID($seat));}return implode('&',$refs);}
function FaBEVOPulseChoices(string $refs,int $x): string {return implode('&',array_filter(explode('&',$refs),function($r)use($x){$f=FaBIdentityFromMZ($r);return $f&&FaBHasType($f['object'],'Action')&&is_numeric(CardDefense($f['object']->CardID))&&intval(CardDefense($f['object']->CardID))<$x;}));}
function FaBEVOPulseDefend(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Hand')return;$o=FaBMoveUID(intval($f['object']->UniqueID),'CombatChain',$p);$o->Role='DEFENSE';$o->FromZone='Hand';$s=FaBGetState();$o->ChainLink=intval($s['chainLink']);$s['handBlockUIDs'][]=intval($o->UniqueID);FaBSetState($s);OnDefended($p,FaBDTDSource(intval($o->UniqueID)),$p);}
function FaBEVOSetX(int $uid,int $x): void {$f=FaBFindUID($uid);$s=FaBGetState();if(!$f||!is_array($s['pendingPayment']??null)||intval($s['pendingPayment']['uid'])!==$uid)return;$n=substr_count((string)CardCost($f['object']->CardID),'X');$x=max(0,min($x,FaBEVOMaxVariableCost(intval($s['pendingPayment']['player']),$uid)));FaBARCSetCard($uid,'evoX',$x);$s=FaBGetState();$s['pendingPayment']['cost']+=max(1,$n)*$x;FaBSetState($s);}
function FaBEVOLockwave(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if($f)FaBEVOAdd($f['player'],'MUST_DEFEND',1,['uid'=>intval($f['object']->UniqueID),'attacker'=>$p]);}
function FaBEVOEmptyShield(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&intval(FaBObjectCounters($f['object'])['STEAM']??0)===0)FaBMONDestroy(intval($f['object']->UniqueID));}
function FaBEVOBackup(int $p,int $pitch): string {return implode('&',array_filter(explode('&',FaBEVORefs($p,'Graveyard','mechAA')),fn($r)=>$r!==''&&intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===$pitch));}
function FaBEVORevealTop(int $p): int {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return 0;FaBRevealChoices($p,$r);return intval(CardPitch($f['object']->CardID));}
function FaBEVOLens(int $p,int $pitch): string {if(!$pitch)return '';return implode('&',array_filter(explode('&',FaBEVORefs($p,'Banish','item')),fn($r)=>$r!==''&&FaBHasType(FaBIdentityFromMZ($r)['object'],'Mechanologist')&&intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===$pitch));}
function FaBEVODestroyTop(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if($f)FaBMONDestroy(intval($f['object']->UniqueID));}
function FaBEVOResetItems(int $p,string $refs): void {$uids=FaBUPRUIDs($refs);foreach($uids as $uid){$f=FaBFindUID($uid);if($f&&$f['player']===$p&&$f['zone']==='Arena')FaBMoveUID($uid,'Banish');}foreach($uids as $uid){$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Banish')continue;$owner=intval($f['object']->Owner??0)?:$f['player'];$o=FaBMoveUID($uid,'Arena',$owner);$o->Counters=[];$o->TurnEffects=[];$o->Controller=$owner;FaBARCEnterItem($owner,$o);}}
function FaBEVOContractBanish(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$id=$f['object']->CardID;$victim=$f['player'];FaBMoveUID(intval($f['object']->UniqueID),'Banish',$victim);FaBDYNBanished($victim,FaBFindUID(intval($f['object']->UniqueID))['object'],$p);}
function FaBEVOFaceDownArsenals(): string {$r=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'Arsenal') as $ref)if(!empty(FaBIdentityFromMZ($ref)['object']->FaceDown))$r[]=$ref;return implode('&',$r);}
function FaBEVOEmbolden(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$f['object']->FaceDown=0;if(FaBHasType($f['object'],'Defense Reaction')){FaBMONDestroy(intval($f['object']->UniqueID));FaBWTRAddEffect($p,'NEXT_WEAPON',1);}}
function FaBEVORandomItem(): void {$uids=FaBUPRUIDs(FaBEVORefs(1,'Arena','item',true));if($uids)FaBMONDestroy($uids[EngineRandomInt(0,count($uids)-1)]);}
function FaBEVOWaxOn(int $p): bool {foreach(FaBARCPlayed($p) as $id)if(FaBWTRBase($id)==='wax_on')return true;return false;}
function FaBEVOShriek(): string {$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));return $a&&FaBHasType($a['object'],'Assassin')?FaBEVOPulseChoices(FaBDefendingChoices(intval($s['defender']),true),99):'';}
function FaBEVOImperial(int $p,string $refs): void {$uids=FaBUPRUIDs($refs);if(count($uids)===2){foreach($uids as $uid)FaBARCPitchForEffect($p,FaBDTDSource($uid));}else foreach(FaBChoiceRefs($p,'Hand') as $r)FaBMoveChoice($p,$r,'Hand','Banish');}

function FaBEVORemoveSteamMany(string $refs): void {foreach(explode('&',$refs) as $r)FaBEVORemoveSteam($r);}

function FaBEVOCounter(object $o,string $key,$value): void {$c=FaBObjectCounters($o);$c[$key]=$value;$o->Counters=$c;}
function FaBEVOCreateDriver(int $p): void {$o=FaBWTRCreateArena($p,'hyper_driver');if($o)FaBEVOEnter($p,$o);}
function FaBEVOMaxVariableCost(int $p,int $uid): int {$f=FaBFindUID($uid);$pending=FaBGetState()['pendingPayment']??null;$fixed=is_array($pending)&&intval($pending['uid']??0)===$uid?intval($pending['cost']??0):0;return $f?intdiv(max(0,FaBAvailablePitch($p)-$fixed),max(1,substr_count((string)CardCost($f['object']->CardID),'X'))):0;}
function FaBEVOHeroCanDefend(int $p,object $o): bool {if($o->CardID!=='teklovossen_the_mechropotent')return false;foreach(FaBChoiceRefs($p,'CombatChain') as $r)if(!empty(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['EVO_HERO_UID']))return false;return true;}
function FaBEVOHeroDefend(int $p,object $o): object {$d=AddCombatChain($p,CardID:$o->CardID,Owner:$p,Controller:$p);$d->Counters=FaBObjectCounters($o);$d->TurnEffects=(array)$o->TurnEffects;FaBEVOCounter($d,'EVO_HERO_UID',intval($o->UniqueID));return $d;}
function FaBEVOHeroClose(int $p,object $o): bool {$uid=intval(FaBObjectCounters($o)['EVO_HERO_UID']??0);if(!$uid)return false;$f=FaBFindUID($uid);if($f)FaBEVOCounter($f['object'],'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0));$o->removed=true;return true;}

function FaBEVOSymbiosisSteam(int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Weapons'&&intval(FaBObjectCounters($f['object'])['STEAM']??0)<6)FaBARCSteam($f['object'],1);}
