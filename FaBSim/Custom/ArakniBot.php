<?php
// Arakni evaluates its own cards and public information; deck inspections use
// only the Temp cards exposed to this player by the resolving ability.
function FaBIsArakniBot(int $p): bool {return (FaBGetState()['botProfiles'][(string)$p]??'')==='arakni';}
function FaBArakniKeepValue(object $o,int $p): float {
 $b=FaBWTRBase($o->CardID);
 if($b==='the_hand_that_pulls_the_strings')return FaBArakniStrings($p)?4:14;
 if(FaBDYNContract($o->CardID)!=='')return floatval(CardPower($o->CardID))+4-FaBCardCost($o,$p);
 if($b==='hunted_or_hunter')return 5;
 if(in_array($b,['cut_to_the_chase','incision','razor_reflex'],true))return 6-intval(CardPitch($o->CardID));
 if($b==='shred')return 3;
 if($b==='up_sticks_and_run')return 3;
 if($b==='mask_of_perdition')return 8;
 return floatval(CardDefense($o->CardID))+2;
}
function FaBArakniFollowup(int $p,int $reserve=0): bool {
 foreach(['Hand','Arsenal'] as $z)foreach(FaBChoiceRefs($p,$z,['attackAction'=>true]) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBAvailablePitch($p,$z==='Hand'?intval($o->UniqueID):0)>=FaBCardCost($o,$p)+$reserve)return true;}
 return false;
}
function FaBArakniPlayScore(int $p,object $o,string $zone): float {
 $b=FaBWTRBase($o->CardID);$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));
 $damage=$a?max(0,FaBAttackPower($s)-FaBDefenseValue($s)):0;
 if($b==='hunted_or_hunter')return FaBIsDefendingHero($p,$s)&&$damage>0?35+min(3,$damage):-100;
 if($p!==intval(GetTurnPlayer()))return -100;
 if(FaBHasType($o,'Attack Reaction')){
  if(!$a)return -100;$gap=FaBDefenseValue($s)-FaBAttackPower($s);
  $boost=$b==='shred'?4-intval(CardPitch($o->CardID))+1:4-intval(CardPitch($o->CardID));
  if($b==='shred'&&FaBDYNDefenders($p,'ASSASSIN')==='')return -100;
  if($gap>=0&&$boost>$gap)return 40+$boost;
  if($b==='razor_reflex'&&FaBWTRIsAttackAction($a['object'])&&$damage>0&&!FaBAttackHasGoAgain($s,$a['object'])&&FaBArakniFollowup($p))return 38;
  return $damage>0&&$b!=='shred'?12+$boost:-100;
 }
 if($b==='up_sticks_and_run')return (count(FaBChoiceRefs($p,'Weapons',['type'=>'Dagger']))>0||FaBArakniRetrieveTargets($p)!=='')&&FaBAvailablePitch($p,intval($o->UniqueID))>=2?45:-100;
 if(FaBWTRIsAttackAction($o))return 22+FaBArakniKeepValue($o,$p);
 return -100;
}
function FaBArakniAbilityScore(int $p,object $o): float {
 $s=FaBGetState();$id=$o->CardID;$a=FaBFindUID(intval($s['attackUID']));$damage=$a?FaBAttackPower($s)-FaBDefenseValue($s):0;
 if($id==='the_hand_that_pulls_the_strings')return 60;
 if($id==='starting_point')return $a&&!FaBAttackHasGoAgain($s,$a['object'])&&FaBArakniFollowup($p)?38:-100;
 if($id==='mask_of_perdition')return $damage>0&&FaBDYNAttacks($p,'CONTRACT')!==''?25:-100;
 if($id==='danger_digits')return GetHealth(intval($s['defender']))<=1||(!FaBArakniFollowup($p)&&FaBAvailablePitch($p)<2)?18:-100;
 if(FaBHasType($o,'Dagger')){
  if($p!==intval(GetTurnPlayer()))return -100;
  // Spend two on a setup stab only if a contract can still be paid for.
  if(FaBArakniFollowup($p)&&!FaBArakniFollowup($p,2))return -100;
  return 36;
 }
 return -100;
}
function FaBArakniBlockScore(int $p,object $o,string $z): float {
 $s=FaBGetState();$remaining=max(0,FaBAttackPower($s)-FaBDefenseValue($s,$p));$def=FaBCurrentDefense($o,$p);if(!$remaining||$def<=0)return -100;
 $lethal=$remaining>=intval(GetHealth($p));
 if($z==='Equipment')return $o->CardID==='leap_frog_slime_skin'?10:($lethal?15:-100);
 return min($remaining,$def)*($lethal?15:3)-FaBArakniKeepValue($o,$p)+(GetHealth($p)<9?4:0);
}
function FaBArakniChoice(int $p,object $d): ?string {
 $tip=(string)$d->Tooltip;$s=FaBGetState();
 if($d->Type==='MZMODAL'){
  if($tip==='Leap_Frog_Slime_Skin')return FaBIsDefendingHero($p,$s)&&FaBAttackPower($s)>FaBDefenseValue($s,$p)?'1':'0';
 }
 if($d->Type==='MZREARRANGE'&&preg_match('/^Top=([^,;]+);Bottom=$/',$d->Param,$match)){
  $id=$match[1];
  foreach(array_merge(FaBChoiceRefs($p,'CombatChain'),array_map(fn($o)=>FaBFindUID(intval($o->UniqueID))['mzID'],array_filter(GetStack(),fn($o)=>empty($o->removed)&&intval($o->Controller)===$p))) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBDYNContractMatches(FaBDYNContract($o->CardID),$id))return $d->Param;}
  return 'Top=;Bottom='.$id;
 }
 if(!in_array($d->Type,['MZCHOOSE','MZMAYCHOOSE'],true))return null;
 $refs=array_values(array_filter(explode('&',$d->Param),fn($r)=>FaBIdentityFromMZ($r)!==null));if(!$refs)return null;
 if($tip==='Look_at_opponent_deck'){$top=FaBStackTop();$victim=intval($top->Params['attackTarget']['player']??$s['defender']);foreach($refs as $r)if(FaBIdentityFromMZ($r)['player']===$victim)return $r;}
 if($tip==='Throw_dagger'){usort($refs,fn($a,$b)=>intval(FaBIdentityFromMZ($a)['object']->Status)<=>intval(FaBIdentityFromMZ($b)['object']->Status));return $refs[0];}
 if($tip==='Choose_defending_card'){usort($refs,fn($a,$b)=>FaBCurrentDefense(FaBIdentityFromMZ($b)['object'],intval($s['defender']))<=>FaBCurrentDefense(FaBIdentityFromMZ($a)['object'],intval($s['defender'])));return $refs[0];}
 if(str_contains(strtolower($tip),'pitch')){usort($refs,fn($a,$b)=>(5*intval(CardPitch(FaBIdentityFromMZ($b)['object']->CardID))-FaBArakniKeepValue(FaBIdentityFromMZ($b)['object'],$p))<=>(5*intval(CardPitch(FaBIdentityFromMZ($a)['object']->CardID))-FaBArakniKeepValue(FaBIdentityFromMZ($a)['object'],$p)));return $refs[0];}
 return null;
}
