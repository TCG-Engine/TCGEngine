<?php
// Uprising. Persistent UIDs identify targets; card-local decisions use saved await macros.
function FaBUPRCount(int $p,string $k): int {return FaBARCEffect($p,'UPR_'.$k);}
function FaBUPRAdd(int $p,string $k,int $n=1): void {FaBWTRAddEffect($p,'UPR_'.$k,$n);}
function FaBUPRClear(int $p,string $k): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='UPR_'.$k)));}
function FaBUPRRed(int $p,int $minimum=1): bool {return count(array_filter(FaBARCPlayed($p),fn($id)=>intval(CardPitch($id))===1))>=$minimum;}
function FaBUPRBleak(): bool {foreach(FaBLiveSeats() as $p)if(FaBMONArena($p,'channel_the_bleak_expanse'))return true;return false;}
function FaBUPRFrozen(object $o): bool {return intval(FaBObjectCounters($o)['UPR_FROZEN_BY']??0)>0;}
function FaBUPRLocked(int $p): bool {foreach(FaBOpponents($p) as $seat)if($seat===intval(GetTurnPlayer())&&FaBMONArena($seat,'themai'))return true;return false;}
function FaBUPRAsh(int $p): string {return implode('&',FaBChoiceRefs($p,'Arena',['type'=>'Ash']));}
function FaBUPRTransform(int $p,string $ref,string $dragon,int $invocationUID=0): int {
 $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||$f['zone']!=='Arena'||!FaBHasType($f['object'],'Ash'))return 0;
 $material=$f['object']->CardID;$f['object']->removed=true;
 if($invocationUID){$card=FaBFindUID($invocationUID);if(!$card)return 0;$id=$card['object']->CardID;$o=FaBMoveUID($invocationUID,'Arena',$p);if(!$o)return 0;$o->CardID=$dragon;FaBSetObjectCounter($o,'UPR_INVOKED',1);$c=FaBObjectCounters($o);$c['UPR_FRONT']=$id;$o->Counters=$c;}
 else $o=FaBWTRCreateArena($p,$dragon);
 if(!$o)return 0;FaBSetObjectCounter($o,'UPR_ASH',1);$c=FaBObjectCounters($o);$c['DYN_ASH']=$material;$o->Counters=$c;$o->Status=2;
 if($dragon==='yendurai')FaBSetObjectCounter($o,'ENDURANCE',1);
 if($dragon==='ouvia')FaBRunSourceMacro('StartTurn',$p,'ouvia',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
 return intval($o->UniqueID);
}
function FaBUPRAnyTargets(int $p,bool $heroOnly=false,bool $multiple=false): string {
 $seats=$multiple?FaBLiveSeats():array_merge([$p],FaBAdjacentOpponents($p));$out=explode('&',FaBARCHeroTargets($p,false,$multiple));
 if(!$heroOnly)foreach($seats as $seat)$out=array_merge($out,FaBChoiceRefs($seat,'Arena',['type'=>'Ally']));return implode('&',array_filter(array_unique($out)));
}
function FaBUPRStoreTarget(int $uid,string $r): void {$f=FaBIdentityFromMZ($r);if($f){FaBARCSetCard($uid,'uprTargetUID',intval($f['object']->UniqueID));FaBARCSetCard($uid,'target',$f['player']);}}
function FaBUPRTarget(int $uid): ?array {return FaBFindUID(intval(FaBARCCard($uid,'uprTargetUID')));}
function FaBUPRHealth(object $o): int {return max(0,intval(FaBObjectCounters($o)['UPR_GHOST_HP']??CardHealth($o->CardID))-intval(FaBObjectCounters($o)['UPR_LOST_HP']??0));}
function FaBUPRDeal(int $p,int $sourceUID,int $targetUID,int $n,string $type='ARCANE',int $payment=0): int {
 $f=FaBFindUID($targetUID);$source=FaBFindUID($sourceUID);if(!$f||!FaBSeatIsLive($f['player']))return 0;
 if($f['zone']==='Hero')return $type==='ARCANE'?FaBELEDealArcane($p,$f['player'],$n,$payment,$sourceUID):DoDamage($p,$source['mzID']??'',$f['player'],$n,'PHYSICAL');
 $o=$f['object'];if($f['zone']!=='Arena'||!FaBHasType($o,'Ally'))return 0;
 if($o->CardID==='yendurai'&&intval(FaBObjectCounters($o)['ENDURANCE']??0)>0){FaBSetObjectCounter($o,'ENDURANCE',0);$n=max(0,$n-3);}
 if($n>0){FaBROSDamaged($p,$f['player'],$n,$type,false);FaBHNTOwnDamage($p,$n,$type);}$o->Damage=intval($o->Damage)+$n;if($n>0&&$o->CardID==='nekria'){FaBSetObjectCounter($o,'UPR_LOST_HP',intval(FaBObjectCounters($o)['UPR_LOST_HP']??0)+1);FaBWTRCreateArena($f['player'],'ash');}
 if(function_exists('QueueDamageAnimation'))QueueDamageAnimation($f['mzID'],$n,500,true,$targetUID);
 if($n>0)FaBDYNDamaged($p,$n,$source['mzID']??'');
 if($n>0&&$source&&$source['object']->CardID==='nekria')FaBUPRNekria($p,$source['object']);
 if(intval($o->Damage)>=FaBUPRHealth($o))FaBMONDestroy(intval($o->UniqueID));return $n;
}
function FaBUPRFreeze(int $p,string $ref): void {$f=FaBIdentityFromMZ($ref);if($f)FaBSetObjectCounter($f['object'],'UPR_FROZEN_BY',$p);}
function FaBUPRFreezeChoices(int $target,string $kind='Both'): string {$refs=[];if($kind!=='Ally')$refs=FaBChoiceRefs($target,'Arsenal');if($kind!=='Arsenal')$refs=array_merge($refs,FaBChoiceRefs($target,'Arena',['type'=>'Ally']));return implode('&',$refs);}
function FaBUPRIceCount(int $p): int {$n=0;foreach(['Arena','Equipment','Hero','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if($o->CardID==='frostbite'||(FaBHasType($o,'Ice')&&FaBHasType($o,'Affliction'))||FaBUPRFrozen($o))++$n;}return $n;}
function FaBUPRAfflict(int $p,int $uid,string $hero): void {$f=FaBIdentityFromMZ($hero);if(!$f||$f['zone']!=='Hero'||$f['player']===$p)return;$o=FaBMoveUID($uid,'Arena',$f['player']);if($o){$o->Owner=$p;$o->Controller=$f['player'];}}
function FaBUPRPitched(int $p,string $id): void {if(intval(CardPitch($id))===1&&FaBMONHero($p,'dromai'))FaBWTRCreateArena($p,'ash');}
function FaBUPRCost(int $p,object $o): int {return FaBHasType($o,'Draconic')?-FaBUPRInstances($p,'BLOOD'):0;}
function FaBUPRCanPlay(int $p,array $f): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);if(FaBUPRLocked($p)||FaBUPRFrozen($o))return false;
 if(((str_starts_with($b,'invoke_')&&$b!=='invoke_suraya')||in_array($b,['skittering_sands','sand_cover'],true))&&FaBUPRAsh($p)==='')return false;
 if($b==='frightmare'&&!FaBUPRCount($p,'PHANTASM_AA'))return false;
 if($b==='tome_of_firebrand'&&FaBFaiChainCount($p)<4)return false;
 if($b==='rewind'&&FaBUPRRewindTargets($p)==='')return false;
 if($b==='dragons_of_legend')return false;
 return true;
}
function FaBUPRPlayed(int $p,object $o): void {
 $b=FaBWTRBase($o->CardID);$aa=FaBWTRIsAttackAction($o);
 if(FaBHasType($o,'Draconic')&&FaBUPRCount($p,'BLOOD')){FaBUPRConsume($p,'BLOOD');}
 if(FaBUPRCount($p,'FROSTBURN')&&str_contains((string)CardFunctional_text_plain($o->CardID),'arcane damage')){FaBWTRTag($o,'UPR_FROSTBURN');FaBUPRClear($p,'FROSTBURN');}
 if($aa&&FaBUPRCount($p,'TRANSMOGRIFY')){FaBWTRTag($o,'MON_ILLUSIONIST');FaBWTRTag($o,'MON_PHANTASM');FaBWTRTag($o,'UPR_BASE:'.FaBUPRLast($p,'TRANSMOGRIFY'));FaBUPRClear($p,'TRANSMOGRIFY');}
 if($aa&&intval(CardPower($o->CardID))<=2){FaBUPRAdd($p,'SMALL');if(FaBUPRCount($p,'SMALL')===2&&FaBCRUEquipment($p,'tiger_stripe_shuko')){FaBWTRTag($o,'WTR_POWER:1');FaBWTRTag($o,'UPR_UNPREVENTABLE');}}
 if($b==='cinderskin_devotion'&&FaBFaiChainCount($p)>=2)FaBWTRTag($o,'GO_AGAIN');
  
 if($b==='spreading_flames')FaBUPRAdd($p,'SPREAD');
}
function FaBUPRAttack(int $p,object $o): void {
 $uid=intval($o->UniqueID);$b=FaBWTRBase($o->CardID);$links=FaBFaiChainCount($p);$ref=FaBFindUID($uid)['mzID'];
 if(FaBHasType($o,'Draconic')&&FaBUPRCount($p,'UPRISING')){FaBWTRTag($o,'WTR_POWER:'.FaBUPRConsume($p,'UPRISING'));}
 if($b==='lava_burst'&&intval(FaBGetState()['chainLink'])>=4)FaBWTRTag($o,'WTR_POWER:3');
 if($b==='rise_up'&&intval(FaBGetState()['chainLink'])>=4){FaBWTRTag($o,'DOMINATE');FaBWTRTag($o,'WTR_POWER:'.(2*FaBFaiChainCount($p,'Phoenix Flame')));}
 if(FaBHasType($o,'Dragon')){
  $source=FaBFindUID(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0));if($source){if(FaBDYNMaterialPhantasm($source['object']))FaBWTRTag($o,'MON_PHANTASM');}
  if(!FaBUPRCount($p,'DRAGON_ATTACK')&&FaBMONArena($p,'miragai'))FaBWTRTag($o,'MON_NO_PHANTASM');FaBUPRAdd($p,'DRAGON_ATTACK');
  if(FaBMONHero($p,'dromai')&&FaBUPRRed($p))FaBWTRTag($o,'GO_AGAIN');
  if($b==='cromai'&&$source)FaBUPRCromai($p,$source['object']);
  foreach(FaBMONArena($p,'burn_them_all') as $r){$a=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($a)['UPR_TURN']??-1)!==intval(GetTurnNumber())){FaBSetObjectCounter($a,'UPR_TURN',intval(GetTurnNumber()));FaBRunSourceMacro('ResolveAbility',$p,'burn_them_all_red',['mzID'=>$ref]);}}
 }
}
function FaBUPRPower(int $p,object $o): int {return (FaBHasType($o,'Draconic')&&intval(CardPower($o->CardID))<FaBFaiChainCount($p)?FaBUPRCount($p,'SPREAD'):0)+($o->CardID==='phoenix_flame_red'?FaBUPRCount($p,'HEAT'):0);}
function FaBUPRCromai(int $p,object $o): void {if(intval(FaBObjectCounters($o)['UPR_TURN']??-1)!==intval(GetTurnNumber())){FaBSetObjectCounter($o,'UPR_TURN',intval(GetTurnNumber()));AddActionPoints($p,intval(GetActionPoints($p))+1);}}
function FaBUPRLeaving(int $p,object $o,string $to): void {
 if($o->CardID==='cromai')FaBUPRCromai($p,$o);
 if(FaBHasType($o,'Ally')&&$to==='Graveyard'){foreach(FaBCRUEquipment($p,'silent_stilettos') as $r)if(intval(FaBObjectCounters(FaBFindUID(intval(FaBGetState()['attackUID']))['object']??$o)['MON_SOURCE_UID']??0)===intval($o->UniqueID))FaBRunSourceMacro('ResolveAbility',$p,'silent_stilettos',['mzID'=>$r]);}
 if(!empty(FaBObjectCounters($o)['UPR_FRONT']))$o->CardID=FaBObjectCounters($o)['UPR_FRONT'];
}
function FaBUPRFog(): bool {foreach(FaBLiveSeats() as $p)if(FaBMONArena($p,'fog_down'))return true;return false;}
function FaBUPRStart(int $p): void {
 foreach(FaBLiveSeats() as $seat)foreach(['Hero','Equipment','Arena','Arsenal'] as $z)foreach(FaBChoiceRefs($seat,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval(FaBObjectCounters($o)['UPR_FROZEN_BY']??0)===$p)FaBSetObjectCounter($o,'UPR_FROZEN_BY',0);}
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array(FaBWTRBase($o->CardID),['fog_down','sigil_of_protection'],true))FaBMONDestroy(intval($o->UniqueID));elseif($o->CardID==='ouvia')FaBRunSourceMacro('StartTurn',$p,'ouvia',['mzID'=>$r]);}
 foreach(FaBChoiceRefs($p,'Graveyard',['base'=>'thaw']) as $r)FaBRunSourceMacro('StartTurn',$p,'thaw_red',['mzID'=>$r]);
}
function FaBUPREnd(int $p): void {
 foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);if(in_array($b,['burn_them_all','read_the_ripples'],true))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);
  if($b==='hypothermia')FaBMONDestroy(intval($o->UniqueID));
  if($b==='frostbite')foreach(FaBMONArena($p,'frost_hex') as $hex)FaBRunSourceMacro('ResolveAbility',$p,'frost_hex_blue',['mzID'=>$r]);
 }
 foreach(FaBLiveSeats() as $seat){foreach(FaBChoiceRefs($seat,'Arena',['type'=>'Ally']) as $r){$o=FaBIdentityFromMZ($r)['object'];$o->Damage=0;if(in_array('UPR_GHOST',(array)$o->TurnEffects,true))FaBMoveUID(intval($o->UniqueID),'Equipment',$seat);}
 if(FaBUPRCount($seat,'STRATEGIC'))DoDrawCard($seat,FaBUPRCount($seat,'STRATEGIC'));foreach(FaBChoiceRefs($seat,'Equipment') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBObjectCounters($o)['UPR_DESTROY_END']??0)FaBMONDestroy(intval($o->UniqueID));}}
}
function FaBUPRUIDs(string $refs): array {$out=[];foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f)$out[]=intval($f['object']->UniqueID);}return $out;}
function FaBUPRHeroUID(int $p): int {$r=FaBChoiceRefs($p,'Hero');return $r?intval(FaBIdentityFromMZ($r[0])['object']->UniqueID):0;}
function FaBUPRControlledTargets(int $p): string {return implode('&',array_merge(FaBChoiceRefs($p,'Hero'),FaBChoiceRefs($p,'Arena',['type'=>'Ally'])));}
function FaBUPRDamageBonus(int $p,int $uid,int $n,string $type): int {$f=FaBFindUID($uid);return $type==='ARCANE'?FaBELEDamageBonus($p,$f['mzID']??'',$n+FaBMSTAmp($p,$uid)+FaBROSChorus($p,$uid)+FaBDYNArcaneBonus($uid)+intval(FaBARCCard($uid,'arcaneBonus')),$type):$n;}
function FaBUPRSmallHand(int $p): string {return implode('&',FaBChoiceRefs($p,'Hand',['attackAction'=>true,'maxCost'=>FaBFaiChainCount($p)-1]));}
function FaBUPRBanishPlayable(int $p,string $r,string $tag=''): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);if($o){$o->PlayableFromBanish=1;if($tag)FaBWTRTag($o,$tag);}}
function FaBUPRBottom(string $r): void {$f=FaBIdentityFromMZ($r);if($f)FaBMoveUID(intval($f['object']->UniqueID),'Deck',intval($f['object']->Owner??$f['player']));}
function FaBUPRTop(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$p=intval($f['object']->Owner??$f['player']);$o=FaBMoveUID(intval($f['object']->UniqueID),'Deck',$p);if($o){$deck=&GetDeck($p);$i=array_search($o,$deck,true);if($i!==false){array_splice($deck,$i,1);array_unshift($deck,$o);}}}
function FaBUPRBottomHand(int $p,string $refs): int {$n=0;foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Hand'){FaBUPRBottom($r);++$n;}}return $n;}
function FaBUPRGraveActions(int $p,int $cost): string {$out=[];foreach(FaBLiveSeats() as $s)$out=array_merge($out,FaBChoiceRefs($s,'Graveyard',['type'=>'Action','maxCost'=>$cost]));return implode('&',array_values(array_diff($out,[(string)DecisionQueueController::GetVariable('mzID')])));}
function FaBUPRRewindTargets(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Stack'),function($r){$o=FaBIdentityFromMZ($r)['object'];return ($o->Kind??'')!=='ABILITY'&&FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o);}));}
function FaBUPRRewind(string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Stack')return;$p=intval($f['object']->Controller);FaBMoveStackUID(intval($f['object']->UniqueID),'Hand',$p);AddActionPoints($p,intval(GetActionPoints($p))+1);}
function FaBUPRFrozenArsenal(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal'),fn($r)=>FaBUPRFrozen(FaBIdentityFromMZ($r)['object'])));}
function FaBUPRFreezeHero(int $p,int $target): void {foreach(array_merge(FaBChoiceRefs($target,'Hero'),FaBChoiceRefs($target,'Equipment')) as $r)FaBUPRFreeze($p,$r);}
function FaBUPRThawTargets(int $p,int $mode): string {$out=[];foreach(FaBLiveSeats() as $s)foreach(['Hero','Weapons','Equipment','Arena','Arsenal'] as $z)foreach(FaBChoiceRefs($s,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(($mode===1&&$o->CardID==='frostbite')||($mode===2&&FaBHasType($o,'Ice')&&FaBHasType($o,'Affliction'))||($mode===3&&FaBUPRFrozen($o)))$out[]=$r;}return implode('&',$out);}
function FaBUPRThaw(string $r,int $mode): void {$f=FaBIdentityFromMZ($r);if(!$f)return;if($mode===3)FaBSetObjectCounter($f['object'],'UPR_FROZEN_BY',0);else FaBMONDestroy(intval($f['object']->UniqueID));}
function FaBUPRSetX(int $uid,int $x): void {FaBEVRSetX($uid,2*$x,0);FaBARCSetCard($uid,'uprX',$x);}
function FaBUPRRaze(int $uid): int {$f=FaBFindUID($uid);if(!$f)return 0;$n=intval(FaBObjectCounters($f['object'])['RAZE']??0)+1;FaBSetObjectCounter($f['object'],'RAZE',$n);return $n;}
function FaBUPRPayRaze(int $p,int $uid,string $r,int $n): void {if(count(FaBUPRUIDs($r))!==$n)FaBMONDestroy($uid);else FaBMoveChoices($p,$r,'Graveyard','Banish');}
function FaBUPRLowestLife(int $p): bool {foreach(FaBOpponents($p) as $other)if(intval(GetHealth($p))>=intval(GetHealth($other)))return false;return true;}
function FaBUPRProtect(int $p,string $r,int $n): void {$f=FaBIdentityFromMZ($r);if($f)FaBWTRAddEffect($p,'UPR_PROTECT',$n,['sourceUID'=>intval($f['object']->UniqueID)]);}
function FaBUPRDestroyAtEnd(int $p,string $id): void {foreach(FaBCRUEquipment($p,$id) as $r)FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'UPR_DESTROY_END',1);}
function FaBUPRQuellChoices(int $p,array $used=[]): string {if(FaBAvailablePitch($p)<1)return '';$out=[];foreach(['Equipment','CombatChain'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBHasKeyword($o,'Quell 1')&&!in_array(intval($o->UniqueID),$used,true))$out[]=$r;}return implode('&',$out);}
function FaBUPRQuell(int $p,string $r): int {$f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$p||GetResources($p)<1||!FaBHasKeyword($f['object'],'Quell 1'))return 0;AddResources($p,intval(GetResources($p))-1);FaBSetObjectCounter($f['object'],'UPR_DESTROY_END',1);return 1;}
function FaBUPRPrevent(int $p,int $n,string $source): int {
 $f=FaBIdentityFromMZ($source);$uid=intval($f['object']->UniqueID??0);$left=[];
 foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='UPR_PROTECT'&&intval($e['sourceUID']??0)===$uid){$used=min($n,intval($e['amount']));$e['amount']-=$used;$n-=$used;if($e['amount']<=0)continue;}$left[]=$e;}FaBWTRSetEffects($p,$left);
 foreach(FaBChoiceRefs($p,'Arena') as $r){if($n<=0)break;$o=FaBIdentityFromMZ($r)['object'];$ward=FaBWTRBase($o->CardID)==='sigil_of_protection'?5-intval(CardPitch($o->CardID)):0;foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'UPR_WARD:'))$ward=max($ward,intval(substr($tag,9)));if($ward){$n=max(0,$n-$ward);FaBMONDestroy(intval($o->UniqueID));}}
 return $n;
}
function FaBUPRAttackChoices(int $p,string $kind): string {$f=FaBFindUID(intval(FaBGetState()['attackUID']));if(!$f)return '';$o=$f['object'];$aa=FaBWTRIsAttackAction($o);$ok=match($kind){'ZERO'=>$aa&&intval(CardCost($o->CardID))===0,'SMALL'=>$aa&&intval(CardPower($o->CardID))<=2,'DRACONIC_NINJA'=>$aa&&(FaBHasType($o,'Draconic')||FaBHasType($o,'Ninja')),'ILLUSION'=>FaBHasType($o,'Illusionist'),default=>$aa};return $ok?$f['mzID']:'';}
function FaBUPRCombustion(int $p): string {$out=[];$s=FaBGetState();foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&!FaBHasType($o,'Equipment')&&FaBCurrentDefense($o,$seat)<FaBFaiChainCount($p))$out[]=$r;}return implode('&',$out);}
function FaBUPRAttackingHero(): bool {$s=FaBGetState();$t=$s['attackTarget']??[];return ($t['type']??'HERO')==='HERO'&&intval($s['defender'])>0;}
function FaBUPRRevealRed(int $p,int $n): int {if(FaBUPRBleak())return 0;$refs=array_slice(FaBChoiceRefs($p,'Deck'),0,$n);FaBRevealChoices($p,implode('&',$refs));return count(array_filter($refs,fn($r)=>intval(CardPitch(FaBIdentityFromMZ($r)['object']->CardID))===1));}
function FaBUPREngulf(int $p): void {if(FaBUPRBleak())return;$refs=FaBChoiceRefs($p,'Deck');if(!$refs)return;FaBRevealChoices($p,$refs[0]);$o=FaBIdentityFromMZ($refs[0])['object'];if(FaBWTRIsAttackAction($o)&&intval(CardCost($o->CardID))<FaBFaiChainCount($p))FaBUPRBanishPlayable($p,$refs[0]);}
function FaBUPRTempo(int $p): void {$refs=FaBChoiceRefs($p,'Deck');if(!$refs)return;$o=FaBMoveUID(intval(FaBIdentityFromMZ($refs[0])['object']->UniqueID),'Banish',$p);if($o&&FaBWTRIsAttackAction($o)){$o->PlayableFromBanish=1;FaBSetObjectCounter($o,'EVR_PLAY_UNTIL',intval(GetTurnNumber()));}}
function FaBUPRDuplicity(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p);if($o&&FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o)){$o->PlayableFromBanish=1;FaBARCSetCard(intval($o->UniqueID),'instantTurn',intval(GetTurnNumber()));}}
function FaBUPRRestoreTop(int $p,array $uids): void {foreach(array_reverse($uids) as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['zone']==='Temp')FaBUPRTop($f['mzID']);}}
function FaBUPRBanishHandCopy(string $r,int $target): void {$f=FaBIdentityFromMZ($r);if(!$f)return;foreach(FaBChoiceRefs($target,'Hand') as $hand){$h=FaBIdentityFromMZ($hand);if($h['object']->CardID===$f['object']->CardID){FaBMoveUID(intval($h['object']->UniqueID),'Banish',$target);return;}}}
function FaBUPRWeaken(string $r,int $n): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=$f['object'];FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+$n);if(FaBCurrentDefense($o,$f['player'])===0)FaBMONDestroy(intval($o->UniqueID));}
function FaBUPRSteal(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$owner=intval($f['object']->Owner??$f['player']);$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o){$o->Owner=$owner;$o->Controller=$p;}}
function FaBUPRGhost(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$n=intval(FaBObjectCounters($f['object'])['HAUNT']??0);$o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$p);if($o){FaBSetObjectCounter($o,'UPR_GHOST_HP',$n);FaBWTRTag($o,'UPR_GHOST');FaBWTRTag($o,'MON_PHANTASM');$o->Status=2;}}
function FaBUPRFused(int $p): void {
 foreach(FaBMONArena($p,'insidious_chill') as $r){$o=FaBIdentityFromMZ($r)['object'];$n=intval(FaBObjectCounters($o)['FROST']??0);if($n>0){FaBSetObjectCounter($o,'FROST',$n-1);if($n===1)FaBMONDestroy(intval($o->UniqueID));FaBRunSourceMacro('ResolveAbility',$p,'insidious_chill_blue',['mzID'=>'']);}}
 $weather=[];foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='UPR_WEATHERVANE')$weather[]=intval($e['amount']);FaBUPRClear($p,'WEATHERVANE');foreach($weather as $n)FaBRunSourceMacro('ResolveAbility',$p,'isenhowl_weathervane_red',['mzID'=>'','uprFrostCount'=>$n]);
}
function FaBUPRDamaged(int $p,int $target,int $n,string $type,string $r): void {
 if($n<=0)return;$f=FaBIdentityFromMZ($r);
 if($type==='ARCANE'&&FaBUPRCount($p,'PERMAFROST')){$copies=FaBUPRCount($p,'PERMAFROST');FaBUPRClear($p,'PERMAFROST');FaBELEFrost($target,$n*$copies);}
 if($f&&$type==='PHYSICAL'&&in_array('UPR_LIQUEFY',(array)($f['object']->TurnEffects??[]),true))FaBRunSourceMacro('ResolveAbility',$p,'liquefy_red',['mzID'=>$r,'uprVictim'=>$target]);
 if($f&&$f['object']->CardID==='nekria')FaBUPRNekria($p,$f['object']);
 if($f&&$type==='ARCANE'&&in_array('UPR_FROSTBURN',(array)($f['object']->TurnEffects??[]),true))FaBRunSourceMacro('ResolveAbility',$p,'conduit_of_frostburn',['mzID'=>$r,'uprVictim'=>$target]);
}
function FaBUPRNekria(int $p,object $o): void {$source=FaBFindUID(intval(FaBObjectCounters($o)['MON_SOURCE_UID']??$o->UniqueID));if(!$source||$source['zone']!=='Arena')return;$o=$source['object'];FaBSetObjectCounter($o,'UPR_LOST_HP',intval(FaBObjectCounters($o)['UPR_LOST_HP']??0)+1);FaBWTRCreateArena($p,'ash');if(intval($o->Damage)>=FaBUPRHealth($o))FaBMONDestroy(intval($o->UniqueID));}
function FaBUPRDestroyed(int $p,object $o): void {if(in_array(FaBWTRBase($o->CardID),['dunebreaker_cenipai','embermaw_cenipai'],true))FaBWTRCreateArena($p,'ash');}
function FaBUPRPhantasm(int $p,object $o): void {
 if(FaBHasType($o,'Illusionist')){if(FaBWTRIsAttackAction($o))FaBUPRAdd($p,'PHANTASM_AA');foreach(FaBCRUEquipment($p,'ghostly_touch') as $r){$g=FaBIdentityFromMZ($r)['object'];FaBSetObjectCounter($g,'HAUNT',intval(FaBObjectCounters($g)['HAUNT']??0)+1);}}
 if(FaBWTRIsAttackAction($o))foreach(FaBCRUEquipment($p,'silent_stilettos') as $r)FaBRunSourceMacro('ResolveAbility',$p,'silent_stilettos',['mzID'=>$r]);
 $source=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0);if($source)FaBMONDestroy($source);
}
function FaBUPRUnpreventable(int $uid): bool {$f=FaBFindUID($uid);return $f&&(FaBMSTUnpreventable($f['object'])||in_array('UPR_UNPREVENTABLE',(array)($f['object']->TurnEffects??[]),true)||in_array(FaBWTRBase($f['object']->CardID),['malign','murkmire_grapnel'],true));}
function FaBUPRBarrierValue(string $r): int {$f=FaBIdentityFromMZ($r);if(!$f||HasNoAbilities($f['object']))return 0;$o=$f['object'];$n=0;foreach(FaBKeywords($o) as $k)if(preg_match('/Arcane Barrier (\d+)/i',$k,$m))$n=max($n,intval($m[1]));if($o->CardID==='arcanite_skullcap')$n=FaBARCLowerLife($f['player'])?3:0;if($o->CardID==='aether_sink_yellow')$n=in_array('ARC_BARRIER_2',(array)$o->TurnEffects,true)?2:0;return $n;}
function FaBUPRBarrierChoices(int $p,array $used,int $source): string {if(FaBUPRUnpreventable($source))return '';$f=FaBFindUID($source);if($f&&FaBEVRUnpreventable($f['player'],$p))return '';$out=[];foreach(['Equipment','Weapons','Arena','CombatChain'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if($z==='CombatChain'&&($o->FromZone??'')!=='Equipment')continue;$n=FaBUPRBarrierValue($r);if($n>0&&$n<=FaBAvailablePitch($p)&&!in_array(intval($o->UniqueID),$used,true))$out[]=$r;}return implode('&',$out);}
function FaBUPRAlluvionReady(int $uid,int $n): bool {$f=FaBFindUID($uid);if(!$f||$f['object']->CardID!=='alluvion_constellas'||$n<=0)return false;$o=$f['object'];$c=FaBObjectCounters($o);if(intval($c['UPR_BARRIER_TURN']??-1)===intval(GetTurnNumber()))return false;FaBSetObjectCounter($o,'UPR_BARRIER_TURN',intval(GetTurnNumber()));return intval($c['ENERGY']??0)<4;}
function FaBUPRAlluvion(int $uid,bool $gain): void {$f=FaBFindUID($uid);if($f&&$gain)FaBSetObjectCounter($f['object'],'ENERGY',min(4,intval(FaBObjectCounters($f['object'])['ENERGY']??0)+1));}
// Older set macros use a combined barrier modal. Preserve its numeric contract while
// exposing Quell destruction and optional Alluvion energy explicitly in its labels.
function FaBUPRLegacyPreventionPlans(int $p,int $damage): array {
 $source=FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'));
 if($source&&(FaBUPRUnpreventable(intval($source['object']->UniqueID))||FaBEVRUnpreventable($source['player'],$p)))return [['cost'=>0,'quell'=>[],'energy'=>0]];
 $max=FaBAvailablePitch($p);$plans=[['cost'=>0,'quell'=>[],'energy'=>0]];
 foreach(['Weapons','Equipment','Arena','CombatChain'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(HasNoAbilities($o)||($z==='CombatChain'&&($o->FromZone??'')!=='Equipment'))continue;$n=FaBUPRBarrierValue($r);$uid=intval($o->UniqueID);$options=[];
  if($n>0){$options[]=['cost'=>$n,'quell'=>[],'energy'=>$o->CardID==='alluvion_constellas'?-$uid:0];$c=FaBObjectCounters($o);if($o->CardID==='alluvion_constellas'&&intval($c['ENERGY']??0)<4&&intval($c['UPR_BARRIER_TURN']??-1)!==intval(GetTurnNumber())&&$damage>0)$options[]=['cost'=>$n,'quell'=>[],'energy'=>$uid];}
  if(FaBHasKeyword($o,'Quell 1'))$options[]=['cost'=>1,'quell'=>[$uid],'energy'=>0];
  $next=$plans;foreach($plans as $plan)foreach($options as $option){$v=['cost'=>$plan['cost']+$option['cost'],'quell'=>array_merge($plan['quell'],$option['quell']),'energy'=>$option['energy']!==0?$option['energy']:$plan['energy']];if($v['cost']<=$max)$next[]=$v;}
  $unique=[];foreach($next as $plan)$unique[$plan['cost'].'|'.implode(',',$plan['quell']).'|'.$plan['energy']]=$plan;$plans=array_values($unique);
 }
 usort($plans,fn($a,$b)=>[$a['cost'],count($a['quell']),$a['energy']]<=>[$b['cost'],count($b['quell']),$b['energy']]);return $plans;
}
function FaBUPRLegacyPreventionLabel(array $plan): string {if(!$plan['cost'])return 'Take_damage';$label='Pay_'.$plan['cost'].'_to_prevent_'.$plan['cost'];foreach($plan['quell'] as $uid){$f=FaBFindUID($uid);$label.='_Quell_'.str_replace(' ','_',CardName($f['object']->CardID??''));}if($plan['energy']>0)$label.='_and_gain_Alluvion_energy';elseif($plan['energy']<0)$label.='_Alluvion_without_energy';return $label;}
function FaBUPRHauntCounters($o): int {return intval(FaBObjectCounters($o)['HAUNT']??0);}
function FaBUPRFrostCounters($o): int {return intval(FaBObjectCounters($o)['FROST']??0);}
function FaBUPRRazeCounters($o): int {return intval(FaBObjectCounters($o)['RAZE']??0);}
function FaBUPREnduranceCounters($o): int {return intval(FaBObjectCounters($o)['ENDURANCE']??0);}
function FaBUPRDisplayHealth($o): int {return FaBHasType($o,'Ally')?max(0,FaBUPRHealth($o)-intval($o->Damage??0)):-1;}
function FaBUPRInstances(int $p,string $key): int {return count(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')==='UPR_'.$key&&intval($e['amount']??0)>0));}
function FaBUPRLast(int $p,string $key): int {$n=0;foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='UPR_'.$key)$n=intval($e['amount']??0);return $n;}
function FaBUPRConsume(int $p,string $key): int {$n=0;$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='UPR_'.$key){++$n;$e['amount']=intval($e['amount'])-1;if($e['amount']<=0)continue;}$left[]=$e;}FaBWTRSetEffects($p,$left);return $n;}
