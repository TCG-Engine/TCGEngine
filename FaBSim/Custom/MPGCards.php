<?php
// Mastery Pack: Guardian. Seat-scoped durations are stored outside per-turn effects.
function FaBMPGSurges(int $p): int { return count(FaBChoiceRefs($p,'Arena',['base'=>'seismic_surge'])); }
function FaBMPGAuras(int $p,string $base): array {return array_values(array_filter(FaBMONArena($p,$base),fn($r)=>!HasNoAbilities(FaBIdentityFromMZ($r)['object'])));}
function FaBMPGDuration(int $p,string $kind,string $when='next',array $data=[]): void {
 $s=FaBGetState();$s['mpgDurations'][]=$data+['player'=>$p,'kind'=>$kind,'active'=>$when==='until','started'=>false];FaBSetState($s);
}
function FaBMPGEffects(int $p,string $kind): array {return array_values(array_filter(FaBGetState()['mpgDurations']??[],fn($e)=>intval($e['player'])===$p&&$e['kind']===$kind&&!empty($e['active'])));}
function FaBMPGSuppressed(object $o): bool {if(!str_contains((string)GetGameState(),'BLIND'))return false;$p=intval($o->Owner??$o->Controller??0);return $p>0&&count(FaBMPGEffects($p,'BLIND'))>0;}
function FaBMPGTokenCount(int $p,string $id,int $n): int {
 if(FaBHasType($id,'Aura')){if(FaBMPGEffects($p,'NO_AURA'))return 0;foreach(FaBMPGEffects($p,'NO_NAMED_AURA') as $e)if($e['card']===$id)return 0;}
 if($id==='seismic_surge')$n+=count(FaBMPGAuras($p,'promising_terrain'));return $n;
}
function FaBMPGAfterMove(int $p,object $o,string $from,string $to): void {
 if($to!=='Arena')return;
 if($o->CardID==='seismic_surge')FaBWTRAddEffect($p,'MPG_CONTROLLED_SURGE',1);
 if($from==='Arena')return;
 if(FaBWTRBase($o->CardID)==='geyser_of_seismic_stirrings')FaBSetObjectCounter($o,'ENERGY',4-intval(CardPitch($o->CardID)));
 if($o->CardID==='ley_line_of_the_old_ones_blue')FaBHVYToken($p,'seismic_surge');
 if($o->CardID==='draw_a_crowd_blue')foreach(FaBLiveSeats() as $seat)DoDrawCard($seat,1);
}
function FaBMPGStart(int $p): void {
 $s=FaBGetState();foreach($s['mpgDurations']??[] as $i=>$e)if(intval($e['player'])===$p){$s['mpgDurations'][$i]['started']=true;$s['mpgDurations'][$i]['active']=true;}FaBSetState($s);
 if(FaBMPGSurges($p)>0)FaBWTRAddEffect($p,'MPG_CONTROLLED_SURGE',1);
 if(FaBMPGSurges($p)>=3&&FaBWTRHeroActive($p)&&in_array(GetHero($p)[0]->CardID,['valda_seismic_impact','valda_brightaxe'],true))FaBEVRAdd($p,'VALDA');
 foreach(['daily_grind','seismic_shelter'] as $b)foreach(FaBMPGAuras($p,$b) as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBMPGBegin(int $p): void {
 foreach(FaBMPGAuras($p,'promising_terrain') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));if(FaBMPGSurges($p)>=3){DoDrawCard($p,1);FaBCRUGainLife($p,1);}}
 foreach(FaBMPGAuras($p,'draw_a_crowd') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));FaBWTRAddEffect($p,'NEXT_GUARDIAN',3);}
}
function FaBMPGEnd(int $p): void {
 foreach(FaBMPGAuras($p,'geyser_of_seismic_stirrings') as $r){$o=FaBIdentityFromMZ($r)['object'];$n=intval(FaBObjectCounters($o)['ENERGY']??0);if($n>0){FaBSetObjectCounter($o,'ENERGY',$n-1);FaBHVYToken($p,'seismic_surge');}if($n<=1)FaBMONDestroy(intval($o->UniqueID));}
 if(!FaBMPGSurges($p))foreach(FaBMPGAuras($p,'ley_line_of_the_old_ones') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBMPGExpire(int $p): void {$s=FaBGetState();$s['mpgDurations']=array_values(array_filter($s['mpgDurations']??[],fn($e)=>!($e['player']===$p&&!empty($e['started']))));FaBSetState($s);}
function FaBMPGDamaged(int $p,int $n): void {if($n>0)foreach(FaBMPGAuras($p,'ley_line_of_the_old_ones') as $r)FaBHVYToken($p,'seismic_surge');}
function FaBMPGPower(int $p,object $o): int {
 if(HasNoAbilities($o))return 0;$b=FaBWTRBase($o->CardID);$v=intval(FaBGetState()['defender']);
 if($b==='little_big_foot'){ $n=0;foreach(FaBChoiceRefs($p,'Pitch') as $r)if(intval(CardCost(FaBIdentityFromMZ($r)['object']->CardID))>=3)++$n;return $n>=2?7-intval(CardPitch($o->CardID)):0; }
 if($b==='fault_line')return count(FaBChoiceRefs($p,'Arsenal'))>0?1:0;
 if($b==='renounce_grandeur'&&FaBFaiHeroHit())return FaBMPGTokenAuras($v)!==''?1:0;
 if($b==='headbutt'&&FaBFaiHeroHit())return FaBMPGEquipment($p,'Head')!==''&&FaBMPGEquipment($v,'Head')===''?1:0;return 0;
}
function FaBMPGDefense(int $p,object $o): int {
 $n=0;$b=FaBWTRBase($o->CardID);$s=FaBGetState();$defending=in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true);
 if(!HasNoAbilities($o)){
 if($b==='testament_of_valahai')$n+=FaBMPGSurges($p)>=6?4:(FaBMPGSurges($p)>=3?2:0);
 if($b==='tremor_of_resistance'&&FaBMPGSurges($p))$n+=2;
 if($defending&&in_array($b,['base_of_the_mountain','hoarding_of_denial'],true))foreach(FaBLiveSeats() as $seat)foreach(FaBZoneGet('CombatChain',$seat) as $c)if(empty($c->removed)&&in_array($c->Role,['DEFENSE','DEFENSE_REACTION'],true)&&($b==='hoarding_of_denial'?intval(CardCost($c->CardID))>=3:(intval($c->ChainLink)===intval($s['chainLink'])&&FaBHasType($c,'Action'))))++$n;
 }
 if($defending&&FaBWTRIsAttackAction($o))$n+=count(FaBMPGAuras($p,'seismic_shelter'))*FaBMPGSurges($p);
 if($defending){$a=FaBFindUID(intval($s['attackUID']));if($a&&FaBHVYOwnedPower($p,$o)>FaBAttackPower($s))foreach(FaBLiveSeats() as $seat)foreach(FaBZoneGet('CombatChain',$seat) as $c)if(empty($c->removed)&&$c->CardID==='captain_of_the_guard_blue'&&$c->Role==='DEFENSE'&&!HasNoAbilities($c))++$n;}
 return $n;
}
function FaBMPGEquipment(int $p,string $slot=''): string {
 $refs=array_merge(FaBChoiceRefs($p,'Equipment'),FaBChoiceRefs($p,'CombatChain'));
 return implode('&',array_filter($refs,function($r)use($slot){$o=FaBIdentityFromMZ($r)['object'];return FaBHasType($o,'Equipment')&&(($o->Location??'')!=='CombatChain'||($o->FromZone??'')==='Equipment')&&($slot===''||FaBHasType($o,$slot));}));
}
function FaBMPGCounter(string $r,bool $destroy=false): void {$f=FaBIdentityFromMZ($r);if(!$f)return;$o=$f['object'];FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+1);if($destroy&&FaBCurrentDefense($o,$f['player'])===0)FaBMONDestroy(intval($o->UniqueID));}
function FaBMPGBlockLegal(object $o): bool {$a=FaBFindUID(intval(FaBGetState()['attackUID']));return !$a||$a['object']->CardID!=='headbutt_blue'||HasNoAbilities($a['object'])||!FaBHasType($o,'Equipment')||FaBHasType($o,'Head');}
function FaBMPGNext(int $p,int $power=0,string $tag='',bool $arsenal=false,bool $action=true): void {FaBWTRAddEffect($p,'MPG_NEXT',1,['power'=>$power,'tag'=>$tag,'arsenal'=>$arsenal,'action'=>$action]);}
function FaBMPGPlayed(int $p,object $o,string $from): void {
 if(FaBHasType($o,'Guardian')&&(($o->Kind??'')==='ATTACK'||FaBHasType($o,'Attack'))){$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='MPG_NEXT'&&(!$e['arsenal']||$from==='Arsenal')&&(!$e['action']||FaBWTRIsAttackAction($o))){if($e['power'])FaBWTRTag($o,'WTR_POWER:'.$e['power']);if($e['tag']!=='')FaBWTRTag($o,$e['tag']);}else $left[]=$e;}FaBWTRSetEffects($p,$left);}
 if(FaBHasType($o,'Ice')&&FaBWTRHeroActive($p)&&str_starts_with(GetHero($p)[0]->CardID,'jarl_vetreidi'))FaBROSQueue($p,GetHero($p)[0]->CardID,intval(GetHero($p)[0]->UniqueID),['rosEvent'=>'mpgJarl']);
}
function FaBMPGHit(int $p,object $o,int $n): void {if($n>=4&&FaBFaiHeroHit())foreach((array)$o->TurnEffects as $tag)if($tag==='MPG_DENT')FaBMPGMill(intval(FaBGetState()['defender']),4);}
function FaBMPGMill(int $p,int $n=1): void {for($i=0;$i<$n;++$i)FaBSEAMill($p);}
function FaBMPGDefended(int $p,object $o): void {
 $a=FaBFindUID(intval(FaBGetState()['attackUID']));if(!$a)return;
 if($a['object']->CardID==='pec_perfect_red'&&!HasNoAbilities($a['object']))FaBROSQueue(intval(FaBGetState()['attacker']),$a['object']->CardID,intval($a['object']->UniqueID),['rosEvent'=>'mpgPec','rosTarget'=>$p]);
 if(FaBWTRIsAttackAction($o))foreach(FaBMPGAuras($p,'daily_grind') as $r)FaBROSQueue($p,'daily_grind_blue',intval(FaBIdentityFromMZ($r)['object']->UniqueID),['rosEvent'=>'mpgDaily','rosTarget'=>intval(FaBGetState()['attacker'])]);
}
function FaBMPGCanStealPlay(int $p,array $f): bool {if($f['zone']!=='Arsenal'||!empty($f['object']->FaceDown)||!FaBSeatIsLive($f['player']))return false;foreach(FaBLiveSeats() as $s)foreach(FaBMPGEffects($s,'ARSENAL') as $e)if($s===$p&&intval($e['victim'])===$f['player'])return true;return false;}
function FaBMPGCanPlay(int $p,array $f): bool {if($f['zone']==='Arsenal'&&empty($f['object']->FaceDown)&&$f['player']===$p)foreach(FaBLiveSeats() as $s)foreach(FaBMPGEffects($s,'ARSENAL') as $e)if(intval($e['victim'])===$p)return false;return true;}
function FaBMPGEquipSpace(int $p,object $o): bool {
 foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBHasType($o,$slot))return FaBMPGExposed($p,$slot);
 if(FaBHasType($o,'Off-Hand')||FaBHasType($o,'Quiver')){$hands=0;foreach(FaBChoiceRefs($p,'Weapons') as $r)$hands+=FaBHasType(FaBIdentityFromMZ($r)['object'],'2H')?2:1;return $hands<2&&FaBMPGEquipment($p,'Off-Hand')===''&&FaBMPGEquipment($p,'Quiver')==='';}return false;
}
function FaBMPGEquipChoices(int $p,int $v): string {return implode('&',array_filter(explode('&',FaBMPGEquipment($v)),fn($r)=>$r!==''&&FaBMPGEquipSpace($p,FaBIdentityFromMZ($r)['object'])));}
function FaBMPGEquip(int $p,string $r): void {
 $f=FaBIdentityFromMZ($r);if(!$f||!FaBMPGEquipSpace($p,$f['object']))return;$source=clone $f['object'];$f['object']->removed=true;$new=FaBAddToZone('Equipment',$p,$source);$new->Owner=$source->Owner;$new->Controller=$p;$new->Status=$source->Status;$new->Counters=$source->Counters;$new->TurnEffects=$source->TurnEffects;
}
function FaBMPGExposed(int $p,string $slot): bool {if(FaBMPGEquipment($p,$slot)!=='')return false;foreach(FaBChoiceRefs($p,'Arena') as $r)if((FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['MPG_SLOT']??'')===$slot)return false;return true;}
function FaBMPGBaseDefend(int $p,string $refs): void {foreach(FaBUPRUIDs($refs) as $uid){$f=FaBFindUID($uid);if(!$f||$f['player']!==$p||$f['zone']!=='Hand'||!FaBHasType($f['object'],'Action'))continue;FaBMoveUID($uid,'Banish',$p);$o=FaBMoveUID($uid,'CombatChain',$p);if($o){$o->Role='DEFENSE';$o->FromZone='Banish';$o->ChainLink=intval(FaBGetState()['chainLink']);Defended($p,FaBFindUID($uid)['mzID'],$p);}}}
function FaBMPGHeaveValue(string $id): int {foreach(FaBKeywords($id) as $k)if(preg_match('/^Heave (\d+)/i',$k,$m))return intval($m[1]);return 0;}
function FaBMPGHeaveChoices(int $p): string {if(!FaBELEArsenalSpace($p))return '';return implode('&',array_filter(FaBChoiceRefs($p,'Hand'),function($r)use($p){$o=FaBIdentityFromMZ($r)['object'];$n=FaBMPGHeaveValue($o->CardID);return !HasNoAbilities($o)&&$n>0&&FaBAvailablePitch($p,intval($o->UniqueID))>=$n;}));}
function FaBMPGJarlOptions(int $p): string {$out=[];foreach(FaBOpponents($p) as $v)foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBMPGExposed($v,$slot))$out[]='Player_'.$v.'_'.$slot;return implode('&',$out);}
function FaBMPGJarl(int $source,string $choice): void {if(FaBSEAHeroCreationBlocked($source))return;if(!preg_match('/^Player_(\d+)_(Head|Chest|Arms|Legs)$/',$choice,$m))return;$p=intval($m[1]);if(!FaBSeatIsLive($p)||!FaBMPGExposed($p,$m[2]))return;$o=FaBHVYToken($p,'frostbite');if($o){$c=FaBObjectCounters($o);$c['MPG_SLOT']=$m[2];$o->Counters=$c;}}
function FaBMPGAbilityRows(): array {return ['craterhoof'=>[['ACTION',3,true,true,false,0,'Next arsenal attack gains dominate']],'gauntlet_of_boulderhold'=>[['ACTION',3,true,true,false,0,'Next arsenal attack gains power']],'richter_scale'=>[['ACTION',0,true,false,false,0,'Create two Seismic Surges']],'fearless_confrontation_blue'=>[['INSTANT',0,false,false,false,0,'Discard to weaken an attack']]];}

