<?php
// Support cards from the pinned Arakni deck. Choices live in saved await macros.
function FaBArakniAbilityRows(): array {return [
 'danger_digits'=>[['REACTION',0,true,false,false,0,'Throw a dagger']],
 'starting_point'=>[['REACTION',0,true,false,false,0,'Give attack go again']],
 'the_hand_that_pulls_the_strings'=>[['REACTION',0,false,false,true,0,'Turn face up']],
];}
function FaBArakniReactionKey(int $p): string {return GetTurnNumber().':'.intval(FaBGetState()['attackUID']).':'.$p;}
function FaBArakniAbilityLegal(int $p,array $f): bool {
 $id=$f['object']->CardID;
 if($id==='the_hand_that_pulls_the_strings')return $f['zone']==='Arsenal'&&intval($f['object']->FaceDown)===1;
 if($id==='danger_digits')return FaBArakniDaggers($p)!=='';
 if($id==='starting_point')return !empty(FaBGetState()['arakniReactionSteps'][FaBArakniReactionKey($p)]);
 return true;
}
function FaBArakniReactionEvent(int $p,bool $attackReaction): void {
 $s=FaBGetState();if($s['window']!=='REACTION')return;
 $key=FaBArakniReactionKey($p);$s['arakniReactionSteps'][$key]=true;
 if($attackReaction)$s['arakniAttackReactions'][$key]=true;FaBSetState($s);
 if($attackReaction)foreach(FaBOpponents($p) as $seat)foreach(FaBCRUEquipment($seat,'leap_frog_slime_skin') as $r)FaBRunSourceMacro('ResolveAbility',$seat,'leap_frog_slime_skin',['mzID'=>$r]);
}
function FaBArakniPlayed(int $p,object $o): void {FaBArakniReactionEvent($p,FaBHasType($o,'Attack Reaction'));}
function FaBArakniDaggers(int $p): string {
 $s=FaBGetState();$out=[];$active=FaBFindUID(intval($s['attackUID']));$weapon=$active?intval(FaBObjectCounters($active['object'])['WEAPON_UID']??0):0;
 foreach(FaBChoiceRefs($p,'Weapons',['type'=>'Dagger']) as $r)if(intval(FaBIdentityFromMZ($r)['object']->UniqueID)!==$weapon)$out[]=$r;
 return implode('&',$out);
}
function FaBArakniThrow(int $p,string $r): void {
 if(!in_array($r,explode('&',FaBArakniDaggers($p)),true))return;
 $f=FaBIdentityFromMZ($r);$uid=intval($f['object']->UniqueID);$victim=intval(FaBGetState()['defender']);
 $n=DoDamage($p,$r,$victim,1,'PHYSICAL');
 if($n>0){$s=FaBGetState();$s['daggerHits']=intval($s['daggerHits']??0)+1;FaBSetState($s);if($f['object']->CardID==='spiders_bite')FaBDYNAdd($victim,'SPIDER');}
 FaBMONDestroy($uid);
}
function FaBArakniLeap(int $uid): void {
 $f=FaBFindUID($uid);$s=FaBGetState();if(!$f||$f['zone']!=='Equipment'||$s['window']!=='REACTION')return;
 $p=$f['player'];$o=FaBMoveUID($uid,'CombatChain',$p);$o->Role='DEFENSE';$o->FromZone='Equipment';$o->ChainLink=intval($s['chainLink']);
 FaBSetObjectCounter($o,'DEFENDING_HERO',intval($s['defender']));OnDefended($p,FaBFindUID($uid)['mzID'],$p);
}
function FaBArakniHunted(int $p): void {$s=FaBGetState();$a=intval($s['attacker']);if(!empty($s['arakniAttackReactions'][FaBArakniReactionKey($a)]))FaBARCLoseLife($a,1,$p);}
function FaBArakniStrings(int $p): bool {foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'the_hand_that_pulls_the_strings']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(!intval($o->FaceDown)&&!HasNoAbilities($o))return true;}return false;}
function FaBArakniAttack(int $p,object $o): void {if(FaBDYNContract($o->CardID)!==''){FaBDYNAdd($p,'ARAKNI_CONTRACTS');if(FaBDYNCount($p,'ARAKNI_CONTRACTS')===1)FaBTagUID(intval($o->UniqueID),'ARAKNI_FIRST_CONTRACT');}}
function FaBArakniStringsApplies(int $p,object $o): bool {return in_array('ARAKNI_FIRST_CONTRACT',(array)($o->TurnEffects??[]),true)&&FaBArakniStrings($p);}
function FaBArakniFlip(int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Arsenal')$f['object']->FaceDown=0;}
function FaBArakniEnd(int $p): void {foreach(FaBChoiceRefs($p,'Arsenal',['base'=>'the_hand_that_pulls_the_strings']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(!intval($o->FaceDown))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);}}
function FaBArakniStringsUpkeep(int $p,int $uid,string $r): void {
 $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Arsenal')return;
 if(in_array($r,FaBMONArena($p,'silver'),true)){FaBDYNDestroyChoice($r);return;}
 FaBARCToDeck($p,$uid,false);DoDrawCard($p,1);
}
function FaBArakniRetrieveTargets(int $p): string {return FaBArakniWeaponSpace($p)&&FaBAvailablePitch($p)>=1?implode('&',FaBChoiceRefs($p,'Graveyard',['type'=>'Dagger'])):'';}
function FaBArakniWeaponSpace(int $p): bool {$n=count(FaBChoiceRefs($p,'Equipment',['type'=>'Quiver']));foreach(FaBChoiceRefs($p,'Weapons') as $r){$o=FaBIdentityFromMZ($r)['object'];$n+=FaBHasType($o,'2H')?2:1;}return $n<2;}
function FaBArakniRetrieve(int $p,int $uid): void {$f=FaBFindUID($uid);if($f&&$f['player']===$p&&$f['zone']==='Graveyard'&&FaBArakniWeaponSpace($p))FaBMoveUID($uid,'Weapons',$p);}
