<?php
// High Seas: all choice references retain their seat and physical object identity.
function FaBSEACount(int $p,string $key): int {return FaBARCEffect($p,'SEA_'.$key);}
function FaBSEAAdd(int $p,string $key,int $n=1,array $extra=[]): void {FaBWTRAddEffect($p,'SEA_'.$key,$n,$extra);}
function FaBSEAClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='SEA_'.$key)));}
function FaBSEAWatery($o): bool {return FaBHasKeyword($o,'Watery Grave')||FaBHasKeyword($o,'Legend of the Watery Grave');}
function FaBSEAHighTide(int $p): bool {return count(FaBChoiceRefs($p,'Pitch',['pitch'=>3]))>=2;}
function FaBSEALessGold(int $p): bool {$n=count(FaBChoiceRefs($p,'Arena',['base'=>'gold']));foreach(FaBOpponents($p) as $v)if(count(FaBChoiceRefs($v,'Arena',['base'=>'gold']))>$n)return true;return false;}
function FaBSEARefs(int $p,string $kind,string $zone='Arena',bool $all=false): string {
 $out=[];foreach($all?FaBLiveSeats():[$p] as $v)foreach(FaBChoiceRefs($v,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];
  $ok=match($kind){'gun'=>FaBHasType($o,'Gun'),'bow'=>FaBHasType($o,'Bow'),'nimblism'=>FaBWTRBase($o->CardID)==='nimblism','cog'=>FaBHasType($o,'Cog'),'readyCog'=>FaBHasType($o,'Cog')&&FaBSEACanTap($o),'readyAlly'=>FaBHasType($o,'Ally')&&FaBSEACanTap($o),'ally'=>FaBHasType($o,'Ally'),'watery'=>FaBSEAWatery($o),'blue'=>intval(CardPitch($o->CardID))===3,'yellow'=>intval(CardPitch($o->CardID))===2,'item'=>FaBHasType($o,'Item'),'arrow'=>FaBHasType($o,'Arrow'),'gold'=>$o->CardID==='gold',default=>true};if($ok)$out[]=$r;
 }return implode('&',$out);
}
function FaBSEACanTap(object $o): bool {return intval($o->Status??2)===2;}
function FaBSEATap(string $ref): bool {$f=FaBIdentityFromMZ($ref);if(!$f||!in_array($f['zone'],['Hero','Weapons','Equipment','Arena','CombatChain'],true)||!FaBSEACanTap($f['object']))return false;$f['object']->Status=1;return true;}
function FaBSEACanUntap(object $o): bool {
 if($o->CardID==='havoc_wrap' && !empty(FaBObjectCounters($o)['PEN_HAVOC']))return false;
 $u=intval($o->UniqueID);$p=intval($o->Controller??$o->Owner??0);
 if(FaBHasType($o,'Hero')&&FaBSEACount($p,'RUM_LOCK')&&!FaBHasType($o,'Pirate'))return false;
 foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Arena',['base'=>'clap_em_in_irons']) as $r)if(intval(FaBARCCard(intval(FaBIdentityFromMZ($r)['object']->UniqueID),'seaIrons'))===$u)return false;
 return true;
}
function FaBSEAUntap(string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&FaBSEACanUntap($f['object']))$f['object']->Status=2;}}
function FaBSEAPermanents(int $p,string $kind='any',bool $all=true): string {
 $r=[];foreach($all?FaBLiveSeats():[$p] as $v)foreach(['Hero','Arena','Weapons','Equipment'] as $z)foreach(FaBChoiceRefs($v,$z) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if($kind==='heroAlly'&&!FaBHasType($o,'Hero')&&!FaBHasType($o,'Ally'))continue;if($kind==='pirate'&&(!FaBHasType($o,'Pirate')||(!FaBHasType($o,'Hero')&&!FaBHasType($o,'Ally'))))continue;$r[]=$ref;}return implode('&',$r);
}
function FaBSEATop(int $p): string {$r=FaBChoiceRefs($p,'Deck');return $r[0]??'';}
function FaBSEAMill(int $p): string {$r=FaBSEATop($p);$f=FaBIdentityFromMZ($r);if(!$f)return ''; $id=$f['object']->CardID;FaBMONDestroy(intval($f['object']->UniqueID));return $id;}
function FaBSEAPitchTop(int $p): string {$r=FaBSEATop($p);$f=FaBIdentityFromMZ($r);if(!$f)return ''; $id=$f['object']->CardID;FaBMoveUID(intval($f['object']->UniqueID),'Pitch',$p);AddResources($p,intval(GetResources($p))+max(0,intval(CardPitch($id))));FaBMSTPitch($p,$id,FaBMONPitchValue($p,$id));FaBWTRCardPitched($p,$id);FaBRunSourceMacro('CardPitched',$p,$id,['mzID'=>FaBDTDSource(intval($f['object']->UniqueID))]);return $id;}
function FaBSEAFaceDown(string $ref): string {$f=FaBIdentityFromMZ($ref);if(!$f||$f['zone']!=='Graveyard'||!empty($f['object']->FaceDown))return ''; $id=$f['object']->CardID;$f['object']->FaceDown=1;return $id;}
function FaBSEASalvage(int $p,string $ref): void {$id=FaBSEAFaceDown($ref);if($id!==''&&intval(CardPitch($id))===2)FaBHVYToken($p,'gold');}
function FaBSEAGravePlayable(int $p,array $f): bool {return $f['player']===$p&&$f['zone']==='Graveyard'&&empty($f['object']->FaceDown)&&FaBMONHero($p,'gravy_bones')&&FaBSEACount($p,'BLUE_GRAVE')>0&&FaBSEAWatery($f['object']);}
function FaBSEAGraveEntered(int $p,object $o,string $from,?object $source=null): void {
 if(intval(CardPitch($o->CardID))===3)FaBSEAAdd($p,'BLUE_GRAVE');
 if(FaBHasType($o,'Ally'))FaBSEAAdd($p,'ALLY_GRAVE');
 if(FaBWTRBase($o->CardID)==='fiddlers_green')FaBCRUGainLife($p,3);
 if(FaBWTRBase($o->CardID)==='sirens_of_safe_harbor')FaBCRUGainLife($p,1);
 if(FaBSEAWatery($o)&&in_array($from,['Arena','Equipment','Weapons','Hero'],true))FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>'seaWatery']);
}
function FaBSEADiscarded(int $p,string $id): void {if($id==='fools_gold_yellow')FaBHVYToken($p,'gold');if($id==='sea_legs_yellow')FaBHVYToken($p,'goldkiss_rum');}
function FaBSEASteal(int $p,string $ref,bool $temporary=false): bool {
 $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']===$p||$f['zone']!=='Arena'||(!FaBHasType($f['object'],'Item')&&!FaBHasType($f['object'],'Ally')))return false;
 $u=intval($f['object']->UniqueID);$source=clone $f['object'];$old=$f['player'];$f['object']->removed=true;
 $o=FaBAddToZone('Arena',$p,$source);$o->Controller=$p;$o->Status=$source->Status;$o->Counters=$source->Counters;$o->TurnEffects=$source->TurnEffects;
 if($temporary)FaBSEAAdd($p,'RETURN_CONTROL',1,['uid'=>$u,'player'=>$old]);
 if($o->CardID==='gold')FaBSEAAdd($p,'GOLD_GAINED');return true;
}
function FaBSEAIsland(): ?object {foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Arena',['base'=>'treasure_island']) as $r)return FaBIdentityFromMZ($r)['object'];return null;}
function FaBSEAIslandGold(int $n): int {$o=FaBSEAIsland();if(!$o)return 0;$old=intval(FaBObjectCounters($o)['SEA_GOLD']??0);$new=max(0,$old+$n);FaBSetObjectCounter($o,'SEA_GOLD',$new);return abs($new-$old);}
function FaBSEADivvy(int $p): void {$o=FaBSEAIsland();if(!$o)return;$n=intval(FaBObjectCounters($o)['SEA_GOLD']??0);$n=FaBHasType(GetHero($p)[0],'Thief')?$n:intval(ceil($n/2));FaBHVYToken($p,'gold',FaBSEAIslandGold(-$n));}
function FaBSEANext(int $p,string $kind,int $power=0,string $tag='',int $source=0): void {FaBSEAAdd($p,'NEXT',1,['kind'=>$kind,'power'=>$power,'tag'=>$tag,'source'=>$source]);}
function FaBSEAMatches(object $o,string $kind): bool {return match($kind){'gun'=>FaBHasType($o,'Gun'),'action'=>in_array($o->Kind??'',['ACTION','ATTACK'],true)||FaBHasType($o,'Action'),'first'=>FaBSEACount(intval($o->Controller),'ATTACKED')===0,'arrow'=>FaBHasType($o,'Arrow'),'mech'=>FaBHasType($o,'Mechanologist')&&(($o->Kind??'')==='ATTACK'||FaBHasType($o,'Attack')),'pirate'=>FaBHasType($o,'Pirate'),'pirateAlly'=>FaBHasType($o,'Pirate')&&!empty(FaBObjectCounters($o)['MON_ARENA_ATTACK']),'ally'=>!empty(FaBObjectCounters($o)['MON_ARENA_ATTACK']),'naa'=>FaBHasType($o,'Action')&&!FaBHasType($o,'Attack'),default=>true};}
function FaBSEAArsenal(int $p,string $ref,int $power=0,string $tag=''): bool {$f=FaBIdentityFromMZ($ref);$u=intval($f['object']->UniqueID??0);if(!FaBARCLoadArsenal($p,$ref,true,$power))return false;if($tag!==''&&$u)FaBTagUID($u,$tag);return true;}
function FaBSEAQueue(int $p,object $o,string $event,int $target=0): void {FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>$event,'rosTarget'=>$target]);}
function FaBSEAAbilitySource(): int {return intval(FaBFindUID(intval(DecisionQueueController::GetVariable('fabAbilityStackUID')))['object']->SourceUniqueID??DecisionQueueController::GetVariable('rosSource')??0);}
function FaBSEACogBuff(int $uid,bool $go=false): void {FaBTagUID($uid,$go?'GO_AGAIN':'WTR_POWER:1');}
function FaBSEAActivatedIndex(): int {return intval(DecisionQueueController::GetVariable('arcAbilityIndex'));}
function FaBSEADeckPeek(int $p,int $n=1): string {$uids=[];foreach(array_slice(FaBChoiceRefs($p,'Deck'),0,$n) as $r)$uids[]=intval(FaBIdentityFromMZ($r)['object']->UniqueID);foreach($uids as $u)FaBMoveUID($u,'Temp',$p,false);return implode('&',array_map(fn($u)=>FaBDTDSource($u),$uids));}
function FaBSEARestorePeek(int $p,string $refs): void {foreach(array_reverse(FaBUPRUIDs($refs)) as $u){$f=FaBFindUID($u);if($f&&$f['zone']==='Temp')FaBARCToDeck($p,$u,true);}}
function FaBSEABonesOptions(int $p): string {return 'Decline'.(FaBHandCount($p)>0?'&Discard_card':'').(FaBSEATop($p)!==''?'&Destroy_top_card':'');}
function FaBSEACreateCard(int $p,string $id): void {if(FaBSEAHeroCreationBlocked($p))return;AddHand($p,CardID:$id,Owner:$p,Controller:$p);}
function FaBSEAHeroCreationBlocked(int $p): bool {foreach(FaBLiveSeats() as $v)if(FaBMONArena($v,'preach_modesty'))return true;return false;}
function FaBSEAMutiny(int $p): bool {$n=count(FaBChoiceRefs($p,'Arena',['base'=>'gold']));$victims=[];foreach(FaBOpponents($p) as $v)if(count(FaBChoiceRefs($v,'Arena',['base'=>'gold']))>$n)$victims[]=$v;$stolen=false;foreach($victims as $v)$stolen=FaBSEAStealGold($p,$v)||$stolen;return $stolen;}
function FaBSEAStealGold(int $p,int $v): bool {$r=FaBChoiceRefs($v,'Arena',['base'=>'gold']);return $r?FaBSEASteal($p,$r[0]):false;}
function FaBSEAYellowArsenal(int $p): bool {foreach(FaBChoiceRefs($p,'Arsenal') as $r){$o=FaBIdentityFromMZ($r)['object'];if(empty($o->FaceDown)&&FaBHasType($o,'Arrow')&&intval(CardPitch($o->CardID))===2)return true;}return false;}
function FaBSEAUpkeepCog(int $u): void {$f=FaBFindUID($u);if(!$f)return;$n=intval(FaBObjectCounters($f['object'])['STEAM']??0);if($n>0)FaBARCSteam($f['object'],-1);else FaBMONDestroy($u);}
function FaBSEAAddSteam(string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&FaBHasType($f['object'],'Cog'))FaBARCSteam($f['object'],1);}}
function FaBSEASetBottom(int $u): void {$f=FaBFindUID($u);if(!$f)return;if($f['zone']==='Graveyard')FaBARCToDeck($f['player'],$u,false);else FaBTagUID($u,'SEA_BOTTOM');}
function FaBSEADefenders(): string {$out=[];$s=FaBGetState();foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$out[]=$r;}return implode('&',$out);}
function FaBSEAPowerCounter(string $ref): void {$f=FaBIdentityFromMZ($ref);if($f&&FaBHasType($f['object'],'Ally'))FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);}
function FaBSEATapAll(int $p): void {foreach(explode('&',FaBSEAPermanents($p,'heroAlly')) as $r)FaBSEATap($r);}
function FaBSEAIrons(int $uid,string $ref): void {$f=FaBIdentityFromMZ($ref);if(!$f)return;FaBSEATap($ref);FaBARCSetCard($uid,'seaIrons',intval($f['object']->UniqueID));}
function FaBSEAMidas(string $ref): void {$f=FaBIdentityFromMZ($ref);if(!$f||!FaBHasType($f['object'],'Ally'))return;$p=$f['player'];$n=max(0,intval(CardCost($f['object']->CardID)));FaBMONDestroy(intval($f['object']->UniqueID));FaBHVYToken($p,'gold',$n);}
function FaBSEADestroyArsenal(int $v): int {$uids=FaBUPRUIDs(implode('&',FaBChoiceRefs($v,'Arsenal')));foreach($uids as $u)FaBMONDestroy($u);return count($uids);}
function FaBSEAFishChoices(int $p,int $v,bool $cannon): string {if(!$cannon)return implode('&',FaBChoiceRefs($v,'Hand'));return FaBEVRUIDRefs(FaBDYNPrivateHand($p,$v));}
function FaBSEAGoFish(int $p,int $v,string $ref,string $kind,bool $cannon): void {
 $f=$cannon?FaBDYNPrivateOriginal($ref):FaBIdentityFromMZ($ref);if($f&&$f['player']===$v&&$f['zone']==='Hand'){$o=$f['object'];FaBRevealChoices($v,$f['mzID']);$match=match($kind){'king_shark_harpoon'=>FaBWTRIsAttackAction($o),'king_kraken_harpoon'=>FaBHasType($o,'Action')&&!FaBHasType($o,'Attack'),'red_fin_harpoon'=>intval(CardPitch($o->CardID))===1,'yellow_fin_harpoon'=>intval(CardPitch($o->CardID))===2,'blue_fin_harpoon'=>intval(CardPitch($o->CardID))===3,default=>false};if($match){FaBDiscardChoice($v,$f['mzID']);FaBHVYToken($p,'gold');}}
 if($cannon)foreach(FaBChoiceRefs($p,'Temp') as $r){$f=FaBIdentityFromMZ($r);if(FaBARCCard(intval($f['object']->UniqueID),'dynOriginal'))$f['object']->removed=true;}
}
function FaBSEAPitchChoice(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Temp'||intval(CardPitch($f['object']->CardID))!==3)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Pitch',$p);AddResources($p,intval(GetResources($p))+3);FaBMSTPitch($p,$o->CardID,3);FaBWTRCardPitched($p,$o->CardID);FaBRunSourceMacro('CardPitched',$p,$o->CardID,['mzID'=>FaBDTDSource(intval($o->UniqueID))]);}
function FaBSEAChartRest(int $p,string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if(!$f||$f['zone']!=='Temp')continue;$yellow=intval(CardPitch($f['object']->CardID))===2;FaBMONDestroy($u);if($yellow)FaBHVYToken($p,'gold');}}
function FaBSEAStackActions(int $except): string {$r=[];foreach(GetStack() as $o)if(empty($o->removed)&&intval($o->UniqueID)!==$except&&FaBHasType($o,'Action'))$r[]=FaBDTDSource(intval($o->UniqueID));return implode('&',$r);}
function FaBSEAMeldGrave(int $p,string $kind): string {$r=[];foreach(FaBLiveSeats() as $v)foreach(FaBChoiceRefs($v,'Graveyard') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if($kind==='life'?(FaBHasType($o,'Action')&&intval(CardCost($o->CardID))<FaBROSCount($p,'LIFE')):(FaBHasType($o,'Instant')||FaBHasType($o,'Aura')))$r[]=$ref;}return implode('&',$r);}
function FaBSEACosmosCount(int $p): int {return min(FaBROSCount($p,'ARCANE'),count(FaBUPRUIDs(FaBSEAMeldGrave($p,'cosmos'))));}
function FaBSEABanishChoices(string $refs): void {foreach(FaBUPRUIDs($refs) as $u){$f=FaBFindUID($u);if($f&&empty($f['object']->FaceDown))FaBMoveUID($u,'Banish',$f['player']);}}
function FaBSEATopStronger(string $r,int $u): bool {$f=FaBIdentityFromMZ($r);$a=FaBFindUID($u);return $f&&$a&&FaBAttackPower(FaBGetState())>intval(CardPower($f['object']->CardID));}
function FaBSEADestroyPhantasm(int $p): void {$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if($f&&FaBIsDefendingHero($p,$s)&&FaBHasKeyword($f['object'],'Phantasm'))FaBMONDestroy(intval($f['object']->UniqueID));}
function FaBSEAUnpreventableArcane(int $p,int $u,int $v): void {$GLOBALS['hntUnpreventableReflection']=true;try{DoDamage($p,FaBDTDSource($u),$v,1,'ARCANE');}finally{unset($GLOBALS['hntUnpreventableReflection']);}}
function FaBSEAEnterCounter(int $u,string $key,int $n): void {$f=FaBFindUID($u);if($f)FaBSetObjectCounter($f['object'],$key,$n);}
function FaBSEAReturnFire(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||$f['zone']!=='Hand'||!FaBHasType($f['object'],'Arrow'))return;$u=intval($f['object']->UniqueID);FaBMoveUID($u,'Banish',$p);FaBARCSetCard($u,'seaReturnFire',$p);}
function FaBSEASarongRefs(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal'),fn($r)=>!empty(FaBIdentityFromMZ($r)['object']->FaceDown)&&intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===3&&FaBHasType(FaBIdentityFromMZ($r)['object'],'Arrow')));}
function FaBSEASarong(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||!in_array($r,explode('&',FaBSEASarongRefs($p)),true))return;$f['object']->FaceDown=0;FaBARCArsenalFaceUp($p,$f['object'],'Arsenal');}
function FaBSEAGiveGold(int $u,string $r): bool {$f=FaBIdentityFromMZ($r);$g=FaBFindUID($u);return $f&&$g&&$f['zone']==='Hero'&&$g['object']->CardID==='gold'&&FaBSEASteal($f['player'],$g['mzID']);}
function FaBSEAHeroExcept(int $p,int $u): string {return implode('&',array_filter(explode('&',FaBDYNHeroTargets($p,false)),fn($r)=>intval(FaBIdentityFromMZ($r)['object']->UniqueID)!==$u));}
function FaBSEABarrageTargets(int $p,int $u): string {$f=FaBFindUID($u);$first=intval($f['object']->Params['attackTarget']['uid']??0);if(!$first)return '';$r=[];foreach(FaBLegalAttackTargets($p) as $t)if(intval($t['uid'])!==$first)$r[]=FaBAttackTargetMZ($t);return implode('&',$r);}
function FaBSEABarrageOptions(int $p,int $u): string {$s=FaBGetState();return 'Decline'.(FaBAvailablePitch($p,$u)>=intval($s['pendingPayment']['cost']??0)+3&&FaBSEABarrageTargets($p,$u)!==''?'&Pay_three':'');}
function FaBSEABarrage(int $u,string $r): void {$f=FaBFindUID($u);$t=FaBIdentityFromMZ($r);if(!$f||!$t||!in_array($r,explode('&',FaBSEABarrageTargets(intval($f['object']->Controller),$u)),true))return;$f['object']->Params['attackTargets']=[$f['object']->Params['attackTarget'],FaBAttackTargetDescriptor($t)];$s=FaBGetState();$s['pendingPayment']['cost']+=3;FaBSetState($s);}
function FaBSEAPolly(int $u): void {$f=FaBFindUID($u);if(!$f||$f['zone']!=='Banish')return;$p=intval($f['object']->Owner);$o=FaBMoveUID($u,'Arena',$p);if(!$o)return;$o->Status=1;FaBSetObjectCounter($o,'STEAM',1);FaBRunSourceMacro('ResolveAbility',$p,'master_cog_yellow',['mzID'=>FaBDTDSource($u),'evoEvent'=>'crank']);}
function FaBSEAStealChoices(int $p,string $r): void {foreach(FaBUPRUIDs($r) as $u){$f=FaBFindUID($u);if($f)FaBSEASteal($p,$f['mzID']);}}
function FaBSEAHeroCreate(string $helper,...$args) {if(FaBSEAHeroCreationBlocked(0))return null;return $helper(...$args);}
function FaBSEACompliance(int $u): void {if(FaBFindUID($u))FaBARCSetCard($u,'seaComplianceTurn',intval(GetTurnNumber()));}
function FaBSEAArcaneCapped(int $u): bool {return $u>0&&FaBARCCard($u,'seaComplianceTurn',-1)===intval(GetTurnNumber());}
function FaBSEAGoldCounters(object $o): int {return intval(FaBObjectCounters($o)['SEA_GOLD']??0);}
function FaBSEABalanceCounters(object $o): int {return intval(FaBObjectCounters($o)['BALANCE']??0);}