function FaBMPGTokenAuras(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Aura']),fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Token')));}
function FaBMPGGuardianAttack(): bool {$f=FaBFindUID(intval(FaBGetState()['attackUID']));return $f&&FaBHasType($f['object'],'Guardian');}
function FaBMPGBackup(int $p,int $except=0): string {$f=FaBFindUID($except);$name=$f?CardName($f['object']->CardID):'';return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['attackAction'=>true]),fn($r)=>CardName(FaBIdentityFromMZ($r)['object']->CardID)!==$name));}
function FaBMPGFaceUpArsenal(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal'),fn($r)=>empty(FaBIdentityFromMZ($r)['object']->FaceDown)));}
function FaBMPGDestroyAuras(int $p): void {foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($p,'Arena',['type'=>'Aura']))) as $u)FaBMONDestroy($u);}
function FaBMPGBottomArsenals(): void {foreach(FaBLiveSeats() as $p)foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($p,'Arsenal'))) as $u){$f=FaBFindUID($u);if($f)FaBUPRBottom($f['mzID']);}}
function FaBMPGDiscardHand(int $p): void {foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($p,'Hand'))) as $u){$f=FaBFindUID($u);if($f)FaBDiscardChoice($p,$f['mzID']);}}
function FaBMPGSmelt(int $p): void {foreach(FaBUPRUIDs(FaBMPGEquipment($p)) as $u){$f=FaBFindUID($u);if($f&&intval(FaBObjectCounters($f['object'])['DEFENSE']??0)>0)FaBMONDestroy($u);}}
function FaBMPGOffhands(int $p): string {return implode('&',array_filter(explode('&',FaBMPGEquipment($p,'Off-Hand')),fn($r)=>$r!==''&&FaBHasType(FaBIdentityFromMZ($r)['object'],'Guardian')));}
function FaBMPGRepair(string $r,int $n): void {$f=FaBIdentityFromMZ($r);if($f)FaBSetObjectCounter($f['object'],'DEFENSE',max(0,intval(FaBObjectCounters($f['object'])['DEFENSE']??0)-$n));}
function FaBMPGStealAura(int $p,string $r): void {$f=FaBIdentityFromMZ($r);if(!$f||$f['zone']!=='Arena'||!FaBHasType($f['object'],'Aura'))return;$source=clone $f['object'];$f['object']->removed=true;$o=FaBAddToZone('Arena',$p,$source);$o->Controller=$p;$o->Status=$source->Status;$o->Counters=$source->Counters;$o->TurnEffects=$source->TurnEffects;if($o->CardID==='seismic_surge')FaBWTRAddEffect($p,'MPG_CONTROLLED_SURGE',1);}
function FaBMPGLoseLife(int $p,int $n): void {AddHealth($p,intval(GetHealth($p))-$n);if(intval(GetHealth($p))<=0)FaBEliminateSeat($p);}

function FaBMPGFrostZone(object $o): string {return (string)(FaBObjectCounters($o)['MPG_SLOT']??'');}
function FaBMPGOutsidePower(int $p,object $o): int {if(FaBWTRBase($o->CardID)!=='little_big_foot')return 0;return FaBMPGPower($p,$o);}

function FaBMPGCounterUpdated(object $o,string $key,int $n): void {if($key==='ENERGY'&&$n<=0&&FaBWTRBase($o->CardID)==='geyser_of_seismic_stirrings'&&!HasNoAbilities($o)){ $f=FaBFindUID(intval($o->UniqueID));if($f&&$f['zone']==='Arena')FaBMONDestroy(intval($o->UniqueID));}}
