<?php
// Outsiders: seat-aware runtime rules. Interactive choices are saved await macros.
function FaBOUTCount(int $p,string $key): int {return FaBARCEffect($p,'OUT_'.$key);}
function FaBOUTAdd(int $p,string $key,int $n=1,array $data=[]): void {FaBWTRAddEffect($p,'OUT_'.$key,$n,$data);}
function FaBOUTClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='OUT_'.$key)));}
function FaBOUTSource(int $uid): string {return FaBFindUID($uid)['mzID']??'';}
function FaBOUTHero(int $p,string $id): bool {return FaBMONHero($p,$id);}
function FaBOUTNames(object $o): array {
 $owner=intval($o->Owner??0)?:intval(FaBFindUID(intval($o->UniqueID??0))['player']??$o->Controller??0);if($owner&&FaBOUTCount($owner,'AMNESIA'))return [];
 $names=(array)FaBARCCard(intval($o->UniqueID??0),'penNames',[(string)CardName($o->CardID)]);foreach((array)($o->TurnEffects??[]) as $t)if(str_starts_with($t,'OUT_NAME:'))$names[]=substr($t,9);return array_unique($names);
}
function FaBOUTNamed(object $o,string $name): bool {return in_array($name,FaBOUTNames($o),true);}
function FaBOUTCombo(int $p,string $kind,?object $o=null): bool {
 $s=FaBGetState();$names=$o?FaBARCCard(intval($o->UniqueID),'outPreviousNames',$s['outPreviousNames'][$p]??[]):($s['outPreviousNames'][$p]??[]);
 if(!$o){$a=FaBFindUID(intval($s['attackUID']));if($a&&intval($s['attacker'])===$p)$names=FaBOUTNames($a['object']);}
 if(empty($s['combatOpen']))return false;
 return match($kind){'descendent_gustwave'=>in_array('Surging Strike',$names,true),'bonds_of_ancestry'=>count(array_filter($names,fn($n)=>str_contains($n,'Gustwave')))>0,'dishonor'=>in_array('Bonds of Ancestry',$names,true),'back_heel_kick'=>in_array('Twin Twisters',$names,true),'one_two_punch','recoil'=>in_array('Head Jab',$names,true),'spinning_wheel_kick'=>count(array_intersect($names,['Twin Twisters','Spinning Wheel Kick']))>0,'cyclone_roundhouse'=>in_array('Spinning Wheel Kick',$names,true),default=>false};
}
function FaBOUTReaction(int $p): bool {$s=FaBGetState();return !empty($s['outReactions'][GetTurnNumber().':'.($s['chainLink']??0).':'.$p]);}
function FaBOUTReactionPlayed(int $p,bool $attack): void {$s=FaBGetState();$key=GetTurnNumber().':'.($s['chainLink']??0).':'.$p;$s['outAnyReactions'][$key]=true;if($attack)$s['outReactions'][$key]=true;FaBSetState($s);}
function FaBOUTAnyReaction(int $p): bool {$s=FaBGetState();return !empty($s['outAnyReactions'][GetTurnNumber().':'.($s['chainLink']??0).':'.$p]);}
function FaBOUTTargets(int $p,string $kind): string {
 $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f||intval($s['attacker'])!==$p||$f['zone']!=='CombatChain')return '';$o=$f['object'];
 $ok=match($kind){'stealth'=>FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Stealth'),'small'=>FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=2,'tiny'=>intval(CardPower($o->CardID))<=1,'dagger'=>FaBHasType($o,'Dagger'),'hybrid'=>FaBWTRIsAttackAction($o)&&(FaBHasType($o,'Ninja')||FaBHasType($o,'Assassin')),'combo'=>FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Combo'),'short'=>FaBHasType($o,'Dagger')||(FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=2),'aa'=>FaBWTRIsAttackAction($o),default=>true};return $ok?$f['mzID']:'';
}
function FaBOUTToken(int $p,string $token,int $n=1): void {FaBHVYToken($p,$token,$n);}
function FaBOUTDisease(int $victim,string $choice): void {FaBOUTToken($victim,['frailty','inertia','bloodrot_pox'][intval($choice)]??'frailty');}
function FaBOUTNext(int $p,string $kind,int $power=0,string $tag='',bool $chain=false): void {FaBOUTAdd($p,'NEXT',$power,['kind'=>$kind,'tag'=>$tag,'chain'=>$chain]);}
function FaBOUTNextMatches(object $o,string $from,string $kind): bool {return match($kind){'arrow'=>FaBHasType($o,'Arrow'),'stealth'=>FaBHasKeyword($o,'Stealth'),'dagger'=>FaBHasType($o,'Dagger'),'small'=>FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=2,'arsenal'=>FaBWTRIsAttackAction($o)&&$from==='Arsenal','hybrid'=>FaBWTRIsAttackAction($o)&&(FaBHasType($o,'Assassin')||FaBHasType($o,'Ranger')),'aa'=>FaBWTRIsAttackAction($o),default=>FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)};}
function FaBOUTPlayed(int $p,object $o,string $from): void {
 $uid=intval($o->UniqueID);$b=FaBWTRBase($o->CardID);$s=FaBGetState();
 if(FaBHasType($o,'Attack Reaction')||FaBHasType($o,'Defense Reaction'))FaBOUTReactionPlayed($p,FaBHasType($o,'Attack Reaction'));
 if(FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)){
  $a=FaBFindUID(intval($s['attackUID']));$names=$a&&intval($s['attacker'])===$p?FaBOUTNames($a['object']):($s['outPreviousNames'][$p]??[]);FaBARCSetCard($uid,'outPreviousNames',$names);
  $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='OUT_NEXT'&&FaBOUTNextMatches($o,$from,$e['kind'])){if($e['amount'])FaBWTRTag($o,'WTR_POWER:'.$e['amount']);if($e['tag']!=='')FaBWTRTag($o,$e['tag']);}elseif(($e['type']??'')==='OUT_NEXT_NAME'&&FaBWTRIsAttackAction($o))FaBWTRTag($o,'OUT_NAME:'.$e['name']);else $left[]=$e;}FaBWTRSetEffects($p,$left);
  if(FaBHasKeyword($o,'Stealth')){FaBOUTAdd($p,'STEALTH');if(FaBOUTCount($p,'STEALTH')===1&&FaBOUTHero($p,'arakni_solitary_confinement'))FaBWTRTag($o,'GO_AGAIN');}
  if($b==='bonds_of_ancestry'&&FaBOUTCombo($p,$b,$o))FaBWTRTag($o,'GO_AGAIN');
  if($b==='prowl')FaBOUTNext($p,'stealth',1,'',true);
 }
 if(FaBHasType($o,'Attack Reaction'))FaBOUTClear($p,'REACTION_COST');
 if(FaBWTRIsAttackAction($o))FaBOUTClear($p,'SILKEN');
 if($from==='Hand'&&FaBOUTHero($p,'riptide')&&FaBELEArsenalSpace($p)&&FaBHandCount($p)>0)FaBRunSourceMacro('ResolveAbility',$p,'riptide',['mzID'=>FaBOUTSource(FaBUPRHeroUID($p))]);
}
function FaBOUTPower(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;$s=FaBGetState();
 if(FaBHasType($o,'Dagger'))$n+=FaBOUTCount($p,'DAGGER_POWER');
 if(FaBWTRIsWeapon($o)||($o->FromZone??'')==='Arsenal')$n-=count(FaBMONArena($p,'frailty'));
 if($b==='sneak_attack'&&FaBOUTReaction($p))$n+=4;
 if(in_array($b,['descendent_gustwave','dishonor'],true)&&FaBOUTCombo($p,$b,$o))$n+=2;
 if($b==='spinning_wheel_kick'&&FaBOUTCombo($p,$b,$o))++$n;
 if(in_array($b,['falcon_wing','infecting_shot','sedation_shot','withering_shot','skybound_shot','murkmire_grapnel'],true)&&FaBDYNAimed(intval($o->UniqueID)))++$n;
 $blocks=FaBOUTDefenders();
 if($b==='widowmaker'&&count($blocks)<2)$n+=3;
 if(in_array($b,['feisty_locals','freewheeling_renegades'],true)&&array_filter($blocks,fn($f)=>FaBHasType($f['object'],'Action')))$n+=$b==='feisty_locals'?2:-2;
 if(in_array($o->CardID,['nerve_scalpel','orbitoclast','scale_peeler'],true)&&FaBDYNDefendingEquipment()!=='')++$n;
 foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='OUT_HEAD_NAME'&&FaBWTRIsAttackAction($o)&&FaBOUTNamed($o,$e['name']))$n+=$e['amount'];
 return $n;
}
function FaBOUTCost(int $p,object $o): int {
 $b=FaBWTRBase($o->CardID);$n=0;
 if($b==='bleed_out')$n-=intval(FaBGetState()['outDaggerDamage'][$p]??0);
 if($b==='descendent_gustwave'&&FaBOUTCombo($p,$b))--$n;
 if($b==='bonds_of_ancestry'&&FaBOUTCombo($p,$b))$n-=2;
 if(FaBHasType($o,'Attack Reaction'))$n-=FaBOUTCount($p,'REACTION_COST');
 if(FaBWTRIsAttackAction($o))$n-=FaBOUTCount($p,'SILKEN');return $n;
}
function FaBOUTPowerTag(object $o,string $tag): string {
 if(!preg_match('/^WTR_POWER:([1-9][0-9]*)$/',$tag,$m))return $tag;
 if(in_array('OUT_NO_GAIN',(array)($o->TurnEffects??[]),true))return '';
 $b=FaBWTRBase($o->CardID);$f=FaBFindUID(intval($o->UniqueID??0));
 if($f&&($b==='amplifying_arrow'||($b==='back_heel_kick'&&FaBOUTCombo($f['player'],$b,$o)))&&($f['zone']!=='Arsenal'||!intval($o->FaceDown)))return 'WTR_POWER:'.(intval($m[1])+1);
 return $tag;
}
function FaBOUTCanPlay(int $p,array $f): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));
 if(in_array($b,['death_touch','virulent_touch'],true)&&$f['zone']==='Hand')return false;
 if(FaBHasType($o,'Defense Reaction')){
  if($a&&in_array(FaBWTRBase($a['object']->CardID),['back_stab','widowmaker','wreck_havoc'],true))return false;
  if(FaBOUTCount($p,'BURDENS'))foreach(FaBChoiceRefs($p,'Graveyard') as $r)if(array_intersect(FaBOUTNames($o),FaBOUTNames(FaBIdentityFromMZ($r)['object'])))return false;
 }
 if($f['zone']==='Banish'&&FaBARCCard(intval($o->UniqueID),'outChainPlay')&&!$s['combatOpen'])return false;
 return true;
}
function FaBOUTCanPitch(int $p,object $o): bool {return !FaBOUTCount($p,'NO_PITCH_'.intval(CardPitch($o->CardID)));}
function FaBOUTDefenders(): array {$s=FaBGetState();$out=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$f=FaBIdentityFromMZ($r);$o=$f['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$out[]=$f;}return $out;}
function FaBOUTDefense(int $p,object $o): int {
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a)return 0;$n=0;
 foreach((array)$a['object']->TurnEffects as $t)if(str_starts_with($t,'OUT_FLETCH:')&&FaBDYNAimed(intval($a['object']->UniqueID))&&intval(CardPitch($o->CardID))===intval(substr($t,11)))--$n;return $n;
}
function FaBOUTDaggerHit(int $p,object $o,int $victim,int $damage): void {
 if(!FaBHasType($o,'Dagger')||$damage<=0)return;$s=FaBGetState();$s['outDaggerDamage'][$p]=intval($s['outDaggerDamage'][$p]??0)+$damage;$s['outDaggerHits'][$p]=intval($s['outDaggerHits'][$p]??0)+1;FaBSetState($s);
 $key=['nerve_scalpel'=>'REACTION','orbitoclast'=>'NAA','scale_peeler'=>'EQUIPMENT'][$o->CardID]??'';if($key!=='')FaBOUTAdd($victim,'DAGGER_'.$key);
 for($i=0;$i<FaBOUTCount($p,'SHIFTING');++$i)FaBRunSourceMacro('Hit',$p,'mask_of_shifting_perspectives',['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
}
function FaBOUTDefendGroup(int $p,array $uids): void {
 foreach(['REACTION','NAA','EQUIPMENT'] as $kind){$n=FaBOUTCount($p,'DAGGER_'.$kind);if(!$n)continue;$used=false;foreach($uids as $uid){$f=FaBFindUID(intval($uid));if(!$f||$f['player']!==$p)continue;$o=$f['object'];$match=match($kind){'REACTION'=>FaBHasType($o,'Attack Reaction')||FaBHasType($o,'Defense Reaction'),'NAA'=>FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o),'EQUIPMENT'=>FaBHasType($o,'Equipment')};if($match){FaBWTRTag($o,'DYN_DEFENSE:'.(-$n));$used=true;}}if($used)FaBOUTClear($p,'DAGGER_'.$kind);}
}
function FaBOUTHit(int $p,object $o,int $n): void {
 if($n<=0)return;$s=FaBGetState();$victim=intval($s['defender']);if(FaBFaiHeroHit())FaBOUTDaggerHit($p,$o,$victim,$n);
 if(FaBFaiHeroHit()&&FaBWTRIsAttackAction($o))foreach(FaBLiveSeats() as $seat)if(FaBOUTCount($seat,'PREMEDITATE')){FaBOUTToken($seat,'ponder',FaBOUTCount($seat,'PREMEDITATE'));FaBOUTClear($seat,'PREMEDITATE');}
 foreach((array)$o->TurnEffects as $t){
  if(str_starts_with($t,'OUT_DISEASE:')&&FaBFaiHeroHit())FaBOUTToken($victim,substr($t,12));
  if(str_starts_with($t,'OUT_TOXICITY:')&&FaBFaiHeroHit())FaBARCLoseLife($victim,intval(substr($t,13)),$p);
  if($t==='OUT_TOXIC_TIPS'&&FaBFaiHeroHit())FaBRunSourceMacro('Hit',$p,'death_touch_red',['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
  if($t==='OUT_CONCEALED')FaBRunSourceMacro('Hit',$p,'concealed_blade_blue',['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
  if($t==='OUT_MELTING'&&FaBDYNAimed(intval($o->UniqueID))&&FaBFaiHeroHit())FaBRunSourceMacro('Hit',$p,'melting_point_red',['mzID'=>FaBOUTSource(intval($o->UniqueID))]);
 }
}
function FaBOUTTrapCondition(object $o): bool {
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a)return false;$b=FaBWTRBase($o->CardID);
 return match($b){'bloodrot_trap','pendulum_trap','spike_pit_trap'=>FaBOUTAnyReaction(intval($s['attacker'])),'frailty_trap','collapsing_trap','tarpit_trap'=>FaBAttackHasGoAgain($s,$a['object']),'inertia_trap','boulder_trap','buzzsaw_trap'=>FaBAttackPower($s)>intval(CardPower($a['object']->CardID)),default=>false};
}
function FaBOUTTrapTriggered(int $p): void {if(FaBOUTHero($p,'riptide'))DoDamage($p,FaBOUTSource(FaBUPRHeroUID($p)),intval(FaBGetState()['attacker']),1,'PHYSICAL');}
function FaBOUTMill(int $p,int $n): array {$ids=[];foreach(array_slice(FaBChoiceRefs($p,'Deck'),0,$n) as $r){$f=FaBIdentityFromMZ($r);$ids[]=FaBOUTNames($f['object']);FaBMoveUID(intval($f['object']->UniqueID),'Graveyard',$p);}return $ids;}
function FaBOUTSpikePit(int $p): void {$victim=intval(FaBGetState()['attacker']);$names=FaBOUTMill($victim,1)[0]??[];$n=0;foreach(FaBChoiceRefs($victim,'Graveyard') as $r)if(array_intersect($names,FaBOUTNames(FaBIdentityFromMZ($r)['object'])))++$n;FaBARCLoseLife($victim,$n,$p);}
function FaBOUTCollapse(int $p): void {$victim=intval(FaBGetState()['attacker']);$refs=FaBChoiceRefs($victim,'Hand');foreach($refs as $r)FaBDiscardChoice($victim,$r);DoDrawCard($victim,max(0,count($refs)-1));}
function FaBOUTPitch(int $p,string $id): void {if($id==='plague_hive_yellow')foreach(FaBOpponents($p) as $victim)FaBOUTDisease($victim,(string)EngineRandomInt(0,2));}
function FaBOUTLongEffect(int $source,int $victim,string $key,int $n=1,bool $end=false): void {FaBOUTAdd($victim,$key,$n,[$end?'expiresAfterTurnOf':'expiresAtStartOf'=>$end?$victim:$source]);}
function FaBOUTNameCard(int $uid,string $name): void {$f=FaBFindUID($uid);if($f&&FaBOUTNames($f['object']))FaBWTRTag($f['object'],'OUT_NAME:'.str_replace('_',' ',$name));}
function FaBOUTStart(int $p): void {foreach(FaBChoiceRefs($p,'Graveyard',['base'=>'redback_shroud']) as $r)FaBRunSourceMacro('StartTurn',$p,'redback_shroud',['mzID'=>$r]);}
function FaBOUTClose(): void {
 $s=FaBGetState();$s['outDaggerDamage']=[];$s['outDaggerHits']=[];$s['outPreviousNames']=[];$s['outReactions']=[];$s['outAnyReactions']=[];FaBSetState($s);
 foreach(FaBLiveSeats() as $p){FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>empty($e['chain'])&&($e['type']??'')!=='OUT_HEAD_NAME')));foreach(FaBChoiceRefs($p,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'outChainPlay'))$o->PlayableFromBanish=0;}}
}
