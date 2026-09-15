<?php
// Uses only our own cards, offered choices, and the public combat state.
function FaBIsUzuriBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='uzuri';}
function FaBUzuriSwapValue(object $o): float {
 if(!FaBWTRIsAttackAction($o)||intval(CardCost($o->CardID))>2)return -100;
 $b=FaBWTRBase($o->CardID);$v=floatval(CardPower($o->CardID));
 if($b==='sneak_attack')$v+=4; // Activating Uzuri satisfies its reaction condition.
 if(in_array($b,['death_touch','humble','cut_down_to_size','destructive_deliberation'],true))$v+=1;
 return $v;
}
function FaBUzuriSwapTarget(int $p,int $exclude=0): ?object {
 $best=null;foreach(FaBChoiceRefs($p,'Hand') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->UniqueID)===$exclude||FaBUzuriSwapValue($o)<0)continue;if(!$best||FaBUzuriSwapValue($o)>FaBUzuriSwapValue($best))$best=$o;}return $best;
}
function FaBUzuriKeepValue(object $o,int $p): float {
 $b=FaBWTRBase($o->CardID);
 if(FaBHasKeyword($o,'Stealth'))return 7+(intval(CardPitch($o->CardID))===1?1:0);
 if(FaBUzuriSwapValue($o)>=6)return FaBUzuriSwapValue($o)+2;
 if($b==='razors_edge')return 3;
 if($b==='peace_of_mind'||$b==='unmovable')return 4;
 return floatval(CardDefense($o->CardID))+2;
}
function FaBUzuriPlayScore(int $p,object $o,string $zone): float {
 $s=FaBGetState();$b=FaBWTRBase($o->CardID);$a=FaBFindUID(intval($s['attackUID']));$damage=$a?max(0,FaBAttackPower($s)-FaBDefenseValue($s)):0;
 if($b==='peace_of_mind'||FaBHasType($o,'Defense Reaction'))return FaBIsDefendingHero($p,$s)&&$damage>0?35+min($damage,4):-100;
 if($p!==intval(GetTurnPlayer()))return -100;
 if($b==='razors_edge'){
  if(!$a||!FaBHasKeyword($a['object'],'Stealth'))return -100;
  // Swap before spending a reaction that would be lost with the old attack.
  if(FaBUzuriWantsSwap($p))return -100;
  $boost=4-intval(CardPitch($o->CardID));return $damage>0||FaBDefenseValue($s)-FaBAttackPower($s)<$boost?35:-100;
 }
 if(FaBWTRIsAttackAction($o)){
  $target=FaBUzuriSwapTarget($p,intval($o->UniqueID));
  if(FaBHasKeyword($o,'Stealth')&&$target&&FaBUzuriSwapValue($target)>=6)return 50+(FaBWTRBase($o->CardID)==='isolate'?2:0);
  return 20+floatval(CardPower($o->CardID))-FaBCardCost($o,$p);
 }
 return -100;
}
function FaBUzuriWantsSwap(int $p): bool {
 $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));$o=FaBUzuriSwapTarget($p);
 if(!$f||!$o||intval($s['attacker'])!==$p||!FaBHasKeyword($f['object'],'Stealth'))return false;
 // A swap consumes a card: require a meaningful improvement over the current attack.
 return FaBUzuriSwapValue($o)>=FaBAttackPower($s)+2;
}
function FaBUzuriAbilityScore(int $p,object $o): float {
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));$damage=$a?FaBAttackPower($s)-FaBDefenseValue($s):0;
 if(in_array($o->CardID,['uzuri','uzuri_switchblade'],true))return FaBUzuriWantsSwap($p)?70:-100;
 if($o->CardID==='mask_of_shifting_perspectives')return $a&&FaBHasType($a['object'],'Dagger')&&$damage>0&&FaBHandCount($p)>0?40:-100;
 if($o->CardID==='fisticuffs')return $a&&!FaBUzuriWantsSwap($p)&&$damage>=0&&GetResources($p)>=2?30:-100;
 if(FaBHasType($o,'Dagger')){
  // Use floating resources or spare cards; do not pitch away the stealth/swap pair.
  $fuel=intval(GetResources($p));$target=FaBUzuriSwapTarget($p);$stealth=null;
  foreach(FaBChoiceRefs($p,'Hand') as $r){$c=FaBIdentityFromMZ($r)['object'];if(FaBHasKeyword($c,'Stealth')&&(!$target||$c->UniqueID!==$target->UniqueID)){$stealth=$c;break;}}
  foreach(FaBChoiceRefs($p,'Hand') as $r){$c=FaBIdentityFromMZ($r)['object'];if(($target&&$c->UniqueID===$target->UniqueID)||($stealth&&$c->UniqueID===$stealth->UniqueID))continue;$fuel+=intval(CardPitch($c->CardID));}
  return $fuel>=2?60:-100;
 }
 return -100;
}
function FaBUzuriBlockScore(int $p,object $o,string $z): float {
 $s=FaBGetState();$damage=max(0,FaBAttackPower($s)-FaBDefenseValue($s,$p));$def=FaBCurrentDefense($o,$p);
 if($o->CardID==='ironhide_legs')return $damage>=2&&FaBAvailablePitch($p)>0?12:-100;
 if(!$damage||$def<=0)return -100;
 $lethal=$damage>=intval(GetHealth($p));
 return min($damage,$def)*($lethal?15:3)-FaBUzuriKeepValue($o,$p)+(GetHealth($p)<9?4:0);
}
function FaBUzuriChoice(int $p,object $d): ?string {
 $tip=(string)$d->Tooltip;
 if($d->Type==='MZMODAL'&&$tip==='Pay_for_equipment_defense')return FaBAvailablePitch($p)>0?'1':'0';
 if($d->Type==='MZMODAL'&&$tip==='Choose_disease')return '2';
 if(!in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true))return null;
 $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));if(!$refs)return null;
 if($tip==='Banish_card_face_down'){
  usort($refs,fn($a,$b)=>FaBUzuriSwapValue(FaBIdentityFromMZ($b)['object'])<=>FaBUzuriSwapValue(FaBIdentityFromMZ($a)['object']));return $refs[0];
 }
 if($tip==='Bottom_card_to_draw'){
  usort($refs,fn($a,$b)=>FaBUzuriKeepValue(FaBIdentityFromMZ($a)['object'],$p)<=>FaBUzuriKeepValue(FaBIdentityFromMZ($b)['object'],$p));return FaBUzuriKeepValue(FaBIdentityFromMZ($refs[0])['object'],$p)<7?$refs[0]:'PASS';
 }
 if(str_contains(strtolower($tip),'pitch')){
  usort($refs,fn($a,$b)=>(5*intval(CardPitch(FaBIdentityFromMZ($b)['object']->CardID))-FaBUzuriKeepValue(FaBIdentityFromMZ($b)['object'],$p))<=>(5*intval(CardPitch(FaBIdentityFromMZ($a)['object']->CardID))-FaBUzuriKeepValue(FaBIdentityFromMZ($a)['object'],$p)));return $refs[0];
 }
 return null;
}
